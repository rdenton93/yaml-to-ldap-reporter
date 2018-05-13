<?php

// (c) MIT License - See LICENSE for details
 
namespace Report;

/**
* @author Robbie Denton - rjdenton93@gmail.com
*/

use Symfony\Component\Yaml\Yaml;

class Yamlreporter{

    use DateConversionTrait;

    const LDAP_PORT = 389;

    private $basedn;
    private $bindParams;
    private $reports;
    private $ldap;
    private $attributes;

    public function __construct($configFile)
    {

        // Open YML file containing configuration settings and reports
        $config = YAML::parseFile($configFile);

        $this->bindParams = $config["ldap_connection"];
        $this->reports = $config["reports"];
        $this->basedn = $this->bindParams["basedn"];

    	return $this;

    }

    public function bind() 
    {

        // Bind to the domain using YML configuration settings
        $host = $this->bindParams["host"];
        $domain = $this->bindParams["domain"];
        $username = $this->bindParams["username"];
        $password = $this->bindParams["password"];

        $this->ldap = ldap_connect($host.":".self::LDAP_PORT);

        ldap_set_option($this->ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($this->ldap, LDAP_OPT_REFERRALS, 0);

        // Bind to domain, supressing warnings
        $bind = @ldap_bind($this->ldap,"$domain\\$username",$password);

        if(!$bind){

            die("Could not bind.");

        }

        return $this;

    }

    public function query($qalias,$scope=null,$scope_level=null)
    {

        // Get settings for requested report
        $report = $this->reports[$qalias];

        $query = $report["query"];
        $this->attributes = $report["attributes"];
        $this->scope_level = ($scope_level!=null)? $scope_level:"sub";


        $recursive = true;

        // Create a recursive loop, if we are piping the result to another query then this will run again
        while ($recursive) {

            $scopedn = ($scope!=null)? $scope:$this->basedn;

            $ldapquery = "";

            // Check for conitionals, needs more work
            foreach ($query as $key => $value) {

                if($key=="%beginConditional"){

                    $ldapquery .= "(".$value;
                }

                else if($key=="%endConditional"){

                    $ldapquery .= ")";

                }

                else if(@strpos($value,"|")){

                    $conditional_array  = explode("|",$value);
                    $query_pieces = "";


                    foreach($conditional_array as $piece){

                        $query_pieces .= "(".$key."=".$piece.")";

                    }

                    $ldapquery .= "(|".$query_pieces.")";


                }

                else {    

                    $value = ($value == "%q") ? "=" . $pipe_query : $value;

                    // If the value of a key is an array then run switch statement to find what were dealing with

                    if (gettype($value) == "array") {

                        $type = $value["type"];
                        $operator = $value["operator"];

                        switch ($type) {

                            // Convert value into timestamp value AD will understand
                            case "Timestamp":
                                $value = $this->convertDateToTimeStamp(new \DateTime($value["value"]));
                                $ldapquery .= "(" . $key . $operator . $value . ")";

                            break;
                        }

                    } else {


                        if ($key[0] == "!") { // If there is an inverse

                            $ldapquery .= "(!(" . substr($key, 1) . $value . "))";

                        } else {

                            $ldapquery .= "(" . $key . $value . ")";

                        }

                    }
                }    

            }

            // Add an ampersand if there are multiple queries, required by ldap
            if (count($query) > 1) {

                $ldapquery = "(&" . $ldapquery . ")";

            }

            // Run query
            $this->result = $this->runQuery($scopedn, $ldapquery, $this->attributes,$this->scope_level);


            // If we are passing the value to another query then extract its value, else set recursive to false to end the while loop

            if (array_key_exists("pipeto", $report)) {

                $this->attributes = $report["attributes"];
                $return = $report["return"];

                $report = $this->reports[$report["pipeto"]];
                $query = $report["query"];

                $pipe_query = $this->extractQueryValue($return);

            } else {

                $recursive = false;

            }


        }

        return $this;

    }

    public function runQuery($scopedn, $ldapquery, $attributes, $scopelevel)
    {

    /*  Run query based on the scope level
        ldapsearch() - scope sub
        ldapread() - scope base
        ldaplist() - scope one
    */
        switch($scopelevel)
        {

            case "sub":
            $scope = "ldap_search";
            break;

            case "one":
            $scope = "ldap_list";
            break;

            case "base":
            $scope = "ldap_read";
            break;

        }

        return @$scope($this->ldap, $scopedn, $ldapquery, $attributes);
        
    }

    private function extractQueryValue($attribute)
    {
        // Extract the result of a query to be passed through (piped) to the next query
        return ldap_get_entries($this->ldap,$this->result)["0"][strtolower($attribute)][0];

    }

    public function execute()
    {
        $resource = ldap_get_entries($this->ldap,$this->result);

        // If there are no results return an empty array
        if(count($resource)==0){

            return [];
        }

        array_shift($resource);

        $return_values = [];


        // Run through results and extract the attributes requested
        foreach($resource as $data)
        {

            $objects = [];

            foreach($this->attributes as $attribute)
            {

                if(!isset($data[strtolower($attribute)]))
                {
                    continue;
                }

                $result = $data[strtolower($attribute)];
                unset($result["count"]);
                $objects[$attribute] = $result;

            }

            $return_values[] = $objects;

        }

        // Unbind from ldap, freeing up resources
        ldap_unbind($this->ldap);

        return $this->formatContext($return_values);
    }

    public function formatContext($objects)
    {

        $formatted_objects = [];

        foreach($objects as $object){

            // Get the last element in the Object class array, which will give us its type.

            $object["objectClass"] = end($object["objectClass"]);

            // Format object based on its type

            switch(strtolower($object["objectClass"])){

                case "user":
                $object = $this->performADObjectFormatting(new ADUserObject($object));
                break;

                case "group":
                $object = $this->performADObjectFormatting(new ADGroupObject($object));
                break;


                case "computer":
                $object = $this->performADObjectFormatting(new ADComputerObject($object));
                break;



            }

            array_push($formatted_objects,$object);

        }

        return $formatted_objects;

    }

    public function performADObjectFormatting(ADObjectInterface $object)
    {

        return $object->contextFormatter()->generalFormatter()->object;
    }


}
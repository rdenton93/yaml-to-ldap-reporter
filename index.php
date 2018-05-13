<?php

// (c) MIT License - See LICENSE for details 

/**
* @author Robbie Denton - rjdenton93@gmail.com
*/

require "vendor/autoload.php";

use Report\YamlReporter;

$report = (new YamlReporter("config/config.yml"))->bind();
$result = $report->query("tree",null,"one")->execute();

echo json_encode($result,JSON_PRETTY_PRINT);
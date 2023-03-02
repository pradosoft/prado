<?php

$assetsPath = __DIR__ . "/assets";
$runtimePath = __DIR__ . "/protected/runtime";

if (!is_writable($assetsPath)) {
	die("Please make sure that the directory $assetsPath is writable by Web server process.");
}
if (!is_writable($runtimePath)) {
	die("Please make sure that the directory $runtimePath is writable by Web server process.");
}

require(__DIR__ . '/../../../vendor/autoload.php');

$application = new \Prado\TApplication;
$application->run();

<?php

return [
	'parameters' => [
		'RootParameter' => 'root',
	],
	'authorization' => [
		['action' => 'deny', 'pages' => 'Secret', 'users' => '?'],
		['action' => 'allow', 'pages' => 'Admin.*', 'roles' => 'admin'],
	],
	'pages' => [
		'properties' => ['Theme' => 'Basic'],
		'Home' => ['properties' => ['Title' => 'Root home']],
	],
];

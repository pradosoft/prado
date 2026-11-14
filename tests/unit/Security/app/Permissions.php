<?php

// PHP permission file for TPermissionsManagerTest.
return [
	'roles' => [
		'phpFileRole' => 'Cron, Manager',
	],
	'permissionrules' => [
		['name' => 'permissions_manage_roles', 'action' => 'allow', 'roles' => 'cron'],
	],
];

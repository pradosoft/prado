<?php
return array(
	'application' => array(
		'id' => 'Database',
		'Mode' => 'Debug',
	),
	'paths' => array(
		'Example' => 'APP_CODE',
		'Quickstart' => '../../quickstart',
	),
	'using' => array(
		'Quickstart.protected.controls.*',
	),
	'services' => array(
		'page' => array(
			'class' => 'TPageService',
			'properties' => array(
				'DefaultPage' => 'Manual.Overview',
			),
		),
	),
);
<?php
return array(
	'application' => array(
		'id' => 'personal',
		'mode' => 'Debug',
	),
	'paths' => array(
		'using'=>array('Application.Common.*'),
	),
	'modules' => array(	
	),
	'services' => array(
		'page' => array(
			'class' => 'TPageService',
			'properties' => array(
				'basePath' => 'Application.Pages',
			),
			'modules' => array(
				'users' => array(
					'class' => 'System.Security.TUserManager',
					'properties' => array(
						'passwordMode' => 'Clear',
					),
					'users' => array(
						array('name'=>'demo','password'=>'demo'),
					),
				), // users
				'auth' => array(
					'class' => 'System.Security.TAuthManager',
					'properties' => array(
						'userManager' => 'users',
						'loginPage' => 'UserLogin',
					),
				),
			),
		),
	),
	'parameters' => array(
		'siteName' => 'My Personal Site (PHP Config)',
	)
);
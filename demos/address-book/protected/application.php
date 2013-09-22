<?php

return array(
	'application' => array(
		'id' => 'address-book',
		'mode' => 'Debug',
	),
	'paths' => array(
		'using'=>array(
			'System.Data.*',
			'System.Security.*',
			'System.Data.ActiveRecord.*',
			'System.Web.Services.*',
		)
	),
	'modules' => array(
		'sqlite-db' => array(
			'class' => 'TActiveRecordConfig',
			'database' => array(
				'ConnectionString' => 'sqlite:./protected/pages/sqlite.db',
			),
		),
		'users' => array(
			'class' => 'TUserManager',
			'properties' => array(
				'PasswordMode' => 'Clear',
			),
			'users' => array(
				array(
					'name'=>'demo',
					'password'=>'demo'
				),
			),
		),
		'auth' => array(
			'class' => 'System.Security.TAuthManager',
			'properties' => array(
				'userManager' => 'users',
				'loginPage' => 'Login',
			),
		),
	),
	'services' => array(
		'soap' => array(
			'class' => 'TSoapService',
			'address-book' => array(
				'properties' => array(
					'provider' => 'Application.pages.AddressProvider',
					'ClassMaps' => 'AddressRecord',
				),
			),
		),
	),
);
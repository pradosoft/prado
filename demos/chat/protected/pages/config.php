<?php
return array(
	'modules' => array(
		'users' => array(
			'class' => 'ChatUserManager',
		),
		'auth' => array(
			'class' => 'TAuthManager',
			'properties' => array(
				'UserManager' => 'users',
				'LoginPage' => 'Login',
			),
		),
	),
	
	'authorization' => array(
		array(
			'action' => 'allow',
			'pages' => 'Login',
			'users' => '?',
		),
		array(
			'action' => 'allow',
			'roles' => 'normal',
		),
		array(
			'action' => 'deny',
			'users' => '*',
		),
	),
);
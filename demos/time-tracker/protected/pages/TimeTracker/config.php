<?php
return array(
	'modules' => array(
		'users' => array(
			'class' => 'Application.App_Code.UserManager',
		),
		'auth' => array(
			'class' => 'Application.App_Code.TrackerAuthManager',
			'properties' => array(
				'UserManager' => 'users',
				'LoginPage' => 'TimeTracker.Login'
			),
		),
	),
	'authorization' => array(
		array('action'=>'allow','pages'=>'ProjectList, ProjectDetails, ReportResource, ReportProject','roles'=>'manager'),
		array('action'=>'allow','pages'=>'LogTimeEntry','roles'=>'consultant'),
		array('action'=>'allow','pages'=>'UserCrate,Logout,Login','users'=>'*'),
		array('action'=>'deny','users'=>'*'),
	),
	'pages' => array(
		'properties' => array(
			'MasterClass' => 'Application.pages.TimeTracker.MainLayout',
			'Theme' => 'TimeTracker',
		),
	),
	'parameters' => array(
		'NewUserRoles' => 'admin,manager,consultant',
	),
);
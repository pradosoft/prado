<?php
return array(
	'application' => array(
		'id'=>'Time-Tracker',
		'Mode'=>'Debug'
	),
	'paths' => array(
		'using' => array(
			'System.Data.*',
			'System.Security.*',
			'Application.App_Code.*',
			'Application.App_Code.Dao.*',
			'Application.App_Data.*',
		),
	),
	'modules' => array(
		'daos' => array(
			'class' => 'DaoManager',
			'properties' => array(
				'EnableCache' => 'true',
				'configFile' => 'Application.App_Data.sqlite-sqlmap',
			),
			'daos' => array(
				'UserDao' => 'Application.App_Code.Dao.UserDao',
				'ProjectDao' => 'Application.App_Code.Dao.ProjectDao',
				'TimeEntryDao' => 'Application.App_Code.Dao.TimeEntryDao',
				'CategoryDao' => 'Application.App_Code.Dao.CategoryDao',
				'ReportDao' => 'Application.App_Code.Dao.ReportDao',
			)
		),
		'globalization' => array(
			'class' => 'System.I18N.TGlobalization',
			'properties' => array(
				'CharSet' => 'UTF-8',
			),
		),
	),
	'services' => array(
		'page' => array(
			'class' => 'TPageService',
			'properties' => array(
				'DefaultPage' => 'TimeTracker.LogTimeEntry',
			),
		),
	),
);
<?php
return array(
	'application' => array(
		'id' => 'Chat',
		'mode' => 'Debug'
	),
	'paths' => array(
		'using'=>array(
			'Application.App_Code.*',
			'System.Data.*',
			'System.Data.ActiveRecord.*',
			'System.Security.*',
			'System.Web.UI.ActiveControls.*',
		),
	),
	'modules' => array(
		'db' => array(
			'class' => 'TActiveRecordConfig',
			'properties' => array(
				'EnableCache' => 'true',
				'Database.ConnectionString'=>"sqlite:protected/App_Code/chat.db",
			)
		),
	),
);
<?php
return array(
	'application' => array(
		'id' => 'northwind-db',
		'mode' => 'Debug'
	),
	'paths' => array(
		'using'=>array(
			'System.Data.*',
			'System.Data.ActiveRecord.*',
			'System.Data.ActiveRecord.Scaffold.*',
			'Application.database.*',
		),
	),
	'modules' => array(
		'db' => array(
			'class' => 'TActiveRecordConfig',
			'database' => array(
				'ConnectionString' => 'sqlite:protected/data/Northwind.db',
			),
		),
		'i81n' => array(
			'class' => 'System.I18N.TGlobalization',
			'properties' => array(
				'DefaultCharSet'=>'UTF-8',
			),
		),
	),
);
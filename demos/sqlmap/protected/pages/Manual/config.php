<?php
return array(
	'modules' => array(
		'theme' => array(
			'class' => 'System.Web.UI.TThemeManager',
			'properties' => array(
				'BasePath' => 'Quickstart.themes',
				'BaseUrl' => '../quickstart/themes',
			),
		),
	),
	'pages' => array(
		'properties' => array(
			'MasterClass' => 'Application.pages.Manual.Layout',
			'Theme' => 'PradoSoft',
		),
	),
);
<?php
return array(
	'application' => array(
		'id' => 'blog',
		'mode' => 'Debug'
	),
	'paths' => array(
		'using' => array(
			'Application.Common.*',
		),
	),
	// Modules configured and loaded for all services
	'modules' => array(
		'request' => array(
			'class' => 'THttpRequest',
			'properties' => array(
				'UrlFormat' => 'Path',
				'UrlManager' => 'friendly-url',
			),
		),
		
		'cache' => array(
			'class' => 'System.Caching.TSqliteCache',
		),
		
		'error' => array(
			'class' => 'Application.Common.BlogErrorHandler',
		),
		array(
			'class' => 'System.Util.TLogRouter',
			'routes' => array(
				array(
					'class' => 'TFileLogRoute',
					'properties' => array(
						'Categories' => 'BlogApplication',
					),
				),
			),
		),
		array(
			'class' => 'System.Util.TParameterModule',
			'properties' => array(
				'ParameterFile' => 'Application.Data.Settings',
			),
		),
		'friendly-url' => array(
			'class' => 'System.Web.TUrlMapping',
			'properties' => array(
				'EnableCustomUrl' => true,
			),
			'urls' => array(
				array('properties' => array('ServiceParameter'=>'Posts.ViewPost','pattern'=>'post/{id}/?','parameters.id'=>'\d+')),
			),
		),
	),
	'services' => array(
		'page' => array(
			'class' => 'TPageService',
			'properties' => array(
				'BasePath' => 'Application.Pages',
				'DefaultPage' => 'Posts.ListPost',
			),
			'modules' => array(
				'users' => array(
					'class' => 'Application.Common.BlogUserManager',
				),
				'auth' => array(
					'class' => 'System.Security.TAuthManager',
					'properties' => array(
						'UserManager' => 'users',
						'LoginPage' => 'Posts.ListPost',
					),
				),
				'data' => array(
					'class' => 'Application.Common.BlogDataModule',
				),
			),
			'pages' => array(
				'properties' => array(
					'MasterClass' => 'Application.Layouts.MainLayout',
					'Theme' => 'Basic',
				),
			),
		),
	),
);
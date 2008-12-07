<?php
return array(
	'authorization' => array(
		array(
			'action' => 'deny',
			'pages' => 'EditPost,NewPost,MyPost',
			'users' => '?',
		),
		array(
			'action' => 'allow',
			'pages' => 'NewCategory,EditCategory',
			'users' => 'admin',
		),
		array(
			'action' => 'deny',
			'pages' => 'NewCategory,EditCategory',
			'users' => '*',
		),
	)
);
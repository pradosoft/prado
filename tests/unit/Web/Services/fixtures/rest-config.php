<?php

return [
	'resources' => [
		['pattern' => 'phpcfg-users', 'class' => 'DoStyleResource'],
	],
	'groups' => [
		[
			'prefix' => 'v2/',
			'resources' => [
				['pattern' => 'things', 'class' => 'DoStyleResource'],
			],
		],
	],
];

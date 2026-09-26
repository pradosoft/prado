<?php

use Prado\Test\Unit\Web\Services\DoStyleResource;

return [
	'resources' => [
		['pattern' => 'phpcfg-users', 'class' => DoStyleResource::class],
	],
	'groups' => [
		[
			'prefix' => 'v2/',
			'resources' => [
				['pattern' => 'things', 'class' => DoStyleResource::class],
			],
		],
	],
];

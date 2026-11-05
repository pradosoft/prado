<?php

use Prado\Test\Unit\Web\Services\Rest\UsersFixtureResource;

return [
	'resources' => [
		['class' => UsersFixtureResource::class, 'prefix' => 'included', 'Tag' => 'from-php'],
	],
	'groups' => [
		[
			'prefix' => 'debug',
			'enabled' => 'Debug',
			'routes' => [
				['verbs' => 'GET', 'pattern' => 'dump', 'class' => UsersFixtureResource::class, 'method' => 'index', 'name' => 'dump'],
			],
		],
	],
];

<?php

use Prado\Test\Unit\Web\Services\DoStyleResource;

return [
	'resources' => [
		['pattern' => 'php-users', 'class' => DoStyleResource::class],
		['pattern' => 'php-users/{id}', 'class' => DoStyleResource::class, 'parameters' => ['id' => '\d+']],
	],
];

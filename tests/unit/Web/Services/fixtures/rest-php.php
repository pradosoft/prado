<?php

return [
	'resources' => [
		['pattern' => 'php-users', 'class' => DoStyleResource::class],
		['pattern' => 'php-users/{id}', 'class' => DoStyleResource::class, 'parameters' => ['id' => '\d+']],
	],
];

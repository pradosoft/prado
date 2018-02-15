<?php

$config = PhpCsFixer\Config::create()
	->setRiskyAllowed(true)
	->setRules([
		'elseif' => true,
		'encoding' => true,
	])
	->setFinder(
		PhpCsFixer\Finder::create()
			->exclude('build/')
			->exclude('docs/')
			->exclude('tests/')
			->exclude('vendor/')
			->in(__DIR__)
	)
;

return $config;
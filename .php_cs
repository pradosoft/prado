<?php

$config = PhpCsFixer\Config::create()
	->setRiskyAllowed(true)
	->setRules([
		'align_multiline_comment' => true,
		'array_syntax' => ['syntax' => 'short'],
		'blank_line_after_namespace' => true,
		'blank_line_after_opening_tag' => true,
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
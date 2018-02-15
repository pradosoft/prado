<?php

$config = PhpCsFixer\Config::create()
	->setRiskyAllowed(true)
    ->setIndent("\t")
    ->setLineEnding("\n")
	->setRules([
		'align_multiline_comment' => true,
		'array_syntax' => ['syntax' => 'short'],
		'blank_line_after_namespace' => true,
		'blank_line_after_opening_tag' => true,
		'concat_space' => ['spacing' => 'one'],
		'elseif' => true,
		'encoding' => true,
		'indentation_type' => true,
		'method_argument_space' => true,
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
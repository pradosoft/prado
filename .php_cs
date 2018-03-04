<?php

$config = PhpCsFixer\Config::create()
	->setRiskyAllowed(true)
    ->setIndent("\t")
    ->setLineEnding("\n")
	->setRules([
		'@PSR2' => true,
		'align_multiline_comment' => true,
		'array_syntax' => ['syntax' => 'short'],
		'binary_operator_spaces' => true,
		'blank_line_after_namespace' => true,
		'blank_line_after_opening_tag' => true,
		'concat_space' => ['spacing' => 'one'],
		'is_null' => true,
		'no_alias_functions' => true,
		'no_null_property_initialization' => true,
		'psr4' => true,
		'ternary_operator_spaces' => true,
		'visibility_required' => true,
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
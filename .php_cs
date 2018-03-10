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
		'cast_spaces' => ['space' => 'single'],
		'concat_space' => ['spacing' => 'one'],
		'dir_constant' => true,
		'is_null' => true,
		'modernize_types_casting' => true,
		'no_alias_functions' => true,
		'no_null_property_initialization' => true,
		'no_whitespace_before_comma_in_array' => true,
		'phpdoc_add_missing_param_annotation' => ['only_untyped' => false],
		'phpdoc_order' => true,
		'phpdoc_scalar' => true,
		'phpdoc_types_order' => true,
		'psr4' => true,
		'ternary_operator_spaces' => true,
		'trim_array_spaces' => true,
		'visibility_required' => true,
		'whitespace_after_comma_in_array' => true,
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
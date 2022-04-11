<?php

$finder = PhpCsFixer\Finder::create()
	->exclude('build/')
	->exclude('docs/')
	->exclude('tests/')
	->exclude('vendor/')
	->in(__DIR__);

$config = new PhpCsFixer\Config();
$config
	->setRiskyAllowed(true)
    ->setIndent("\t")
    ->setLineEnding("\n")
	->setRules([
		'@PSR12' => true,
		'align_multiline_comment' => true,
		'array_syntax' => ['syntax' => 'short'],
		'binary_operator_spaces' => true,
		'blank_line_after_namespace' => true,
		'blank_line_after_opening_tag' => true,
		'cast_spaces' => ['space' => 'single'],
		'combine_nested_dirname' => true,
		'concat_space' => ['spacing' => 'one'],
		'dir_constant' => true,
		'is_null' => true,
		'list_syntax' => ['syntax' => 'short'],
		'modernize_types_casting' => true,
		'no_alias_functions' => true,
		'no_null_property_initialization' => true,
		'no_whitespace_before_comma_in_array' => true,
		'phpdoc_add_missing_param_annotation' => ['only_untyped' => false],
		'phpdoc_order' => true,
		'phpdoc_scalar' => true,
		'phpdoc_types_order' => true,
		'psr_autoloading' => true,
		'ternary_operator_spaces' => true,
		'ternary_to_null_coalescing' => true,
		'trim_array_spaces' => true,
		'visibility_required' => true,
		'whitespace_after_comma_in_array' => true,
	])
	->setFinder($finder);

return $config;
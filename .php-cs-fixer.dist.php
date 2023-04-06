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
		'@PHP82Migration' => true,
		'align_multiline_comment' => true,
		'binary_operator_spaces' => true,
		'blank_line_after_namespace' => true,
		'blank_line_after_opening_tag' => true,
		'cast_spaces' => ['space' => 'single'],
		'clean_namespace' => true,
		'combine_nested_dirname' => true,
		'concat_space' => ['spacing' => 'one'],
		'dir_constant' => true,
		'is_null' => true,
		'function_typehint_space' => true,
		'method_chaining_indentation' => true,
		'modernize_types_casting' => true,
		'no_alias_functions' => true,
		'no_blank_lines_after_phpdoc' => true,
		'no_null_property_initialization' => true,
		'phpdoc_add_missing_param_annotation' => ['only_untyped' => false],
		'phpdoc_indent' => true,
		'phpdoc_no_package' => true,
		'phpdoc_order' => true,
		'phpdoc_scalar' => true,
		'phpdoc_types' => true,
		'phpdoc_types_order' => true,
		'psr_autoloading' => true,
		'ternary_operator_spaces' => true,
		'trim_array_spaces' => true,
		'whitespace_after_comma_in_array' => true,
	])
	->setFinder($finder);

return $config;
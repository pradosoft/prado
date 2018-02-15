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
		'is_null' => true,
		'line_ending' => true,
		'lowercase_constants' => true,
		'lowercase_keywords' => true,
		'method_argument_space' => true,
		'no_alias_functions' => true,
		'no_break_comment' => true,
		'no_closing_tag' => true,
		'no_null_property_initialization' => true,
		'no_spaces_after_function_name' => true,
		'no_spaces_inside_parenthesis' => true,
		'no_trailing_whitespace' => true,
		'no_trailing_whitespace_in_comment' => true,
		'psr4' => true,
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
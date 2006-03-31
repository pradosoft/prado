<?php


$language_data = array (
	'LANG_NAME' => 'VARDUMP',
	'COMMENT_SINGLE' => array(),
	'COMMENT_MULTI' => array(),
	'CASE_KEYWORDS' => GESHI_CAPS_NO_CHANGE,
	'QUOTEMARKS' => array('"', "'"),
	'ESCAPE_CHAR' => '\\',
	'KEYWORDS' => array(
		1 => array(
			'boolean', 'integer','double','string','resource','unknown type','array','object'
			),
		2 => array(
			'NULL', 'null', 'true', 'false'
			)
		),
	'SYMBOLS' => array(
		'(', ')', '{', '}', ':', ';','[',']'
		),
	'CASE_SENSITIVE' => array(
		GESHI_COMMENTS => false,
		1 => false,
		2 => false,
		3 => false,
		),
	'STYLES' => array(
		'KEYWORDS' => array(
			1 => 'color: #000000; font-weight: bold;',
			2 => 'color: #993333;'
			),
		'ESCAPE_CHAR' => array(
			0 => 'color: #000099; font-weight: bold;'
			),
		'BRACKETS' => array(
			0 => 'color: #669966;'
			),
		'STRINGS' => array(
			0 => 'color: #ff0000;'
			),
		'NUMBERS' => array(
			0 => 'color: #cc66cc;'
			),
		'METHODS' => array(
			),
		'SYMBOLS' => array(
			0 => 'color: #66cc66;'
			),
		'SCRIPT' => array(
			),
		'REGEXPS' => array(
			0 => 'color: #cc00cc;',
			1 => 'color: #6666ff;',
			2 => 'color: #3333ff;',
			)
		),
	'URLS' => array(
		1 => '',
		2 => ''
		),
	'OOLANG' => false,
	'OBJECT_SPLITTERS' => array(
		),
	'REGEXPS' => array(
		0 => '\#[a-zA-Z0-9\-]+\s+\{',
		1 => '\.[a-zA-Z0-9\-]+\s',
		2 => ':[a-zA-Z0-9\-]+\s'
		),
	'STRICT_MODE_APPLIES' => GESHI_NEVER,
	'SCRIPT_DELIMITERS' => array(
		),
	'HIGHLIGHT_STRICT_BLOCK' => array(
		)
);

?>
<?php

//package base folder in prado alias notation
$folders = [
	'prado' => 'Prado\\Web\\Javascripts\\source\\prado',
	'jquery' => 'Vendor\\bower-asset\\jquery\\dist',
	'jquery-ui' => 'Vendor\\bower-asset\\jquery-ui',
	'bootstrap' => 'Vendor\\bower-asset\\bootstrap\\dist',
	'tinymce' => 'Vendor\\bower-asset\\tinymce',
	'highlightjs' => 'Vendor\\bower-asset\\highlightjs',
	'highlightjs-line-numbers' => 'Vendor\\bower-asset\\highlightjs-line-numbers.js\\dist',
	'clipboard' => 'Vendor\\bower-asset\\clipboard\\dist',
];

//package names and its contents (files relative to the current directory)
$packages = [
	// base prado scripts
	'prado' => [
		'prado/prado.js',
		'prado/controls/controls.js',
	],

	'logger' => [
		'prado/logger/logger.js',
	],

	'validator' => [
		'prado/validator/validation3.js',
	],

	'datepicker' => [
		'prado/datepicker/datepicker.js',
	],

	'colorpicker' => [
		'prado/colorpicker/colorpicker.js',
	],

	'ajax' => [
		'prado/activecontrols/ajax3.js',
		'prado/activecontrols/activecontrols3.js',
	],

	'slider' => [
		'prado/controls/slider.js',
	],

	'keyboard' => [
		'prado/controls/keyboard.js',
	],

	'tabpanel' => [
		'prado/controls/tabpanel.js',
	],

	'activedatepicker' => [
		'prado/activecontrols/activedatepicker.js',
	],

	'activefileupload' => [
		'prado/activefileupload/activefileupload.js',
	],

	'htmlarea' => [
		'prado/controls/htmlarea.js',
	],

	'htmlarea4' => [
		'prado/controls/htmlarea4.js',
	],

	'accordion' => [
		'prado/controls/accordion.js',
	],

	'inlineeditor' => [
		'prado/activecontrols/inlineeditor.js',
	],

	'ratings' => [
		'prado/ratings/ratings.js',
	],

	// jquery
	'jquery' => [
		'jquery/jquery.js',
	],
	'jqueryui' => [
		'jquery-ui/jquery-ui.js',
	],
		
	//bootstrap
	'bootstrap' => [
		'bootstrap/js/bootstrap.js',
	],

	//tinymce
	'tinymce' => [
		'tinymce/tinymce.js',
	],

	//highlightjs
	'highlightjs' => [
		'highlightjs/highlight.pack.js',
		'highlightjs-line-numbers/highlightjs-line-numbers.min.js',
	],

	//clipboard
	'clipboard' => [
		'clipboard/clipboard.js',
	]
];

//package names and their dependencies
$dependencies = [
	'jquery' => ['jquery'],
	'prado' => ['jquery', 'prado'],
	'bootstrap' => ['jquery', 'bootstrap'],
	'validator' => ['jquery', 'prado', 'validator'],
	'tabpanel' => ['jquery', 'prado', 'tabpanel'],
	'ajax' => ['jquery', 'prado', 'ajax'],
	'logger' => ['jquery', 'prado', 'logger'],
	'activefileupload' => ['jquery', 'prado', 'ajax', 'activefileupload'],
	'effects' => ['jquery', 'jqueryui'],
	'datepicker' => ['jquery', 'prado', 'datepicker'],
	'activedatepicker' => ['jquery', 'prado', 'datepicker', 'ajax', 'activedatepicker'],
	'colorpicker' => ['jquery', 'prado', 'colorpicker'],
	'htmlarea' => ['jquery', 'prado', 'htmlarea'],
	'htmlarea4' => ['jquery', 'prado', 'htmlarea4', 'tinymce'],
	'keyboard' => ['jquery', 'prado', 'keyboard'],
	'slider' => ['jquery', 'prado', 'slider'],
	'inlineeditor' => ['jquery', 'prado', 'ajax', 'inlineeditor'],
	'accordion' => ['jquery', 'prado', 'accordion'],
	'ratings' => ['jquery', 'prado', 'ajax', 'ratings'],
	'jqueryui' => ['jquery', 'jqueryui'],
	'texthighlight' => ['jquery', 'prado', 'highlightjs', 'clipboard'],
];

return [$folders, $packages, $dependencies];

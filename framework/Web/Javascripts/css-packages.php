<?php

//package base folder in prado alias notation
$folders = [
	'jquery-ui' => 'Vendor\\bower-asset\\jquery-ui',
	'highlightjs' => 'Vendor\\bower-asset\\highlightjs',
];

//package names and its contents (files relative to the current directory)
$packages = [
	'jquery-ui' => [
		'jquery-ui/themes/base/jquery-ui.css',
	],
	'jquery.ui.accordion' => [
		'jquery-ui/themes/base/accordion.css',
	],
	'jquery.ui.autocomplete' => [
		'jquery-ui/themes/base/autocomplete.css',
	],
	'jquery.ui.button' => [
		'jquery-ui/themes/base/button.css',
	],
	'jquery.ui.core' => [
		'jquery-ui/themes/base/core.css',
	],
	'jquery.ui.datepicker' => [
		'jquery-ui/themes/base/datepicker.css',
	],
	'jquery.ui.dialog' => [
		'jquery-ui/themes/base/dialog.css',
	],
	'jquery.ui.menu' => [
		'jquery-ui/themes/base/menu.css',
	],
	'jquery.ui.progressbar' => [
		'jquery-ui/themes/base/progressbar.css',
	],
	'jquery.ui.resizable' => [
		'jquery-ui/themes/base/resizable.css',
	],
	'jquery.ui.selectable' => [
		'jquery-ui/themes/base/selectable.css',
	],
	'jquery.ui.slider' => [
		'jquery-ui/themes/base/slider.css',
	],
	'jquery.ui.spinner' => [
		'jquery-ui/themes/base/spinner.css',
	],
	'jquery.ui.tabs' => [
		'jquery-ui/themes/base/tabs.css',
	],
	'jquery.ui.theme' => [
		'jquery-ui/themes/base/theme.css',
	],
	'jquery.ui.tooltip' => [
		'jquery-ui/themes/base/tooltip.css',
	],

	//highlightjs
	'highlightjs' => [
		'highlightjs/styles/default.css',
	],
];

//package names and their dependencies
$dependencies = [
		'jquery-ui' => ['jquery-ui'],
		'jquery.ui.accordion' => ['jquery.ui.core', 'jquery.ui.accordion'],
		'jquery.ui.autocomplete' => ['jquery.ui.core', 'jquery.ui.autocomplete'],
		'jquery.ui.button' => ['jquery.ui.core', 'jquery.ui.button'],
		'jquery.ui.core' => ['jquery.ui.core'],
		'jquery.ui.datepicker' => ['jquery.ui.core', 'jquery.ui.datepicker'],
		'jquery.ui.dialog' => ['jquery.ui.core', 'jquery.ui.dialog'],
		'jquery.ui.menu' => ['jquery.ui.core', 'jquery.ui.menu'],
		'jquery.ui.progressbar' => ['jquery.ui.core', 'jquery.ui.progressbar'],
		'jquery.ui.resizable' => ['jquery.ui.core', 'jquery.ui.resizable'],
		'jquery.ui.selectable' => ['jquery.ui.core', 'jquery.ui.selectable'],
		'jquery.ui.slider' => ['jquery.ui.core', 'jquery.ui.slider'],
		'jquery.ui.spinner' => ['jquery.ui.core', 'jquery.ui.spinner'],
		'jquery.ui.tabs' => ['jquery.ui.core', 'jquery.ui.tabs'],
		'jquery.ui.theme' => ['jquery.ui.core', 'jquery.ui.theme'],
		'jquery.ui.tooltip' => ['jquery.ui.core', 'jquery.ui.tooltip'],
];

return [$folders, $packages, $dependencies];

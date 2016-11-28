<?php

//package base folder in prado alias notation
$folders = array(
	'jquery-ui' => 'Vendor.bower-asset.jquery-ui',
	'bootstrap' => 'Vendor.bower-asset.bootstrap.dist',
);

//package names and its contents (files relative to the current directory)
$packages = array(
	'jquery-ui' => array(
		'jquery-ui/themes/base/jquery-ui.css',
	),
	'jquery.ui.accordion' => array(
		'jquery-ui/themes/base/jquery.ui.accordion.css',
	),
	'jquery.ui.autocomplete' => array(
		'jquery-ui/themes/base/jquery.ui.autocomplete.css',
	),
	'jquery.ui.button' => array(
		'jquery-ui/themes/base/jquery.ui.button.css',
	),
	'jquery.ui.core' => array(
		'jquery-ui/themes/base/jquery.ui.core.css',
	),
	'jquery.ui.datepicker' => array(
		'jquery-ui/themes/base/jquery.ui.datepicker.css',
	),
	'jquery.ui.dialog' => array(
		'jquery-ui/themes/base/jquery.ui.dialog.css',
	),
	'jquery.ui.menu' => array(
		'jquery-ui/themes/base/jquery.ui.menu.css',
	),
	'jquery.ui.progressbar' => array(
		'jquery-ui/themes/base/jquery.ui.progressbar.css',
	),
	'jquery.ui.resizable' => array(
		'jquery-ui/themes/base/jquery.ui.resizable.css',
	),
	'jquery.ui.selectable' => array(
		'jquery-ui/themes/base/jquery.ui.selectable.css',
	),
	'jquery.ui.slider' => array(
		'jquery-ui/themes/base/jquery.ui.slider.css',
	),
	'jquery.ui.spinner' => array(
		'jquery-ui/themes/base/jquery.ui.spinner.css',
	),
	'jquery.ui.tabs' => array(
		'jquery-ui/themes/base/jquery.ui.tabs.css',
	),
	'jquery.ui.theme' => array(
		'jquery-ui/themes/base/jquery.ui.theme.css',
	),
	'jquery.ui.tooltip' => array(
		'jquery-ui/themes/base/jquery.ui.tooltip.css',
	),

	// bootstrap
	'bootstrap' => array(
		'bootstrap/css/bootstrap.css',
	),
	'bootstrap-theme' => array(
		'bootstrap/css/bootstrap-theme.css',
	),
);

//package names and their dependencies
$dependencies = array(
		'jquery-ui'				=> array('jquery-ui'),
		'jquery.ui.accordion'	=> array('jquery.ui.core', 'jquery.ui.accordion'),
		'jquery.ui.autocomplete'	=> array('jquery.ui.core', 'jquery.ui.autocomplete'),
		'jquery.ui.button'	=> array('jquery.ui.core', 'jquery.ui.button'),
		'jquery.ui.core'	=> array('jquery.ui.core'),
		'jquery.ui.datepicker'	=> array('jquery.ui.core', 'jquery.ui.datepicker'),
		'jquery.ui.dialog'	=> array('jquery.ui.core', 'jquery.ui.dialog'),
		'jquery.ui.menu'	=> array('jquery.ui.core', 'jquery.ui.menu'),
		'jquery.ui.progressbar'	=> array('jquery.ui.core', 'jquery.ui.progressbar'),
		'jquery.ui.resizable'	=> array('jquery.ui.core', 'jquery.ui.resizable'),
		'jquery.ui.selectable'	=> array('jquery.ui.core', 'jquery.ui.selectable'),
		'jquery.ui.slider'	=> array('jquery.ui.core', 'jquery.ui.slider'),
		'jquery.ui.spinner'	=> array('jquery.ui.core', 'jquery.ui.spinner'),
		'jquery.ui.tabs'	=> array('jquery.ui.core', 'jquery.ui.tabs'),
		'jquery.ui.theme'	=> array('jquery.ui.core', 'jquery.ui.theme'),
		'jquery.ui.tooltip'	=> array('jquery.ui.core', 'jquery.ui.tooltip'),
		'bootstrap'			=> array('bootstrap'),
		'bootstrap-theme'		=> array('bootstrap', 'bootstrap-theme'),
);

return array($folders, $packages, $dependencies);


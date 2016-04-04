<?php

//$Id: packages.php 3319 2013-09-08 20:59:44Z ctrlaltca $

// To make future upgrades easier
if (!defined('JQUERY_DIR')) define ('JQUERY_DIR', 'jquery');
if (!defined('BOOTSTRAP_DIR')) define ('BOOTSTRAP_DIR', 'bootstrap3');

//package names and its contents (files relative to the current directory)
$packages = array(
	'jquery-ui' => array(
		JQUERY_DIR.'/css/base/jquery-ui.css',
	),
	'jquery.ui.accordion' => array(
		JQUERY_DIR.'/css/base/jquery.ui.accordion.css',
	),
	'jquery.ui.autocomplete' => array(
		JQUERY_DIR.'/css/base/jquery.ui.autocomplete.css',
	),
	'jquery.ui.button' => array(
		JQUERY_DIR.'/css/base/jquery.ui.button.css',
	),
	'jquery.ui.core' => array(
		JQUERY_DIR.'/css/base/jquery.ui.core.css',
	),
	'jquery.ui.datepicker' => array(
		JQUERY_DIR.'/css/base/jquery.ui.datepicker.css',
	),
	'jquery.ui.dialog' => array(
		JQUERY_DIR.'/css/base/jquery.ui.dialog.css',
	),
	'jquery.ui.menu' => array(
		JQUERY_DIR.'/css/base/jquery.ui.menu.css',
	),
	'jquery.ui.progressbar' => array(
		JQUERY_DIR.'/css/base/jquery.ui.progressbar.css',
	),
	'jquery.ui.resizable' => array(
		JQUERY_DIR.'/css/base/jquery.ui.resizable.css',
	),
	'jquery.ui.selectable' => array(
		JQUERY_DIR.'/css/base/jquery.ui.selectable.css',
	),
	'jquery.ui.slider' => array(
		JQUERY_DIR.'/css/base/jquery.ui.slider.css',
	),
	'jquery.ui.spinner' => array(
		JQUERY_DIR.'/css/base/jquery.ui.spinner.css',
	),
	'jquery.ui.tabs' => array(
		JQUERY_DIR.'/css/base/jquery.ui.tabs.css',
	),
	'jquery.ui.theme' => array(
		JQUERY_DIR.'/css/base/jquery.ui.theme.css',
	),
	'jquery.ui.tooltip' => array(
		JQUERY_DIR.'/css/base/jquery.ui.tooltip.css',
	),

	// bootstrap
	'bootstrap' => array(
		BOOTSTRAP_DIR.'/css/bootstrap.css',
	),
	'bootstrap-theme' => array(
		BOOTSTRAP_DIR.'/css/bootstrap-theme.css',
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

return array($packages, $dependencies);


<?php

//$Id: packages.php 3319 2013-09-08 20:59:44Z ctrlaltca $

// To make future upgrades easier
if (!defined('PROTOTYPE_DIR')) define ('PROTOTYPE_DIR', 'prototype-1.7');
if (!defined('JQUERY_DIR')) define ('JQUERY_DIR', 'jquery');
if (!defined('BOOTSTRAP_DIR')) define ('BOOTSTRAP_DIR', 'bootstrap3');
if (!defined('SCRIPTACULOUS_DIR')) define ('SCRIPTACULOUS_DIR', 'scriptaculous-1.9.0');

//package names and its contents (files relative to the current directory)
$packages = array(
	// base prado scripts
	'prado' => array(
		'prado/prado.js',
		'prado/controls/controls.js'
	),

	'logger' => array(
		'prado/logger/logger.js',
	),

	'validator' => array(
		'prado/validator/validation3.js'
	),

	'datepicker' => array(
		'prado/datepicker/datepicker.js'
	),

	'colorpicker' => array(
		'prado/colorpicker/colorpicker.js'
	),

	'ajax' => array(
		'prado/activecontrols/ajax3.js',
		'prado/activecontrols/activecontrols3.js',
	),

	'slider'=>array(
		'prado/controls/slider.js'
	),

	'keyboard'=>array(
		'prado/controls/keyboard.js'
	),

	'tabpanel'=>array(
		'prado/controls/tabpanel.js'
	),

	'activedatepicker' => array(
		'prado/activecontrols/activedatepicker.js'
	),

	'activefileupload' => array(
		'prado/activefileupload/activefileupload.js'
	),

	'htmlarea'=>array(
		'prado/controls/htmlarea.js'
	),

	'htmlarea4'=>array(
		'prado/controls/htmlarea4.js'
	),

	'accordion'=>array(
		'prado/controls/accordion.js'
	),

	'inlineeditor' => array(
		'prado/activecontrols/inlineeditor.js'
	),

	'ratings' => array(
		'prado/ratings/ratings.js',
	),

	// jquery
	'jquery' => array(
		JQUERY_DIR.'/jquery.js',
	),
	'jqueryui' => array(
		JQUERY_DIR.'/jquery-ui.js',
		JQUERY_DIR.'/jquery-ui-i18n.min.js',
	),

	// prototype + scriptaculous
	'prototype' => array(
		PROTOTYPE_DIR.'/prototype.js',
		SCRIPTACULOUS_DIR.'/builder.js',
		SCRIPTACULOUS_DIR.'/effects.js'
	),
		
	//bootstrap
	'bootstrap' => array(
		BOOTSTRAP_DIR.'/js/bootstrap.js',
	),

	'dragdrop'=>array(
		SCRIPTACULOUS_DIR.'/dragdrop.js',
		'prado/activecontrols/dragdrop.js'
	),

	'dragdropextra'=>array(
		'prado/activecontrols/dragdropextra.js',
	),

	'autocomplete' => array(
		SCRIPTACULOUS_DIR.'/controls.js',
		'prado/activecontrols/autocomplete.js'
	),
);


//package names and their dependencies
$dependencies = array(
		'jquery'			=> array('jquery'),
		'prado'				=> array('jquery', 'prado'),
		'bootstrap'			=> array('jquery', 'bootstrap'),
		'validator'			=> array('jquery', 'prado', 'validator'),
		'tabpanel'			=> array('jquery', 'prado', 'tabpanel'),
		'ajax'				=> array('jquery', 'prado', 'ajax'),
		'logger'			=> array('jquery', 'prado', 'logger'),
		'activefileupload'	=> array('jquery', 'prado', 'ajax', 'activefileupload'),
		'effects'			=> array('jquery', 'jqueryui'),
		'datepicker'		=> array('jquery', 'prado', 'datepicker'),
		'activedatepicker'	=> array('jquery', 'prado', 'datepicker', 'ajax', 'activedatepicker'),
		'colorpicker'		=> array('jquery', 'prado', 'colorpicker'),
		'htmlarea'			=> array('jquery', 'prado', 'htmlarea'),
		'htmlarea4'			=> array('jquery', 'prado', 'htmlarea4'),
		'keyboard'			=> array('jquery', 'prado', 'keyboard'),
		'slider'			=> array('jquery', 'prado', 'slider'),
		'inlineeditor'		=> array('jquery', 'prado', 'ajax', 'inlineeditor'),
		'accordion'			=> array('jquery', 'prado', 'accordion'),
		'ratings'			=> array('jquery', 'prado', 'ajax', 'ratings'),
		'jqueryui'			=> array('jquery', 'jqueryui'),
		'prototype'			=> array('prototype'),
		'dragdrop'			=> array('prototype', 'jquery', 'prado', 'ajax', 'dragdrop'),
		'dragdropextra'		=> array('prototype', 'jquery', 'prado', 'ajax', 'dragdrop','dragdropextra'),
		'autocomplete'		=> array('prototype', 'jquery', 'prado', 'ajax', 'autocomplete'),
);

return array($packages, $dependencies);


<?php

//$Id: packages.php 3319 2013-09-08 20:59:44Z ctrlaltca $

// To make future upgrades easier
if (!defined('PROTOTYPE_DIR')) define ('PROTOTYPE_DIR', 'prototype-1.7');
if (!defined('JQUERY_DIR')) define ('JQUERY_DIR', 'jquery');
if (!defined('SCRIPTACULOUS_DIR')) define ('SCRIPTACULOUS_DIR', 'scriptaculous-1.9.0');

//package names and its contents (files relative to the current directory)
$packages = array(
	'prototype' => array(
		PROTOTYPE_DIR.'/prototype.js',
		SCRIPTACULOUS_DIR.'/builder.js',
	),
	'prado' => array(
		'prado/prado.js',
		'prado/controls/controls.js'
	),

	'effects' => array(
		SCRIPTACULOUS_DIR.'/effects.js'
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

	'dragdrop'=>array(
		SCRIPTACULOUS_DIR.'/dragdrop.js',
		'prado/activecontrols/dragdrop.js'
	),

	'dragdropextra'=>array(
		'prado/activecontrols/dragdropextra.js',
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

	'accordion'=>array(
		'prado/controls/accordion.js'
	),

	'htmlarea'=>array(
		'prado/controls/htmlarea.js'
	),

	'htmlarea4'=>array(
		'prado/controls/htmlarea4.js'
	),

	'ratings' => array(
		'prado/ratings/ratings.js',
	),

	'inlineeditor' => array(
		'prado/activecontrols/inlineeditor.js'
	),

	// jquery
	'jquery' => array(
		JQUERY_DIR.'/jquery.js',
	),
	'jqueryui' => array(
		JQUERY_DIR.'/jquery-ui.js',
		JQUERY_DIR.'/jquery-ui-i18n.min.js',
	),

);


//package names and their dependencies
$dependencies = array(
		//'prototype'			=> array('prototype'),
		'jquery'			=> array('jquery'),
		'prado'				=> array('jquery', 'prado'),
		'validator'			=> array('jquery', 'prado', 'validator'),
		'tabpanel'			=> array('jquery', 'prado', 'tabpanel'),
		'ajax'				=> array('jquery', 'prado', 'ajax'),
		'logger'			=> array('jquery', 'prado', 'logger'),

		'effects'			=> array('prototype', 'prado', 'effects'),
		'datepicker'		=> array('prototype', 'prado', 'datepicker'),
		'colorpicker'		=> array('prototype', 'prado', 'colorpicker'),
		'dragdrop'			=> array('prototype', 'prado', 'effects', 'ajax', 'dragdrop'),
		'slider'			=> array('prototype', 'prado', 'slider'),
		'keyboard'			=> array('prototype', 'prado', 'keyboard'),
		'activedatepicker'	=> array('prototype', 'prado', 'datepicker', 'ajax', 'activedatepicker'),
		'activefileupload'	=> array('prototype', 'prado', 'effects', 'ajax', 'activefileupload'),
		'dragdropextra'		=> array('prototype', 'prado', 'effects', 'ajax', 'dragdrop','dragdropextra'),
		'accordion'			=> array('prototype', 'prado', 'effects', 'accordion'),
		'htmlarea'			=> array('prototype', 'prado', 'htmlarea'),
		'htmlarea4'			=> array('prototype', 'prado', 'htmlarea4'),
		'ratings'			=> array('prototype', 'prado', 'effects', 'ajax', 'ratings'),
		'inlineeditor'		=> array('prototype', 'prado', 'effects', 'ajax', 'inlineeditor'),
		'jqueryui'			=> array('jquery', 'jqueryui'),
);

return array($packages, $dependencies);


<?php

//$Id: packages.php 3187 2012-07-12 11:21:01Z ctrlaltca $

// To make future upgrades easier
if (!defined('PROTOTYPE_DIR')) define ('PROTOTYPE_DIR', 'prototype-1.7');
if (!defined('SCRIPTACULOUS_DIR')) define ('SCRIPTACULOUS_DIR', 'scriptaculous-1.9.0');

//package names and its contents (files relative to the current directory)
$packages = array(
	'prototype' => array(
		PROTOTYPE_DIR.'/prototype.js',
		SCRIPTACULOUS_DIR.'/builder.js',
	),
	'prado' => array(
		'prado/prado.js',
		'prado/scriptaculous-adapter.js',
		'prado/controls/controls.js',
		SCRIPTACULOUS_DIR.'/effects.js'
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
		SCRIPTACULOUS_DIR.'/controls.js',
		'prado/activecontrols/json2.js',
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

	'ratings' => array(
		'prado/ratings/ratings.js',
	),

	'inlineeditor' => array(
		'prado/activecontrols/inlineeditor.js'
	),

);


//package names and their dependencies
$dependencies = array(
		'prototype'			=> array('prototype'),
		'prado'				=> array('prototype', 'prado'),
		'effects'			=> array('prototype', 'prado', 'effects'),
		'validator'			=> array('prototype', 'prado', 'validator'),
		'logger'			=> array('prototype', 'prado', 'logger'),
		'datepicker'		=> array('prototype', 'prado', 'datepicker'),
		'colorpicker'		=> array('prototype', 'prado', 'colorpicker'),
		'ajax'				=> array('prototype', 'prado', 'effects', 'ajax'),
		'dragdrop'			=> array('prototype', 'prado', 'effects', 'ajax', 'dragdrop'),
		'slider'			=> array('prototype', 'prado', 'slider'),
		'keyboard'			=> array('prototype', 'prado', 'keyboard'),
		'tabpanel'			=> array('prototype', 'prado', 'tabpanel'),
		'activedatepicker'	=> array('prototype', 'prado', 'datepicker', 'ajax', 'activedatepicker'),
		'activefileupload'	=> array('prototype', 'prado', 'effects', 'ajax', 'activefileupload'),
		'dragdropextra'		=> array('prototype', 'prado', 'effects', 'ajax', 'dragdrop','dragdropextra'),
		'accordion'			=> array('prototype', 'prado', 'effects', 'accordion'),
		'htmlarea'			=> array('prototype', 'prado', 'htmlarea'),
		'ratings'			=> array('prototype', 'prado', 'effects', 'ajax', 'ratings'),
		'inlineeditor'		=> array('prototype', 'prado', 'effects', 'ajax', 'inlineeditor'),
);

return array($packages, $dependencies);


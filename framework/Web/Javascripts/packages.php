<?php

//package base folder in prado alias notation
$folders = array(
	'prado' => 'System.Web.Javascripts.source.prado',
	'jquery' => 'Vendor.bower-asset.jquery.dist',
	'jquery-ui' => 'Vendor.bower-asset.jquery-ui',
	'bootstrap' => 'Vendor.bower-asset.bootstrap.dist',
	'protoype' => 'Vendor.bower-asset.prototypejs-bower',
	'scriptaculous' => 'Vendor.bower-asset.scriptaculous-bower',
);

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
		'jquery/jquery.js',
	),
	'jqueryui' => array(
		'jquery-ui/jquery-ui.js',
		'jquery-ui/jquery-ui-i18n.min.js',
	),

	// prototype + scriptaculous
	'prototype' => array(
		'protoype/prototype.js',
		'scriptaculous/builder.js',
		'scriptaculous/effects.js'
	),
		
	//bootstrap
	'bootstrap' => array(
		'bootstrap/js/bootstrap.js',
	),

	'dragdrop'=>array(
		'scriptaculous/dragdrop.js',
		'prado/activecontrols/dragdrop.js'
	),

	'dragdropextra'=>array(
		'prado/activecontrols/dragdropextra.js',
	),

	'autocomplete' => array(
		'scriptaculous/controls.js',
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

return array($folders, $packages, $dependencies);


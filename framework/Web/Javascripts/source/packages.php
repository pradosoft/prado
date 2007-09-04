<?php

//$Id$

//package names and its contents (files relative to the current directory)
$packages = array(
	'prado' => array(
		'prototype/prototype.js',
		'scriptaculous/builder.js',
		'prado/prado.js',
		'prado/scriptaculous-adapter.js',
		'prado/controls/controls.js',
		'prado/ratings/ratings.js',
	),

	'effects' => array(
		'scriptaculous/effects.js'
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
		'scriptaculous/controls.js',
		'prado/activecontrols/json.js',
		'prado/activecontrols/ajax3.js',
		'prado/activecontrols/activecontrols3.js',
		'prado/activecontrols/inlineeditor.js',
		'prado/activeratings/ratings.js'
	),

	'dragdrop'=>array(
		'scriptaculous/dragdrop.js'
	),

	'slider'=>array(
		'scriptaculous/slider.js',
		'prado/controls/slider.js'
	),

	'keyboard'=>array(
		'prado/controls/keyboard.js'
	),

	'tabpanel'=>array(
		'prado/controls/tabpanel.js'
	),
);


//package names and their dependencies
$dependencies = array(
		'prado'			=> array('prado'),
		'effects'		=> array('prado', 'effects'),
		'validator'		=> array('prado', 'validator'),
		'logger'		=> array('prado', 'logger'),
		'datepicker'	=> array('prado', 'datepicker'),
		'colorpicker'	=> array('prado', 'colorpicker'),
		'ajax'			=> array('prado', 'effects', 'ajax'),
		'dragdrop'		=> array('prado', 'effects', 'dragdrop'),
		'slider'		=> array('prado', 'slider'),
		'keyboard'		=> array('prado', 'keyboard'),
		'tabpanel'		=> array('prado', 'tabpanel'),
);

return array($packages, $dependencies);

?>
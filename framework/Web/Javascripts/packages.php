<?php

//package base folder in prado alias notation
$folders = array(
	'prado' => 'Prado\\Web\\Javascripts\\source\\prado',
	'jquery' => 'Vendor\\bower-asset\\jquery\\dist',
	'jquery-ui' => 'Vendor\\bower-asset\\jquery-ui',
	'bootstrap' => 'Vendor\\bower-asset\\bootstrap\\dist',
	'tinymce' => 'Vendor\\bower-asset\\tinymce',
	'highlightjs' => 'Vendor\\bower-asset\\highlightjs',
	'highlightjs-line-numbers' => 'Vendor\\bower-asset\\highlightjs-line-numbers.js\\dist',
	'clipboard' => 'Vendor\\bower-asset\\clipboard\\dist',
);

//package names and its contents (files relative to the current directory)
$packages = array(
	// base prado scripts
	'prado' => array(
		'prado/prado.js',
		'prado/controls/controls.js',
	),

	'logger' => array(
		'prado/logger/logger.js',
	),

	'validator' => array(
		'prado/validator/validation3.js',
	),

	'datepicker' => array(
		'prado/datepicker/datepicker.js',
	),

	'colorpicker' => array(
		'prado/colorpicker/colorpicker.js',
	),

	'ajax' => array(
		'prado/activecontrols/ajax3.js',
		'prado/activecontrols/activecontrols3.js',
	),

	'slider'=>array(
		'prado/controls/slider.js',
	),

	'keyboard'=>array(
		'prado/controls/keyboard.js',
	),

	'tabpanel'=>array(
		'prado/controls/tabpanel.js',
	),

	'activedatepicker' => array(
		'prado/activecontrols/activedatepicker.js',
	),

	'activefileupload' => array(
		'prado/activefileupload/activefileupload.js',
	),

	'htmlarea'=>array(
		'prado/controls/htmlarea.js',
	),

	'htmlarea4'=>array(
		'prado/controls/htmlarea4.js',
	),

	'accordion'=>array(
		'prado/controls/accordion.js',
	),

	'inlineeditor' => array(
		'prado/activecontrols/inlineeditor.js',
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
	),
		
	//bootstrap
	'bootstrap' => array(
		'bootstrap/js/bootstrap.js',
	),

	//tinymce
	'tinymce' => array(
		'tinymce/tinymce.js',
	),

	//highlightjs
	'highlightjs' => array(
		'highlightjs/highlight.pack.js',
		'highlightjs-line-numbers/highlightjs-line-numbers.min.js',
	),

	//clipboard
	'clipboard' => array(
		'clipboard/clipboard.js',
	)
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
	'htmlarea4'			=> array('jquery', 'prado', 'htmlarea4', 'tinymce'),
	'keyboard'			=> array('jquery', 'prado', 'keyboard'),
	'slider'			=> array('jquery', 'prado', 'slider'),
	'inlineeditor'		=> array('jquery', 'prado', 'ajax', 'inlineeditor'),
	'accordion'			=> array('jquery', 'prado', 'accordion'),
	'ratings'			=> array('jquery', 'prado', 'ajax', 'ratings'),
	'jqueryui'			=> array('jquery', 'jqueryui'),
	'texthighlight'		=> array('jquery', 'prado', 'highlightjs', 'clipboard'),
);

return array($folders, $packages, $dependencies);


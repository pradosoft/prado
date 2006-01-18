<?php


/**
 * PradoClientScript class.
 *
 * Resolves Prado client script dependencies. e.g. TPradoClientScript::getScripts("dom");
 *
 * - <b>base</b> basic javascript utilities, e.g. $()
 * - <b>dom</b> DOM and Form functions, e.g. $F(inputID) to retrive form input values.
 * - <b>effects</b> Effects such as fade, shake, move
 * - <b>controls</b> Prado client-side components, e.g. Slider, AJAX components
 * - <b>validator</b> Prado client-side validators.
 * - <b>ajax</b> Prado AJAX library including Prototype's AJAX and JSON.
 *
 * Dependencies for each library are automatically resolved.
 *
 * Namespace: System.Web.UI
 *
 * @author Wei Zhuo<weizhuo[at]gmail[dot]com>
 * @version $Revision: 1.1 $  $Date: 2005/11/06 23:02:33 $
 * @package System.Web.UI
 */
class TClientScript
{
	protected $_manager;
	
	/**
	 * Client-side javascript library dependencies
	 * @var array
	 */
	protected static $_dependencies = array(
		'prado' => array('prado'),
		'effects' => array('prado', 'effects'),
		'ajax' => array('prado', 'effects', 'ajax'),
		'validator' => array('prado', 'validator'),
		'logger' => array('prado', 'logger'),
		'datepicker' => array('prado', 'datepicker'),
		'rico' => array('prado', 'effects', 'ajax', 'rico')
		);

	public function __construct($manager)
	{
		$this->_manager = $manager;
	}

	/**
	 * Resolve dependencies for the given library.
	 * @param array list of libraries to load.
	 * @return array list of libraries including its dependencies.
	 */
	public function getScripts($scripts)
	{
		$files = array();
		if(!is_array($scripts)) $scripts = array($scripts);
		foreach($scripts as $script)
		{
			if(isset(self::$_dependencies[$script]))
				$files = array_merge($files, self::$_dependencies[$script]);
			$files[] = $script;
		}
		$files = array_unique($files);
		return $files;
	}
	
	
	/**
	 * TODO: clean up
	 *
	public function getPostBackEventReference($control,$parameter='',$options=null,$javascriptPrefix=true)
	{
		if(!$options || (!$options->getPerformValidation() && !$options->getTrackFocus() && $options->getClientSubmit() && $options->getActionUrl()==''))
		{
			$this->registerPostBackScript();
			if(($form=$this->_page->getForm())!==null)
				$formID=$form->getClientID();
			else
				throw new TConfigurationException('clientscriptmanager_form_required');
			$postback=self::POSTBACK_FUNC.'(\''.$formID.'\',\''.$control->getUniqueID().'\',\''.THttpUtility::quoteJavaScriptString($parameter).'\')';
			if($options && $options->getAutoPostBack())
				$postback='setTimeout(\''.THttpUtility::quoteJavaScriptString($postback).'\',0)';
			return $javascriptPrefix?'javascript:'.$postback:$postback;
		}
		$opt='';
		$flag=false;
		if($options->getPerformValidation())
		{
			$flag=true;
			$this->registerValidationScript();
			$opt.=',true,';
		}
		else
			$opt.=',false,';
		if($options->getValidationGroup()!=='')
		{
			$flag=true;
			$opt.='"'.$options->getValidationGroup().'",';
		}
		else
			$opt.='\'\',';
		if($options->getActionUrl()!=='')
		{
			$flag=true;
			$this->_page->setCrossPagePostBack(true);
			$opt.='"'.$options->getActionUrl().'",';
		}
		else
			$opt.='null,';
		if($options->getTrackFocus())
		{
			$flag=true;
			$this->registerFocusScript();
			$opt.='true,';
		}
		else
			$opt.='false,';
		if($options->getClientSubmit())
		{
			$flag=true;
			$opt.='true';
		}
		else
			$opt.='false';
		if(!$flag)
			return '';
		$this->registerPostBackScript();
		if(($form=$this->_page->getForm())!==null)
			$formID=$form->getClientID();
		else
			throw new TConfigurationException('clientscriptmanager_form_required');
		$postback=self::POSTBACK_FUNC.'(\''.$formID.'\',\''.$control->getUniqueID().'\',\''.THttpUtility::quoteJavaScriptString($parameter).'\''.$opt.')';
		if($options && $options->getAutoPostBack())
			$postback='setTimeout(\''.THttpUtility::quoteJavaScriptString($postback).'\',0)';
		return $javascriptPrefix?'javascript:'.$postback:$postback;
	}*/	
	
}

?>
<?php

/**
 * TListControlValidator class file
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 */
 
/**
 * Using TBaseValidator class
 */
Prado::using('System.Web.UI.WebControls.TBaseValidator');

/**
 * TListControlValidator class.
 * 
 * TListControlValidator checks the number of selection and their values
 * for a <b>TListControl that allows multiple selection</b>. 
 *
 * You can specify the minimum or maximum (or both) number of selections
 * required using the {@link setMinSelection MinSelection} and
 * {@link setMaxSelection MaxSelection} properties, respectively. In addition,
 * you can specify a comma separated list of required selected values via the
 * {@link setRequiredSelections RequiredSelections} property.
 *
 * Examples
 * - At least two selections
 * <code>
 *	<com:TListBox ID="listbox" SelectionMode="Multiple">
 *		<com:TListItem Text="item1" Value="value1" />
 *		<com:TListItem Text="item2" Value="value2" />
 *		<com:TListItem Text="item3" Value="value3" />
 *	</com:TListBox>
 *
 *	<com:TRequiredListValidator 
 *		ControlToValidate="listbox"
 *		MinSelection="2" 
 *		ErrorMessage="Please select at least 2" />
 * </code>
 * - "value1" must be selected <b>and</b> at least 1 other
 * <code>
 *	<com:TCheckBoxList ID="checkboxes">
 *		<com:TListItem Text="item1" Value="value1" />
 *		<com:TListItem Text="item2" Value="value2" />
 *		<com:TListItem Text="item3" Value="value3" />		
 *	</com:TCheckBoxList>
 *
 *	<com:TRequiredListValidator 
 *		ControlToValidate="checkboxes"
 *		RequiredSelections="value1"
 *		MinSelection="2"
 *		ErrorMessage="Please select 'item1' and at least 1 other" /> 
 * </code>
 *
 * @author Xiang Wei Zhuo <weizhuo[at]gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TListControlValidator extends TBaseValidator
{	
	/**
	 * @return int min number of selections 
	 */
	function getMinSelection()
	{
		return $this->getViewState('MinSelection','');
	}
	
	/**
	 * @param int minimum number of selections.
	 */
	function setMinSelection($value)
	{
		$this->setViewState('MinSelection',$value,'');
	}
	
	/**
	 * @return int max number of selections 
	 */
	function getMaxSelection()
	{
		return $this->getViewState('MaxSelection','');
	}
	
	/**
	 * @param int max number of selections.
	 */	
	function setMaxSelection($value)
	{
		$this->setViewState('MaxSelection',$value,'');
	}

	/**
	 * Get a comma separated list of required selected values.
	 * @return string comma separated list of required values. 
	 */
	function getRequiredSelections()
	{
		return $this->getViewState('RequiredSelections','');
	}
		
	/**
	 * Set the list of required values, using aa comma separated list.
	 * @param string comma separated list of required values. 
	 */
	function setRequiredSelections($value)
	{
		$this->setViewState('RequiredSelections',$value,'');
	}	
	
	/**
	 * This method overrides the parent's implementation.
	 * The validation succeeds if the input component changes its data
	 * from the InitialValue or the input component is not given.
	 * @return boolean whether the validation succeeds
	 */
	public function evaluateIsValid()
	{		
		$control=$this->getValidationTarget();
		
		$exists = true;
		list($count, $values) = $this->getSelection($control);
		$required = $this->getRequiredValues();
		
		//if required, check the values
		if(!empty($required))
		{
			if(count($values) < count($required) ) 
				return false;
			foreach($required as $require)
				$exists = $exists && in_array($require, $values);
		}
		
		$min = $this->getMinSelection();
		$max = $this->getMaxSelection();
		
		if($min !== '' && $max !=- '')
			return $exists && $count >= intval($min) && $count <= intval($max);
		else if($min === '' && $max !== '')
			return $exists && $count <= intval($max);
		else if($min !== '' && $max === '')
			return $exists && $count >= intval($min);		
	}	
	
	/**
	 * @param TListControl control to validate
	 * @return array number of selected values and its values.
	 */
	protected function getSelection($control)
	{
		$count = 0;
		$values = array();

		//get the data
		foreach($control->getItems() as $item)
		{
			if($item->getSelected()) 
			{
				$count++;
				$values[] = $item->getValue();
			}
		}
		return array($count, $values);		
	}
	
	/**
	 * @return array list of required values.
	 */
	protected function getRequiredValues()
	{
		$required = array();
		$string = $this->getRequiredSelections();
		if(!empty($string))
			$required = preg_split('/,\s*/', $string);
		return $required;
	}
	
	/**
	 * Returns an array of javascript validator options.
	 * @return array javascript validator options.
	 */
	protected function getClientScriptOptions()
	{
		$options = parent::getClientScriptOptions();
		$control = $this->getValidationTarget();
		
		if(!$control instanceof TListControl)
		{
			throw new TConfigurationException(
				'tlistcontrolvalidator_invalid_control', 
				$this->getID(),$this->getControlToValidate(), get_class($control));
		}
		
		$min = $this->getMinSelection();
		$max = $this->getMaxSelection();
		if($min !== '')
			$options['Min']= intval($min);
		if($max !== '')
			$options['Max']= intval($max);
		$required = $this->getRequiredSelections();
		if(strlen($required) > 0)
			$options['Required']= $required;
		$options['TotalItems'] = $control->getItemCount();

		return $options;
	}	
}
?>
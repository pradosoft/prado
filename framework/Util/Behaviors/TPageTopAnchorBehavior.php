<?php

/**
 * TPageTopAnchorBehavior class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Behaviors;

use Prado\Util\TBehavior;

/**
 * TPageTopAnchorBehavior adds an <a name='top'> anchor at the top of 
 * every page just before the TForm.
 * @author Brad Anderson <belisoful@icloud.com>
 * @package Prado\Util\Behaviors
 * @since 4.2.0
 */
class TPageTopAnchorBehavior extends TBehavior 
{
	/**
	 * @var string the page theme is set to this parameter key
	 */
	private $_topAnchor = 'top';
	
	/**
	 * This handles the TPage.OnSaveStateComplete event to place the 
	 * '<a name="">' at the last moment and have least interference with
	 * anything else
	 * @return array of events as keys and methods as values
	 */
	public function events()
	{
		return ['OnSaveStateComplete' => 'addFormANameTop'];
	}
	
	/**
	 * This method places an '<a name="">' before the TForm
	 * @param $sender object raising the event
	 * @param $param the parameter of the raised event
	 */
	 public function addFormANameTop($page, $param)
	 {
		$toplink = '<a name="' . $this->_topAnchor . '"></a>';
		if($form=$page->getForm()) {
			$form->getParent()->getControls()->insertBefore($form,$toplink);
		}
	 }
	 
	 /**
	  * @return string the top anchor name, defaults to 'top'.
	  */
	 public function getTopAnchor()
	 {
		 return $this->_topAnchor;
	 }
	 
	 /**
	  * @param $value string the top anchor name.
	  */
	 public function setTopAnchor($value)
	 {
		 $this->_topAnchor = $value;
	 }
}

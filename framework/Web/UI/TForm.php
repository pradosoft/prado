<?php
/**
 * TForm class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $Date: $
 * @package System.Web.UI
 */

/**
 * TForm class
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI
 * @since 3.0
 */
class TForm extends TControl
{
	/**
	 * Registers the form with the page.
	 * @param mixed event parameter
	 */
	public function onInit($param)
	{
		parent::onInit($param);
		$this->getPage()->setForm($this);
	}

	protected function addAttributesToRender($writer)
	{
		$writer->addAttribute('id',$this->getClientID());
		$writer->addAttribute('method',$this->getMethod());
		$writer->addAttribute('action',$this->getRequest()->getRequestURI());
		if(($enctype=$this->getEnctype())!=='')
			$writer->addAttribute('enctype',$enctype);

		$attributes=$this->getAttributes();
		$attributes->remove('action');
		$writer->addAttributes($attributes);

		if(($butt=$this->getDefaultButton())!=='')
		{
			if(($button=$this->findControl($butt))!==null)
				$this->getPage()->getClientScript()->registerDefaultButton($this,$button);
			else
				throw new TInvalidDataValueException('form_defaultbutton_invalid',$butt);
		}
	}

	/**
	 * @internal
	 */
	public function render($writer)
	{
		$this->addAttributesToRender($writer);
		$writer->renderBeginTag('form');
		$page=$this->getPage();
		$page->beginFormRender($writer);
		$this->renderChildren($writer);
		$page->endFormRender($writer);
		$writer->renderEndTag();
	}

	public function getDefaultButton()
	{
		return $this->getViewState('DefaultButton','');
	}

	public function setDefaultButton($value)
	{
		$this->setViewState('DefaultButton',$value,'');
	}

	public function getDefaultFocus()
	{
		return $this->getViewState('DefaultFocus','');
	}

	public function setDefaultFocus($value)
	{
		$this->setViewState('DefaultFocus',$value,'');
	}

	public function getMethod()
	{
		return $this->getViewState('Method','post');
	}

	public function setMethod($value)
	{
		$this->setViewState('Method',TPropertyValue::ensureEnum($value,'post','get'),'post');
	}

	public function getEnctype()
	{
		return $this->getViewState('Enctype','');
	}

	public function setEnctype($value)
	{
		$this->setViewState('Enctype',$value,'');
	}

	public function getName()
	{
		return $this->getUniqueID();
	}
}

?>
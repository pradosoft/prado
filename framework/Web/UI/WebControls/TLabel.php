<?php
/**
 * TLabel class file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 */

/**
 * TLabel class
 *
 * TLabel displays a piece of text on a Web page.
 * Use {@link setText Text} property to set the text to be displayed.
 * TLabel will render the contents enclosed within its component tag
 * if {@link setText Text} is empty.
 * To use TLabel as a form label, associate it with a control by setting the
 * {@link setAssociateControlID AssociateControlID} property.
 * The associated control must be locatable within the label's naming container.
 *
 * Note, {@link setText Text} will NOT be encoded for rendering.
 * Make sure it does not contain dangerous characters that you want to avoid.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TLabel extends TWebControl
{
	/**
	 * @return string tag name of the label, returns 'label' if there is an associated control, 'span' otherwise.
	 */
	protected function getTagName()
	{
		return ($this->getAssociatedControlID()==='')?'span':'label';
	}

	/**
	 * Adds attributes to renderer.
	 * @param THtmlWriter the renderer
	 * @throws TInvalidDataValueException if associated control cannot be found using the ID
	 */
	protected function addAttributesToRender($writer)
	{
		if(($aid=$this->getAssociatedControlID())!=='')
		{
			if($control=$this->findControl($aid))
				$writer->addAttribute('for',$control->getClientID());
			else
				throw new TInvalidDataValueException('label_associatedcontrol_invalid',$aid);
		}
		parent::addAttributesToRender($writer);
	}

	/**
	 * Renders the body content of the label.
	 * @param THtmlWriter the renderer
	 */
	protected function renderContents($writer)
	{
		if(($text=$this->getText())==='')
			parent::renderContents($writer);
		else
			$writer->write($text);
	}

	/**
	 * @return string the text value of the label
	 */
	public function getText()
	{
		return $this->getViewState('Text','');
	}

	/**
	 * @param string the text value of the label
	 */
	public function setText($value)
	{
		$this->setViewState('Text',$value,'');
	}

	/**
	 * @return string the associated control ID
	 */
	public function getAssociatedControlID()
	{
		return $this->getViewState('AssociatedControlID','');
	}

	/**
	 * Sets the ID of the control that the label is associated with.
	 * The control must be locatable via {@link TControl::findControl} using the ID.
	 * @param string the associated control ID
	 */
	public function setAssociatedControlID($value)
	{
		$this->setViewState('AssociatedControlID',$value,'');
	}
}

?>
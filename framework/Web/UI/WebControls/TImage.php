<?php
/**
 * TImage class file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.xisc.com/
 * @copyright Copyright &copy; 2004-2005, Qiang Xue
 * @license http://www.opensource.org/licenses/bsd-license.php BSD License
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 */

/**
 * TImage class
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TImage extends TWebControl
{
	public static $IMAGE_ALIGN=array('NotSet','AbsBottom','AbsMiddle','Baseline','Bottom','Left','Middle','Right','TextTop','Top');
	// todo: TControl::resolveClientUrl()
	/**
	 * @return string tag name of the image
	 */
	protected function getTagName()
	{
		return 'img';
	}

	protected function addAttributesToRender($writer)
	{
		$writer->addAttribute('src',$this->getImageUrl());
		$writer->addAttribute('alt',$this->getAlternateText());
		if(($desc=$this->getDescriptionUrl())!=='')
			$writer->addAttribute('longdesc',$this->resolveClientUrl($desc));
		if(($align=$this->getImageAlign())!=='NotSet')
			$writer->addAttribute('align',strtolower($align));
		parent::addAttributesToRender($writer);
	}

	/**
	 * Renders the body content of the image.
	 * None will be rendered for an image.
	 * @param THtmlTextWriter the writer for rendering
	 */
	protected function renderContents($writer)
	{
	}

	/**
	 * @return string the alternative text displayed in the TImage component when the image is unavailable.
	 */
	public function getAlternateText()
	{
		return $this->getViewState('AlternateText','');
	}

	/**
	 * Sets the alternative text to be displayed in the TImage when the image is unavailable.
	 * @param string the alternative text
	 */
	public function setAlternateText($value)
	{
		$this->setViewState('AlternateText',$value,'');
	}

	/**
	 * @return string the alignment of the image with respective to other elements on the page.
	 */
	public function getImageAlign()
	{
		return $this->getViewState('ImageAlign','');
	}

	/**
	 * Sets the alignment of the image with respective to other elements on the page.
	 * @param string the alignment of the image
	 */
	public function setImageAlign($value)
	{
		$this->setViewState('ImageAlign',TPropertyValue::ensureEnum($value,self::$IMAGE_ALIGN),'NotSet');
	}

	/**
	 * @return string the location of the image file to be displayed
	 */
	public function getImageUrl()
	{
		return $this->getViewState('ImageUrl','');
	}

	/**
	 * Sets the location of the image file to be displayed.
	 * @param string the location of the image file (file path or URL)
	 */
	public function setImageUrl($value)
	{
		$this->setViewState('ImageUrl',$value,'');
	}

	/**
	 * @return string link to long description
	 */
	public function getDescriptionUrl()
	{
		return $this->getViewState('DescriptionUrl','');
	}

	/**
	 * Sets the link to long description
	 * @param string the link to long description
	 */
	public function setDescriptionUrl($value)
	{
		$this->setViewState('DescriptionUrl',$value,'');
	}
}

?>
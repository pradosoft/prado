<?php
/**
 * THyperLink class file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.xisc.com/
 * @copyright Copyright &copy; 2004-2005, Qiang Xue
 * @license http://www.opensource.org/licenses/bsd-license.php BSD License
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 */

/**
 * THyperLink class
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class THyperLink extends TWebControl
{
	// todo: TControl::resolveClientUrl
	/**
	 * @return string tag name of the hyperlink
	 */
	protected function getTagName()
	{
		return 'a';
	}

	protected function addAttributesToRender($writer)
	{
		$isEnabled=$this->getEnabled(true);
		if($this->getEnabled() && !$isEnabled)
			$writer->addAttribute('disabled','disabled');
		parent::addAttributesToRender($writer);
		if(($url=$this->getNavigateUrl())!=='' && $isEnabled)
		{
			// todo
			//$url=$this->resolveClientUrl($url);
			$writer->addAttribute('href',$url);
		}
		if(($target=$this->getTarget())!=='')
			$writer->addAttribute('target',$target);
	}

	/**
	 * Renders the body content of the hyperlink.
	 * @param THtmlWriter the writer for rendering
	 */
	protected function renderContents($writer)
	{
		if(($imageUrl=$this->getImageUrl())==='')
		{
			if($this->getHasControls())
				parent::renderContents($writer);
			else
				$writer->write($this->getText());
		}
		else
		{
			$image=new TImage;
			$image->setImageUrl($this->resolveClientUrl($imageUrl));
			if(($toolTip=$this->getToolTip())!=='')
				$image->setAlternateText($toolTip);
			if(($text=$this->getText())!=='')
				$image->setAlternateText($text);
			$image->renderControl($writer);
		}
	}

	/**
	 * @return string the text caption of the THyperLink
	 */
	public function getText()
	{
		return $this->getViewState('Text','');
	}

	/**
	 * Sets the text caption of the THyperLink.
	 * @param string the text caption to be set
	 */
	public function setText($value)
	{
		if($this->getHasControls())
			$this->getControls()->clear();
		$this->setViewState('Text',$value,'');
	}

	/**
	 * @return string the location of the image file for the THyperLink
	 */
	public function getImageUrl()
	{
		return $this->getViewState('ImageUrl','');
	}

	/**
	 * Sets the location of image file of the THyperLink.
	 * @param string the image file location
	 */
	public function setImageUrl($value)
	{
		$this->setViewState('ImageUrl',$value,'');
	}

	/**
	 * @return string the URL to link to when the THyperLink component is clicked.
	 */
	public function getNavigateUrl()
	{
		return $this->getViewState('NavigateUrl','');
	}

	/**
	 * Sets the URL to link to when the THyperLink component is clicked.
	 * @param string the URL
	 */
	public function setNavigateUrl($value)
	{
		$this->setViewState('NavigateUrl',$value,'');
	}

	/**
	 * @return string the target window or frame to display the Web page content linked to when the THyperLink component is clicked.
	 */
	public function getTarget()
	{
		return $this->getViewState('Target','');
	}

	/**
	 * Sets the target window or frame to display the Web page content linked to when the THyperLink component is clicked.
	 * @param string the target window, valid values include '_blank', '_parent', '_self', '_top' and empty string.
	 */
	public function setTarget($value)
	{
		$this->setViewState('Target',$value,'');
	}
}

?>
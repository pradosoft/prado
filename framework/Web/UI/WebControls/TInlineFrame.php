<?php
/**
 * TInlineFrame class file.
 *
 * @author Jason Ragsdale <jrags@jasrags.net>
 * @author Harry Pottash <hpottash@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 */

/**
 * TInlineFrame class
 *
 * TInlineFrame displays an inline frame (iframe) on a Web page.
 * The location of the frame content is specified by {@link setFrameUrl FrameUrl}.
 * The frame's alignment is specified by {@link setAlign Align}.
 * The {@link setMarginWidth MarginWidth} and {@link setMarginHeight MarginHeight}
 * properties define the number of pixels to use as the left/right margins and
 * top/bottom margins, respectively, within the inline frame.
 * The {@link setScrollBars ScrollBars} property specifies whether scrollbars are
 * provided for the inline frame. And {@link setDescriptionUrl DescriptionUrl}
 * gives the URI of a long description of the frame's contents.
 *
 * Original Prado v2 IFrame Author Information
 * @author Jason Ragsdale <jrags@jasrags.net>
 * @author Harry Pottash <hpottash@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TInlineFrame extends TWebControl
{
	/**
	 * @return string tag name of the iframe.
	 */
	protected function getTagName()
	{
		return 'iframe';
	}

	/**
	 * @return string alignment of the iframe. Defaults to 'NotSet'.
	 */
	public function getAlign()
	{
		return $this->getViewState('Align','NotSet');
	}

	/**
	 * @param string alignment of the iframe. Valid values include
	 * 'NotSet', 'Left', 'Right', 'Top', 'Middle', 'Bottom'.
	 */
	public function setAlign($value)
	{
		$this->setViewState('Align',TPropertyValue::ensureEnum($value,'NotSet','Left','Right','Top','Middle','Bottom'),'NotSet');
	}

	/**
	 * @return string the URL to long description
	 */
	public function getDescriptionUrl()
	{
		return $this->getViewState('DescriptionUrl','');
	}

	/**
	 * @param string the URL to the long description of the image.
	 */
	public function setDescriptionUrl($value)
	{
		$this->setViewState('DescriptionUrl',$value,'');
	}

	/**
	 * @return boolean whether there should be a visual separator between the frames. Defaults to true.
	 */
	public function getShowBorder()
	{
		return $this->getViewState('ShowBorder',true);
	}

	/**
	 * @param boolean whether there should be a visual separator between the frames.
	 */
	public function setShowBorder($value)
	{
		$this->setViewState('ShowBorder',TPropertyValue::ensureBoolean($value),true);
	}

	/**
	 * @return string URL that this iframe will load content from. Defaults to ''.
	 */
	public function getFrameUrl()
	{
		return $this->getViewState('FrameUrl','');
	}

	/**
	 * @param string URL that this iframe will load content from.
	 */
	public function setFrameUrl($value)
	{
		$this->setViewState('FrameUrl',$value,'');
	}

	/**
	 * @return string the visibility and position of scroll bars in an iframe. Defaults to 'Auto'.
	 */
	public function getScrollBars()
	{
		return $this->getViewState('ScrollBars','Auto');
	}

	/**
	 * @param string the visibility and position of scroll bars in an iframe.
	 * Valid values include None, Auto, Both.
	 */
	public function setScrollBars($value)
	{
		$this->setViewState('ScrollBars',TPropertyValue::ensureEnum($value,array('None','Auto','Both')),'Auto');
	}

	/**
	 * @return integer the amount of space, in pixels, that should be left between
	 * the frame's contents and the left and right margins. Defaults to -1, meaning not set.
	 */
	public function getMarginWidth()
	{
		return $this->getViewState('MarginWidth',-1);
	}

	/**
	 * @param integer the amount of space, in pixels, that should be left between
	 * the frame's contents and the left and right margins.
	 */
	public function setMarginWidth($value)
	{
		if(($value=TPropertyValue::ensureInteger($value))<0)
			$value=-1;
		$this->setViewState('MarginWidth',$value,-1);
	}

	/**
	 * @return integer the amount of space, in pixels, that should be left between
	 * the frame's contents and the top and bottom margins. Defaults to -1, meaning not set.
	 */
	public function getMarginHeight()
	{
		return $this->getViewState('MarginHeight',-1);
	}

	/**
	 * @param integer the amount of space, in pixels, that should be left between
	 * the frame's contents and the top and bottom margins.
	 */
	public function setMarginHeight($value)
	{
		if(($value=TPropertyValue::ensureInteger($value))<0)
			$value=-1;
		$this->setViewState('MarginHeight',$value,-1);
	}

	/**
	 * Adds attribute name-value pairs to renderer.
	 * This overrides the parent implementation with additional button specific attributes.
	 * @param THtmlWriter the writer used for the rendering purpose
	 */
	protected function addAttributesToRender($writer)
	{
		if(($id=$this->getID())!=='')
			$writer->addAttribute('name',$this->getUniqueID());

		if(($src=$this->getFrameUrl())!=='')
			$writer->addAttribute('src',$src);

		if(($align=strtolower($this->getAlign()))!=='notset')
			$writer->addAttribute('align',$align);

		$scrollBars=$this->getScrollBars();
		if($scrollBars==='None')
			$writer->addAttribute('scrolling','yes');
		else if($scrollBars==='Both')
			$writer->addAttribute('scrolling','no');

		if (!$this->getShowBorder())
			$writer->addAttribute('frameborder','0');

		if(($longdesc=$this->getDescriptionUrl())!=='')
			$writer->addAttribute('longdesc',$longdesc);

		if(($marginheight=$this->getMarginHeight())!==-1)
			$writer->addAttribute('marginheight',$marginheight);

		if(($marginwidth=$this->getMarginWidth())!==-1)
			$writer->addAttribute('marginwidth',$marginwidth);

		parent::addAttributesToRender($writer);
	}
}
?>
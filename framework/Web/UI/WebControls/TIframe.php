<?php
/**
 * Class TIframe.
 * 
 * Prado V3 Porting Author Information
 * @author Jason Ragsdale <jrags@jasrags.net>
 * @version $Revision: 1.0$  $Date: 3/1/2006$
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package System.Web.UI.WebControls
 */


/**
 * TIframe class
 *
 * TIframe displays displays a Iframe to another Web page.
 *
 * The TIframe component creates an Iframe in your page which will display
 * whatever is specified in the <b>FrameUrl</b> property
 *
 *
 * Properties
 * - <b>FrameUrl</b>, string, kept in viewstate
 *   <br>Gets or sets the URL for the JIframe component.
 * - <b>Scrolling</b>, booliean, default=true, kept in viewstate
 *   <br>Gets or sets the flag determining if scroll bars should be displaed on the nav window
 * - <b>LongDesc</b>, string, kept in viewstate
 *   <br>Gets or sets the long description of the iframe
 * - <b>FrameBorder</b>, booliean, default=true, kept in viewstate
 *   <br>Gets or sets the flag determining if iframe will have a border
 * - <b>MarginHeight</b>, string, default=true, kept in viewstate
 *   <br>Gets or sets the margin height within the iframe
 * - <b>MarginWidth</b>, string, default=true, kept in viewstate
 *   <br>Gets or sets the margin width within the iframe
 *
 * Original Prado v2 IFrame Author Information
 * @author Harry Pottash <hpottash@gmail.com>
 * @version v1.0, last update on 2005/05/21
 */
class TIframe extends TWebControl
{

	/**
	 * @return string tag name of the iframe.
	 */
	protected function getTagName()
	{
		return 'iframe';
	}

	/**
	 * @return string  Defaults to ''.
	 */
	public function getFrameUrl()
	{
		return $this->getViewState('FrameUrl','');
	}
 
	/**
	 * @param string 
	 */
	public function setFrameUrl($value)
	{
		$this->setViewState('FrameUrl',TPropertyValue::ensureString($value),'');
	}
 
	/**
	 * @return boolean  Defaults to true.
	 */
	public function getScrolling()
	{
		return $this->getViewState('Scrolling',true);
	}
 
	/**
	 * @param boolean 
	 */
	public function setScrolling($value)
	{
		$this->setViewState('Scrolling',TPropertyValue::ensureBoolean($value),true);
	}
 
	/**
	 * @return string  Defaults to ''.
	 */
	public function getLongDesc()
	{
		return $this->getViewState('LongDesc','');
	}
 
	/**
	 * @param string 
	 */
	public function setLongDesc($value)
	{
		$this->setViewState('LongDesc',TPropertyValue::ensureString($value),'');
	}
 
	/**
	 * @return boolean  Defaults to true.
	 */
	public function getFrameBorder()
	{
		return $this->getViewState('FrameBorder',true);
	}
 
	/**
	 * @param boolean 
	 */
	public function setFrameBorder($value)
	{
		$this->setViewState('FrameBorder',TPropertyValue::ensureBoolean($value),true);
	}
 
	/**
	 * @return string  Defaults to ''.
	 */
	public function getMarginWidth()
	{
		return $this->getViewState('MarginWidth','');
	}
 
	/**
	 * @param string 
	 */
	public function setMarginWidth($value)
	{
		$this->setViewState('MarginWidth',TPropertyValue::ensureString($value),'');
	}
 
	/**
	 * @return string  Defaults to ''.
	 */
	public function getMarginHeight()
	{
		return $this->getViewState('MarginHeight','');
	}
 
	/**
	 * @param string 
	 */
	public function setMarginHeight($value)
	{
		$this->setViewState('MarginHeight',TPropertyValue::ensureString($value),'');
	}

	/**
	 * Adds attribute name-value pairs to renderer.
	 * This overrides the parent implementation with additional button specific attributes.
	 * @param THtmlWriter the writer used for the rendering purpose
	 */
	protected function addAttributesToRender($writer)
	{	
		if(($uniqueID=$this->getUniqueID())!=='')
			$writer->addAttribute('name',$uniqueID);
		
		$writer->addAttribute('src',$this->getFrameUrl());

		if(!$this->getEnabled())
			$writer->addAttribute('disabled','disabled');
		
		if($this->getScrolling())
			$writer->addAttribute('scrolling','auto');
		else 
			$writer->addAttribute('scrolling','no');
		
		if ($this->getFrameBorder())
			$writer->addAttribute('frameborder','1');
		else 
			$writer->addAttribute('frameborder','0');
			
		if(($longdesc=$this->getLongDesc())!=='')
			$writer->addAttribute('LongDesc',$longdesc);

		if(($marginheight=$this->getMarginHeight())!=='')
			$writer->addAttribute('marginheight',$marginheight);
		
		if(($marginwidth=$this->getMarginWidth())!=='')
			$writer->addAttribute('marginwidth',$marginwidth);
			
		parent::addAttributesToRender($writer);
	}
}
?>
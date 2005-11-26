<?php
/**
 * TPanel class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 */

/**
 * TPanel class
 *
 * TPanel represents a component that acts as a container for other component.
 * It is especially useful when you want to generate components programmatically or hide/show a group of components.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TPanel extends TWebControl
{
	/**
	 * @return string tag name of the panel
	 */
	protected function getTagName()
	{
		return 'div';
	}

	/**
	 * Adds attributes to renderer.
	 * @param THtmlWriter the renderer
	 */
	protected function addAttributesToRender($writer)
	{
		$url=trim($this->getBackImageUrl());
		if($url!=='')
			$this->getStyle
     base.AddAttributesToRender(writer);
      string text1 = this.BackImageUrl;
      if (text1.Trim().Length > 0)
      {
            writer.AddStyleAttribute(HtmlTextWriterStyle.BackgroundImage, "url(" + base.ResolveClientUrl(text1) + ")");
      }
      this.AddScrollingAttribute(this.ScrollBars, writer);
      HorizontalAlign align1 = this.HorizontalAlign;
      if (align1 != HorizontalAlign.NotSet)
      {
            TypeConverter converter1 = TypeDescriptor.GetConverter(typeof(HorizontalAlign));
            writer.AddStyleAttribute(HtmlTextWriterStyle.TextAlign, converter1.ConvertToInvariantString(align1).ToLowerInvariant());
      }
      if (!this.Wrap)
      {
            if (base.EnableLegacyRendering)
            {
                  writer.AddAttribute(HtmlTextWriterAttribute.Nowrap, "nowrap", false);
            }
            else
            {
                  writer.AddStyleAttribute(HtmlTextWriterStyle.WhiteSpace, "nowrap");
            }
      }
      if (this.Direction == ContentDirection.LeftToRight)
      {
            writer.AddAttribute(HtmlTextWriterAttribute.Dir, "ltr");
      }
      else if (this.Direction == ContentDirection.RightToLeft)
      {
            writer.AddAttribute(HtmlTextWriterAttribute.Dir, "rtl");
      }
      if (((!base.DesignMode && (this.Page != null)) && ((this.Page.Request != null) && (this.Page.Request.Browser.EcmaScriptVersion.Major > 0))) && ((this.Page.Request.Browser.W3CDomVersion.Major > 0) && (this.DefaultButton.Length > 0)))
      {
            Control control1 = this.FindControl(this.DefaultButton);
            if (control1 is IButtonControl)
            {
                  this.Page.ClientScript.RegisterDefaultButtonScript(control1, writer, true);
            }
            else
            {
                  object[] objArray1 = new object[1] { this.ID } ;
                  throw new InvalidOperationException(SR.GetString("HtmlForm_OnlyIButtonControlCanBeDefaultButton", objArray1));
            }
      }

	}

	/**
	 * @return boolean whether the content wraps within the panel.
	 */
	public function getWrap()
	{
		return $this->getViewState('Wrap',true);
	}

	/**
	 * Sets the value indicating whether the content wraps within the panel.
	 * @param boolean whether the content wraps within the panel.
	 */
	public function setWrap($value)
	{
		$this->setViewState('Wrap',$value,true);
	}

	/**
	 * @return string the horizontal alignment of the contents within the panel.
	 */
	public function getHorizontalAlign()
	{
		return $this->getViewState('HorizontalAlign','');
	}

	/**
	 * Sets the horizontal alignment of the contents within the panel.
     * Valid values include 'justify', 'left', 'center', 'right' or empty string.
	 * @param string the horizontal alignment
	 */
	public function setHorizontalAlign($value)
	{
		$this->setViewState('HorizontalAlign',$value,'');
	}

	/**
	 * @return string the URL of the background image for the panel component.
	 */
	public function getBackImageUrl()
	{
		return $this->getViewState('BackImageUrl','');
	}

	/**
	 * Sets the URL of the background image for the panel component.
	 * @param string the URL
	 */
	public function setBackImageUrl($value)
	{
		$this->setViewState('BackImageUrl',$value,'');
	}

	/**
	 * This overrides the parent implementation by rendering more TPanel-specific attributes.
	 * @return ArrayObject the attributes to be rendered
	 */
	protected function getAttributesToRender()
	{
		$url=$this->getBackImageUrl();
		if(strlen($url))
			$this->setStyle(array('background-image'=>"url($url)"));
		$attributes=parent::getAttributesToRender();
		$align=$this->getHorizontalAlign();
		if(strlen($align))
			$attributes['align']=$align;
		if(!$this->isWrap())
			$attributes['nowrap']='nowrap';
		return $attributes;
	}
}

?>
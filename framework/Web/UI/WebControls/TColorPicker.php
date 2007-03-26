<?php
/**
 * TColorPicker class file
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id$
 * @package System.Web.UI.WebControls
 */

/**
 * TColorPicker class.
 *
 * Be aware, this control is EXPERIMENTAL and is not stablized yet.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version $Id$
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TColorPicker extends TTextBox
{
	/**
	 * @return boolean whether the color picker should pop up when the button is clicked.
	 */
	public function getShowColorPicker()
	{
		return $this->getViewState('ShowColorPicker',true);
	}

	/**
	 * Sets whether to pop up the color picker when the button is clicked.
	 * @param boolean whether to show the color picker popup
	 */
	public function setShowColorPicker($value)
	{
		$this->setViewState('ShowColorPicker',TPropertyValue::ensureBoolean($value),true);
	}

	/**
	 * @param TColorPickerMode color picker UI mode
	 */
	public function setMode($value)
	{
	   $this->setViewState('Mode', TPropertyValue::ensureEnum($value, 'TColorPickerMode'), TColorPickerMode::Basic);
	}

	/**
	 * @return TColorPickerMode current color picker UI mode. Defaults to TColorPickerMode::Basic.
	 */
	public function getMode()
	{
	   return $this->getViewState('Mode', TColorPickerMode::Basic);
	}

	/**
	 * @param string set the color picker style
	 */
	public function setColorPickerStyle($value)
	{
	   $this->setViewState('ColorStyle', $value, 'default');
	}

	/**
	 * @return string current color picker style
	 */
	public function getColorPickerStyle()
	{
	   return $this->getViewState('ColorStyle', 'default');
	}

	/**
	 * @return string text for the color picker OK button. Default is "OK".
	 */
	public function getOKButtonText()
	{
		return $this->getViewState('OKButtonText', 'OK');
	}

	/**
	 * @param string text for the color picker OK button
	 */
	public function setOKButtonText($value)
	{
		$this->setViewState('OKButtonText', $value, 'OK');
	}

	/**
	 * @return string text for the color picker Cancel button. Default is "Cancel".
	 */
	public function getCancelButtonText()
	{
		return $this->getViewState('CancelButtonText', 'Cancel');
	}

	/**
	 * @param string text for the color picker Cancel button
	 */
	public function setCancelButtonText($value)
	{
		$this->setViewState('CancelButtonText', $value, 'Cancel');
	}

	/**
	 * Get javascript color picker options.
	 * @return array color picker client-side options
	 */
	protected function getColorPickerOptions()
	{
		$options['ID'] = $this->getClientID();
		$options['ClassName'] = $this->getCssClass();
		$options['ShowColorPicker'] = $this->getShowColorPicker();

		if($options['ShowColorPicker'])
		{
			$mode = $this->getMode();

			if($mode == TColorPickerMode::Full) $options['Mode'] = $mode;
			else if($mode == TColorPickerMode::Simple) $options['Palette'] = 'Tiny';

			$options['OKButtonText'] = $this->getOKButtonText();
			$options['CancelButtonText'] = $this->getCancelButtonText();
		}

		return $options;
	}

	/**
	 * Publish the color picker Css asset files.
	 */
	public function onPreRender($param)
	{
		parent::onPreRender($param);
		$this->publishColorPickerStyle();
	}

	/**
	 * Publish the color picker style Css asset file.
	 * @return string Css file url.
	 */
	protected function publishColorPickerStyle()
	{
		$cs = $this->getPage()->getClientScript();
		$style = 'System.Web.Javascripts.prado.colorpicker.'.$this->getColorPickerStyle();
		if(($cssFile=Prado::getPathOfNamespace($style,'.css'))!==null)
		{
			$url = $this->publishFilePath($cssFile);
			if(!$cs->isStyleSheetFileRegistered($style))
				$cs->registerStyleSheetFile($style, $url);
			return $url;
		}
		else
			throw new TConfigurationException('colorpicker_style_invalid',$style);
	}

	/**
	 * Publish the color picker image assets.
	 * @return array list of  image URLs
	 */
	protected function publishColorPickerImageAssets()
	{
		$cs = $this->getPage()->getClientScript();
		$key = "prado:".get_class($this);

		$images = array('button' => '.gif', 'target_black' => '.gif',
						'target_white' => '.gif', 'background' => '.png',
						'slider' => '.gif', 'hue' => '.gif');

		$list = array();

		foreach($images as $filename => $ext)
		{
			$image = 'System.Web.Javascripts.prado.colorpicker.'.$filename;
			if(($file =  Prado::getPathOfNamespace($image, $ext))!==null)
				$list[$filename.$ext] = $this->publishFilePath($file);
			else
				throw new TConfigurationException('colorpicker_image_invalid',$image);
		}
		$imgs['button.gif'] = $list['button.gif'];
		$imgs['background.png'] = $list['background.png'];
		$options = TJavaScript::encode($imgs);
		$code = "Prado.WebUI.TColorPicker.UIImages = {$options};";
		$cs->registerEndScript($key, $code);
		return $list;
	}

	/**
	 * Registers the javascript code to initialize the color picker.
	 * Must use "Event.OnLoad" to initialize the color picker when the
	 * full page is loaded, otherwise IE will throw an error.
	 * @param THtmlWriter writer
	 */
	protected function addAttributesToRender($writer)
	{
		parent::addAttributesToRender($writer);
		$writer->addAttribute('id',$this->getClientID());
		$scripts = $this->getPage()->getClientScript();
		$scripts->registerPradoScript("colorpicker");
		$options = TJavaScript::encode($this->getColorPickerOptions());
		$id = $this->getClientID();
		$code = "Event.OnLoad(function(){ new Prado.WebUI.TColorPicker($options); });";
		$scripts->registerEndScript("prado:$id", $code);
	}

	/**
	 * Renders body content.
	 * This method overrides parent implementation by adding
	 * additional color picker button.
	 * @param THtmlWriter writer
	 */
	public function render($writer)
	{
		parent::render($writer);

		$images = $this->publishColorPickerImageAssets();
		$color = $this->getText();

		$writer->addAttribute('class', 'TColorPicker_button');
		$writer->renderBeginTag('span');

		$writer->addAttribute('id', $this->getClientID().'_button');
		$writer->addAttribute('src', $images['button.gif']);
		if($color !== '')
			$writer->addAttribute('style', "background-color:{$color};");
		$writer->addAttribute('width', '20');
		$writer->addAttribute('height', '20');
		$writer->renderBeginTag('img');
		$writer->renderEndTag();
		$writer->renderEndTag();
	}

}

/**
 * TColorPickerMode class.
 * TColorPickerMode defines the enumerable type for the possible UI mode
 * that a {@link TColorPicker} control can take.
 *
 * The following enumerable values are defined:
 * - Simple
 * - Basic
 * - Full
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id$
 * @package System.Web.UI.WebControls
 * @since 3.0.4
 */
class TColorPickerMode extends TEnumerable
{
	const Simple='Simple';
	const Basic='Basic';
	const Full='Full';
}

?>
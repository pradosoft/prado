<?php
/**
 * TSafeHtml class file
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2013 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id: TSafeHtml.php 3245 2013-01-07 20:23:32Z ctrlaltca $
 * @package System.Web.UI.WebControls
 */

/**
 * TSafeHtml class
 *
 * TSafeHtml is a control that strips down all potentially dangerous
 * HTML content. It is mainly a wrapper of {@link http://pear.php.net/package/SafeHTML SafeHTML}
 * project. According to the SafeHTML project, it tries to safeguard
 * the following situations when the string is to be displayed to end-users,
 * - Opening tag without its closing tag
 * - closing tag without its opening tag
 * - any of these tags: base, basefont, head, html, body, applet, object,
 *   iframe, frame, frameset, script, layer, ilayer, embed, bgsound, link,
 *   meta, style, title, blink, xml, etc.
 * - any of these attributes: on*, data*, dynsrc
 * - javascript:/vbscript:/about: etc. protocols
 * - expression/behavior etc. in styles
 * - any other active content.
 *
 * To use TSafeHtml, simply enclose the content to be secured within
 * the body of TSafeHtml in a template.
 *
 * If the content is encoded in UTF-7, you'll need to enable the  {@link setRepackUTF7 RepackUTF7} property
 * to ensure the contents gets parsed correctly.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version $Id: TSafeHtml.php 3245 2013-01-07 20:23:32Z ctrlaltca $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TSafeHtml extends TControl
{
	/**
	 * Sets whether to parse the contents as UTF-7. This property enables a routine
	 * that repacks the content as UTF-7 before parsing it. Defaults to false.
	 * @param boolean whether to parse the contents as UTF-7
	 */
	public function setRepackUTF7($value)
	{
		$this->setViewState('RepackUTF7',TPropertyValue::ensureBoolean($value),false);
	}

	/**
	 * @return boolean whether to parse the contents as UTF-7. Defaults to false.
	 */
	public function getRepackUTF7()
	{
		return $this->getViewState('RepackUTF7',false);
	}

	/**
	 * Renders body content.
	 * This method overrides parent implementation by removing
	 * malicious javascript code from the body content
	 * @param THtmlWriter writer
	 */
	public function render($writer)
	{
		$htmlWriter = Prado::createComponent($this->GetResponse()->getHtmlWriterType(), new TTextWriter());
		parent::render($htmlWriter);
		$writer->write($this->parseSafeHtml($htmlWriter->flush()));
	}

	/**
	 * Use SafeHTML to remove malicous javascript from the HTML content.
	 * @param string HTML content
	 * @return string safer HTML content
	 */
	protected function parseSafeHtml($text)
	{
		$renderer = Prado::createComponent('System.3rdParty.SafeHtml.TSafeHtmlParser');
		return $renderer->parse($text, $this->getRepackUTF7());
	}
}


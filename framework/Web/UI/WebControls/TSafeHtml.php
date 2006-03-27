<?php
/**
 * TSafeHtml class file
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 */

/**
 * TSafeHtml class
 *
 * TSafeHtml is a control that strips down all potentially dangerous
 * HTML content. It is mainly a wrapper of {@link http://pixel-apes.com/safehtml/ SafeHTML}
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
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TSafeHtml extends TControl
{
	/**
	 * Renders body content.
	 * This method overrides parent implementation by removing
	 * malicious javascript code from the body content
	 * @param THtmlWriter writer
	 */
	public function render($writer)
	{
		$textWriter=new TTextWriter;
		parent::render(new THtmlWriter($textWriter));
		$writer->write($this->parseSafeHtml($textWriter->flush()));
	}

	/**
	 * Use SafeHTML to remove malicous javascript from the HTML content.
	 * @param string HTML content
	 * @return string safer HTML content
	 */
	protected function parseSafeHtml($text)
	{
		$renderer = Prado::createComponent('System.3rdParty.SafeHtml.TSafeHtmlParser');
		return $renderer->parse($text);
	}
}

?>
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
	public function renderContents($writer)
	{
		$textWriter=new TTextWriter;
		parent::renderContents(new THtmlWriter($textWriter));
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
		return $renderer->parse($content);
	}
}

?>
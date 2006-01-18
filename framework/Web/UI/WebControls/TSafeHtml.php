<?php

Prado::using('System.3rdParty.SafeHtml.TSafeHtmlParser');
Prado::using('System.IO.TTextWriter');

/**
 * ${classname}
 *
 * ${description}
 *
 * @author Wei Zhuo<weizhuo[at]gmail[dot]com>
 * @version $Revision: 1.66 $  $Date: ${DATE} ${TIME} $
 * @package ${package}
 */
class TSafeHtml extends TControl
{
	/**
	 * Renders body content.
	 * This method overrides parent implementation by removing
	 * malicious javascript code from the body content
	 * @param THtmlWriter writer
	 */
	protected function renderContents($writer)
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
		$renderer = new TSafeHtmlParser();
		return $renderer->parse($content);
	}
}

?>
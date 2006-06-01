<?php
/**
 * TMarkdown class file
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 */

/**
 * TMarkdown class
 *
 * TMarkdown is a control that produces HTML from code with markdown syntax.
 *
 * Markdown is a text-to-HTML conversion tool for web writers. Markdown allows 
 * you to write using an easy-to-read, easy-to-write plain text format, then 
 * convert it to structurally valid XHTML (or HTML).
 * Further documentation regarding Markdown can be found at
 * http://daringfireball.net/projects/markdown/
 *
 * To use TMarkdown, simply enclose the content to be rendered within
 * the body of TMarkdown in a template.
 *
 * See http://www.pradosoft.com/demos/quickstart/?page=Markdown for
 * details on the Markdown syntax usage.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TMarkdown extends TControl
{
	/**
	 * @var TTextHighlighter
	 */
	private $_highlighter;

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
		$writer->write($this->renderMarkdown($textWriter->flush()));
	}

	/**
	 * Use MarkdownParser to render the HTML content.
	 * @param string markdown content
	 * @return string HTML content
	 */
	protected function renderMarkdown($text)
	{
		$renderer = Prado::createComponent('System.3rdParty.Markdown.MarkdownParser');
		$result = $renderer->parse($text);
		return preg_replace_callback(
				'/<pre><code>\[\s*(\w+)\s*\]\n+((.|\n)*?)\s*<\\/code><\\/pre>/im', 
				array($this, 'highlightCode'), $result);
	}

	/**
	 * @return TTextHighlighter source code highlighter
	 */
	public function getTextHighlighter()
	{
		if(is_null($this->_highlighter))
			$this->_highlighter = new TTextHighlighter;
		return $this->_highlighter;
	}

	
	/**
	 * Highlights source code using TTextHighlighter
	 * @param array matches of code blocks
	 * @return string highlighted code.
	 */
	protected function highlightCode($matches)
	{
		$text = new TTextWriter;
		$writer = new THtmlWriter($text);
		$hi = $this->getTextHighlighter();
		if($hi->getControls()->getCount() > 0)
			$hi->getControls()->removeAt(0);
		$hi->addParsedObject(html_entity_decode($matches[2]));
		$hi->setLanguage($matches[1]);
		$hi->render($writer);
		return $text->flush();
	}

	/**
	 * Registers css style for the highlighted result.
	 * This method overrides parent implementation.
	 * @param THtmlWriter writer
	 */
	public function onPreRender($writer)
	{
		parent::onPreRender($writer);
		$hi = $this->getTextHighlighter();
		$this->getControls()->insertAt(0,$hi);
		$hi->onPreRender($writer);
		$this->getControls()->removeAt(0);
	}
}

?>
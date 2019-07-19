<?php
/**
 * TMarkdown class file
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
 */

namespace Prado\Web\UI\WebControls;

use Parsedown;

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
 * See https://daringfireball.net/projects/markdown/basics for
 * details on the Markdown syntax usage.
 *
 * TMarkdown also performs syntax highlighting for code blocks whose language
 * is recognized by {@link TTextHighlighter}.
 * The language of a code block must be specified in the first line of the block
 * and enclosed within a pair of square brackets (e.g. [php]).
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @package Prado\Web\UI\WebControls
 * @since 3.0.1
 */
class TMarkdown extends TTextHighlighter
{
	/**
	 * Processes a text string.
	 * This method is required by the parent class.
	 * @param string $text text string to be processed
	 * @return string the processed text result
	 */
	public function processText($text)
	{
		$result = Parsedown::instance()->parse($text);
		return preg_replace_callback(
			'/<pre><code class="language-(\w+)">((.|\n)*?)<\\/code><\\/pre>/im',
			[$this, 'highlightCode'],
			$result
		);
	}

	/**
	 * Highlights source code using TTextHighlighter
	 * @param array $matches matches of code blocks
	 * @return string highlighted code.
	 */
	protected function highlightCode($matches)
	{
		$text = html_entity_decode($matches[2], ENT_QUOTES, 'UTF-8');
		$this->setLanguage($matches[1]);
		return parent::processText($text);
	}
}

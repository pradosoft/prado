<?php

/**
 * TMarkdown class file
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Parsedown;

/**
 * TMarkdown class
 *
 * TMarkdown renders its Markdown body, or {@see setText Text}, as HTML in a `div`.
 * {@see https://parsedown.org Parsedown} converts the Markdown; see
 * https://daringfireball.net/projects/markdown/basics for the syntax.
 *
 * A fenced code block renders as `<pre><code class="language-x">`, and the client
 * highlights each block with highlight.js. {@see setSyntaxStyle SyntaxStyle},
 * {@see setTabSize TabSize}, {@see setShowLineNumbers ShowLineNumbers}, and
 * {@see setEnableCopyCode EnableCopyCode} apply to every block. A block without a
 * language has its language detected. {@see setLanguage Language} and
 * {@see setEncodeHtml EncodeHtml} do not apply.
 *
 * ```xml
 * <com:TMarkdown>
 * # Title
 * ~~~php
 * echo 'Hello';
 * ~~~
 * </com:TMarkdown>
 * ```
 *
 * Parsedown renders HTML written in the Markdown as is. Markdown from users is
 * rendered inside a {@see \Prado\Web\UI\WebControls\TSafeHtml}.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @since 3.0.1
 */
class TMarkdown extends TTextHighlighter
{
	/**
	 * @return string the tag of the control, `div`
	 */
	protected function getTagName()
	{
		return 'div';
	}

	/**
	 * @return string the client-side JavaScript class that highlights the code blocks
	 * @since 4.4.0
	 */
	protected function getClientClassName()
	{
		return 'Prado.WebUI.TMarkdown';
	}

	/**
	 * Registers the client script and renders the opening `div` with the ClientID.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 */
	public function renderBeginTag($writer)
	{
		$this->renderClientControlScript($writer);
		$writer->addAttribute('id', $this->getClientID());
		TWebControl::renderBeginTag($writer);
	}

	/**
	 * Renders the closing `div`.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 */
	public function renderEndTag($writer)
	{
		TWebControl::renderEndTag($writer);
	}

	/**
	 * Converts the Markdown text to HTML.
	 * @param string $text the Markdown text
	 * @return string the HTML
	 */
	public function processText($text)
	{
		return Parsedown::instance()->parse($text);
	}

	/**
	 * Highlights source code using TTextHighlighter.
	 * @param array $matches matches of code blocks
	 * @return string highlighted code.
	 * @deprecated 4.4.0 TMarkdown keeps the code blocks of Parsedown, which the client highlights.
	 */
	protected function highlightCode($matches)
	{
		$text = html_entity_decode($matches[2], ENT_QUOTES, 'UTF-8');
		$this->setLanguage($matches[1]);
		return parent::processText($text);
	}
}

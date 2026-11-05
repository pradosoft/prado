<?php

/**
 * TRuby class file
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\TPropertyValue;

/**
 * TRuby class
 * TRuby represents the HTML `<ruby>` element, base text with small annotations
 * above or beside it: the reading of East Asian characters, a pronunciation, or
 * a translation.
 * When {@see setBase() Base} and {@see setAnnotation() Annotation} are set, they
 * render as the base text and its {@see TRubyText}, with {@see TRubyParenthesis}
 * parentheses around the annotation while {@see setShowParentheses()
 * ShowParentheses} is true. The child controls render after them.
 * ```html
 * <com:TRuby Lang="ja" Base="漢字" Annotation="かんじ" />
 * <com:TRuby Lang="ja" Base="漢" Annotation="かん" /><com:TRuby Lang="ja" Base="字" Annotation="じ" />
 * ```
 * The first renders `<ruby>漢字<rp>(</rp><rt>かんじ</rt><rp>)</rp></ruby>`. A word
 * annotated character by character is one TRuby per character.
 * {@see setOpenParenthesis() OpenParenthesis} and
 * {@see setCloseParenthesis() CloseParenthesis} are the parentheses, such as the
 * full-width `（` and `）`. {@see setEncode() Encode} HTML-encodes Base and Annotation.
 * Accessibility: screen readers read the base text, then the annotation; a
 * browser that renders ruby hides the parentheses. Chromium exposes only the
 * first annotation of a `<ruby>`, as its description, so each base text with an
 * annotation is its own TRuby. Text in another language sets `Lang`, so a screen
 * reader reads it with that language's voice.
 * The default tag is `ruby`. It can be overridden at runtime via the
 * {@see setTagName TagName} property (e.g. from a theme or template), which is
 * provided by the {@see THtmlElement} base class.
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TRuby extends THtmlElement
{
	use TEncodeTrait;

	/**
	 * @return string the default tag name
	 */
	public function getDefaultTagName()
	{
		return 'ruby';
	}

	/**
	 * @return string the base text. Defaults to ''.
	 */
	public function getBase()
	{
		return $this->getViewState('Base', '');
	}

	/**
	 * @param string $value the base text.
	 */
	public function setBase($value)
	{
		$this->setViewState('Base', TPropertyValue::ensureString($value), '');
	}

	/**
	 * @return string the annotation of the base text. Defaults to ''.
	 */
	public function getAnnotation()
	{
		return $this->getViewState('Annotation', '');
	}

	/**
	 * @param string $value the annotation of the base text.
	 */
	public function setAnnotation($value)
	{
		$this->setViewState('Annotation', TPropertyValue::ensureString($value), '');
	}

	/**
	 * @return bool whether parentheses render around the Annotation. Defaults to true.
	 */
	public function getShowParentheses()
	{
		return $this->getViewState('ShowParentheses', true);
	}

	/**
	 * @param bool $value whether parentheses render around the Annotation.
	 */
	public function setShowParentheses($value)
	{
		$this->setViewState('ShowParentheses', TPropertyValue::ensureBoolean($value), true);
	}

	/**
	 * @return string the opening parenthesis. Defaults to '('.
	 */
	public function getOpenParenthesis()
	{
		return $this->getViewState('OpenParenthesis', '(');
	}

	/**
	 * @param string $value the opening parenthesis.
	 */
	public function setOpenParenthesis($value)
	{
		$this->setViewState('OpenParenthesis', TPropertyValue::ensureString($value), '(');
	}

	/**
	 * @return string the closing parenthesis. Defaults to ')'.
	 */
	public function getCloseParenthesis()
	{
		return $this->getViewState('CloseParenthesis', ')');
	}

	/**
	 * @param string $value the closing parenthesis.
	 */
	public function setCloseParenthesis($value)
	{
		$this->setViewState('CloseParenthesis', TPropertyValue::ensureString($value), ')');
	}

	/**
	 * Renders {@see getBase() Base} and its {@see getAnnotation() Annotation} when they
	 * are set, then the child controls.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 */
	public function renderContents($writer)
	{
		if (($base = $this->getBase()) !== '') {
			$writer->write($this->encodeText($base));
		}
		if (($annotation = $this->getAnnotation()) !== '') {
			$parentheses = $this->getShowParentheses();
			if ($parentheses) {
				$this->renderParenthesis($writer, $this->getOpenParenthesis());
			}
			$writer->renderBeginTag('rt');
			$writer->write($this->encodeText($annotation));
			$writer->renderEndTag();
			if ($parentheses) {
				$this->renderParenthesis($writer, $this->getCloseParenthesis());
			}
		}
		parent::renderContents($writer);
	}

	/**
	 * Renders one `<rp>` parenthesis.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 * @param string $text the parenthesis.
	 */
	protected function renderParenthesis($writer, $text)
	{
		$writer->renderBeginTag('rp');
		$writer->write($this->encodeText($text));
		$writer->renderEndTag();
	}
}

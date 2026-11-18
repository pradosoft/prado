<?php

/**
 * TEmailPreheader class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Email\UI\WebControls;

use Prado\TPropertyValue;

/**
 * TEmailPreheader class.
 *
 * TEmailPreheader renders the preview text that inbox lists show beside the subject.
 * The text is hidden in the opened email and from assistive technology.  Place it
 * first in the body.  When {@see setPadPreview PadPreview} is true, invisible filler
 * follows the text so clients do not append the start of the body to the preview.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TEmailPreheader extends \Prado\Web\UI\TControl
{
	/** The invisible filler that follows the preview text. */
	public const PREVIEW_PAD = '&#847;&zwnj;&nbsp;';

	/** The number of filler repetitions. */
	public const PREVIEW_PAD_COUNT = 80;

	/**
	 * Renders the hidden preview text.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer.
	 */
	public function render($writer)
	{
		if (($text = $this->getText()) === '') {
			return;
		}
		$writer->addAttribute('style', 'display:none;font-size:1px;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;mso-hide:all;');
		$writer->addAttribute('aria-hidden', 'true');
		$writer->renderBeginTag('div');
		$writer->write(htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8'));
		if ($this->getPadPreview()) {
			$writer->write(str_repeat(self::PREVIEW_PAD, self::PREVIEW_PAD_COUNT));
		}
		$writer->renderEndTag();
	}

	/**
	 * @return string the preview text. Defaults to empty.
	 */
	public function getText()
	{
		return $this->getViewState('Text', '');
	}

	/**
	 * @param string $value the preview text.
	 */
	public function setText($value)
	{
		$this->setViewState('Text', TPropertyValue::ensureString($value), '');
	}

	/**
	 * @return bool whether invisible filler follows the preview text. Defaults to true.
	 */
	public function getPadPreview()
	{
		return $this->getViewState('PadPreview', true);
	}

	/**
	 * @param bool $value whether invisible filler follows the preview text.
	 */
	public function setPadPreview($value)
	{
		$this->setViewState('PadPreview', TPropertyValue::ensureBoolean($value), true);
	}
}

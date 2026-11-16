<?php

/**
 * TEmailButton class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Email\UI\WebControls;

use Prado\TPropertyValue;

/**
 * TEmailButton class.
 *
 * TEmailButton renders a link styled as a button that displays in all mail clients,
 * Outlook included.  The link sits in a one-cell presentation table whose cell carries
 * the background color, and the link itself is an inline block with padding.  It is a
 * {@see TEmailTrackedLink}, so clicks are tracked unless {@see setTracked Tracked} is false.
 *
 * The button is a link: assistive technology announces it with its Text.  Choose
 * {@see setBackColor BackColor} and {@see setForeColor ForeColor} with a contrast
 * ratio of at least 4.5:1.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TEmailButton extends TEmailTrackedLink
{
	/**
	 * Renders the presentation table around the link.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer.
	 */
	public function render($writer)
	{
		$backColor = $this->getBackColor() ?: '#1a73e8';
		$foreColor = $this->getForeColor() ?: '#ffffff';
		$radius = $this->getBorderRadius();
		$style = $this->getStyle();
		$style->setStyleField('display', 'inline-block');
		$style->setStyleField('padding', $this->getPadding());
		$style->setStyleField('background-color', $backColor);
		$style->setStyleField('color', $foreColor);
		$style->setStyleField('text-decoration', 'none');
		$style->setStyleField('border-radius', $radius);
		$writer->addAttribute('role', 'presentation');
		$writer->addAttribute('border', '0');
		$writer->addAttribute('cellspacing', '0');
		$writer->addAttribute('cellpadding', '0');
		$writer->renderBeginTag('table');
		$writer->renderBeginTag('tr');
		$writer->addAttribute('align', 'center');
		$writer->addAttribute('bgcolor', $backColor);
		$writer->addAttribute('style', 'border-radius:' . $radius . ';');
		$writer->renderBeginTag('td');
		parent::render($writer);
		$writer->renderEndTag();
		$writer->renderEndTag();
		$writer->renderEndTag();
	}

	/**
	 * @return string the CSS padding of the button. Defaults to "12px 24px".
	 */
	public function getPadding()
	{
		return $this->getViewState('Padding', '12px 24px');
	}

	/**
	 * @param string $value the CSS padding of the button.
	 */
	public function setPadding($value)
	{
		$this->setViewState('Padding', TPropertyValue::ensureString($value), '12px 24px');
	}

	/**
	 * @return string the CSS border radius of the button. Defaults to "4px".
	 */
	public function getBorderRadius()
	{
		return $this->getViewState('BorderRadius', '4px');
	}

	/**
	 * @param string $value the CSS border radius of the button.
	 */
	public function setBorderRadius($value)
	{
		$this->setViewState('BorderRadius', TPropertyValue::ensureString($value), '4px');
	}
}

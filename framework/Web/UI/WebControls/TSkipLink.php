<?php

/**
 * TSkipLink class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\Prado;
use Prado\TPropertyValue;
use Prado\Web\THttpUtility;

/**
 * TSkipLink class
 *
 * TSkipLink renders a link that skips the repeated blocks before the main content
 * of a page (WCAG 2.4.1 Bypass Blocks). The bundled stylesheet hides the link
 * until it receives keyboard focus. Place it first in the `<body>`, before the
 * header and navigation.
 *
 * {@see setTargetID TargetID} names the target. A control found by
 * {@see \Prado\Web\UI\TControl::findControl} links to its ClientID, such as a
 * {@see TMain}; any other value is an HTML element ID. Following the link moves
 * the sequential focus starting point to the target.
 * ```xml
 * <com:TSkipLink TargetID="Content" />
 * <com:THeader>...</com:THeader>
 * <com:TMain ID="Content">...</com:TMain>
 * ```
 *
 * The link text defaults to the localized "Skip to main content". Text,
 * ImageUrl, or body content replace it.
 *
 * The `skip-link` class is always rendered before {@see setCssClass CssClass}.
 * {@see setCssUrl CssUrl} selects the stylesheet that styles it.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TSkipLink extends THyperLink
{
	/**
	 * The class rendered on every skip link and styled by the bundled stylesheet.
	 */
	public const CSS_CLASS = 'skip-link';

	/**
	 * @return string the ID of the target control or HTML element. Defaults to ''.
	 */
	public function getTargetID()
	{
		return $this->getViewState('TargetID', '');
	}

	/**
	 * Sets the ID of the target control or HTML element.
	 * @param string $value the control ID or HTML element ID
	 */
	public function setTargetID($value)
	{
		$this->setViewState('TargetID', trim(TPropertyValue::ensureString($value)), '');
	}

	/**
	 * @return string the client ID of the target: the ClientID of the control found
	 *   by {@see getTargetID TargetID}, or TargetID itself.
	 */
	public function getTargetClientID()
	{
		$targetID = $this->getTargetID();
		if ($targetID === '') {
			return '';
		}
		$control = $this->findControl($targetID);
		return $control ? $control->getClientID() : $targetID;
	}

	/**
	 * @return string the NavigateUrl when set, otherwise the fragment of
	 *   {@see getTargetClientID TargetClientID}, or '' without a target.
	 */
	public function getNavigateUrl()
	{
		if (($url = parent::getNavigateUrl()) !== '') {
			return $url;
		}
		$targetID = $this->getTargetClientID();
		return $targetID === '' ? '' : '#' . $targetID;
	}

	/**
	 * @return string the link text used when Text, ImageUrl, and body content are empty
	 */
	public function getDefaultText()
	{
		return Prado::localize('Skip to main content');
	}

	/**
	 * @return string URL of the stylesheet. Defaults to 'default', the bundled
	 *   stylesheet. An empty string registers no stylesheet.
	 */
	public function getCssUrl()
	{
		return $this->getViewState('CssUrl', 'default');
	}

	/**
	 * Sets the URL of the stylesheet. A replacement stylesheet styles the
	 * `skip-link` class itself.
	 * @param string $value stylesheet URL, 'default' for the bundled stylesheet,
	 *   or empty to register no stylesheet
	 */
	public function setCssUrl($value)
	{
		$this->setViewState('CssUrl', TPropertyValue::ensureString($value), 'default');
	}

	/**
	 * Registers the stylesheet.
	 * @param mixed $param event parameter
	 */
	public function onPreRender($param)
	{
		parent::onPreRender($param);
		$this->registerStyleSheet();
	}

	/**
	 * Registers the stylesheet of {@see getCssUrl CssUrl}. 'default' publishes the
	 * bundled stylesheet; an empty string registers nothing.
	 */
	protected function registerStyleSheet()
	{
		if (($url = $this->getCssUrl()) === '') {
			return;
		}
		if ($url === 'default') {
			$url = $this->publishAsset('assets' . DIRECTORY_SEPARATOR . 'skiplink.css', self::class);
		}
		$this->getPage()->getClientScript()->registerStyleSheetFile($url, $url);
	}

	/**
	 * Adds the hyperlink attributes and the `skip-link` class before
	 * {@see getCssClass CssClass}.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 */
	protected function addAttributesToRender($writer)
	{
		parent::addAttributesToRender($writer);
		$classes = array_filter(
			explode(' ', $this->getCssClass()),
			fn ($class) => $class !== '' && $class !== self::CSS_CLASS
		);
		$writer->addAttribute('class', implode(' ', [self::CSS_CLASS, ...$classes]));
	}

	/**
	 * Renders the {@see getDefaultText DefaultText} when Text, ImageUrl, and body
	 * content are empty, otherwise the hyperlink contents.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer for rendering
	 */
	public function renderContents($writer)
	{
		if ($this->getText() === '' && $this->getImageUrl() === '' && !$this->getHasControls()) {
			$writer->write(THttpUtility::htmlEncode($this->getDefaultText()));
		} else {
			parent::renderContents($writer);
		}
	}
}

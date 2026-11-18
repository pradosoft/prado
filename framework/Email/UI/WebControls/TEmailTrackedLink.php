<?php

/**
 * TEmailTrackedLink class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Email\UI\WebControls;

use Prado\TPropertyValue;

/**
 * TEmailTrackedLink class.
 *
 * TEmailTrackedLink renders a link whose clicks are counted.  Its href goes to the
 * tracking service, which counts the click and redirects to the
 * {@see setNavigateUrl NavigateUrl}.
 *
 * <code>
 * <com:Prado\Email\UI\WebControls\TEmailTrackedLink NavigateUrl="https://www.example.com/sale?u={{ID}}" Text="See the sale" />
 * </code>
 *
 * The link is rendered with its own NavigateUrl, untracked, when
 * {@see setTracked Tracked} is false, the email module has no tracking, or the
 * URL is not http or https.  A NavigateUrl may hold placeholders, which are URL
 * encoded when replaced.
 *
 * A link without text or content shows its NavigateUrl, so it always has a name
 * that a screen reader can read.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TEmailTrackedLink extends \Prado\Web\UI\WebControls\THyperLink
{
	use TEmailControlTrait;

	/**
	 * Registers the NavigateUrl as a tracked link of the email.
	 * @param mixed $param the event parameter.
	 */
	public function onPreRender($param)
	{
		parent::onPreRender($param);
		$this->registerLink();
	}

	/**
	 * Registers the NavigateUrl with the email when the link is tracked.  A URL
	 * registered twice keeps its number.
	 * @return ?int the link number, null for an untracked link.
	 */
	protected function registerLink(): ?int
	{
		$url = $this->getNavigateUrl();
		if (!$this->getTracked() || !preg_match('/^https?:\/\//i', $url) || !$this->getHasTracking()) {
			return null;
		}
		return $this->getEmail()->addTrackedLink($url);
	}

	/**
	 * Adds the attributes of the link; a tracked link gets the URL of the tracking service as its href.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer.
	 */
	protected function addAttributesToRender($writer)
	{
		parent::addAttributesToRender($writer);
		if (($link = $this->registerLink()) !== null && $this->getEnabled(true)) {
			$writer->addAttribute('href', $this->getEmailTrackingUrl(['click' => $link]));
		}
	}

	/**
	 * Renders the content of the link; a link without text or content shows its NavigateUrl.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer.
	 */
	public function renderContents($writer)
	{
		if ($this->getText() === '' && $this->getImageUrl() === '' && !$this->getHasControls()) {
			$writer->write(htmlspecialchars($this->getNavigateUrl(), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8'));
			return;
		}
		parent::renderContents($writer);
	}

	/**
	 * @return bool whether clicks of the link are counted. Defaults to true.
	 */
	public function getTracked()
	{
		return $this->getViewState('Tracked', true);
	}

	/**
	 * @param bool|string $value whether clicks of the link are counted.
	 */
	public function setTracked($value)
	{
		$this->setViewState('Tracked', TPropertyValue::ensureBoolean($value), true);
	}
}

<?php
/**
 * TActiveImageButton class file.
 *
 * @author Wei Zhuo <weizhuo[at]gamil[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\ActiveControls
 */

namespace Prado\Web\UI\ActiveControls;

use Prado\Web\UI\WebControls\TImageButton;

/**
 * TActiveImageButton class.
 *
 * TActiveImageButton is the active control counter part to TImageButton.
 * When a TActiveImageButton is clicked, rather than a normal post back request a
 * callback request is initiated.
 *
 * The {@link onCallback OnCallback} event is raised during a callback request
 * and it is raise <b>after</b> the {@link onClick OnClick} event.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @package Prado\Web\UI\ActiveControls
 * @since 3.1
 * @method TActiveControlAdapter getAdapter()
 */
class TActiveImageButton extends TImageButton implements IActiveControl, ICallbackEventHandler
{
	/**
	 * Creates a new callback control, sets the adapter to
	 * TActiveControlAdapter. If you override this class, be sure to set the
	 * adapter appropriately by, for example, by calling this constructor.
	 */
	public function __construct()
	{
		parent::__construct();
		$this->setAdapter(new TActiveControlAdapter($this));
	}

	/**
	 * @return TBaseActiveControl basic active control options.
	 */
	public function getActiveControl()
	{
		return $this->getAdapter()->getBaseActiveControl();
	}

	/**
	 * @return TCallbackClientSide client side request options.
	 */
	public function getClientSide()
	{
		return $this->getActiveControl()->getClientSide();
	}

	/**
	 * Sets the alternative text to be displayed in the TImage when the image is unavailable.
	 * @param string $value the alternative text
	 */
	public function setAlternateText($value)
	{
		if (parent::getAlternateText() === $value) {
			return;
		}

		parent::setAlternateText($value);
		if ($this->getActiveControl()->canUpdateClientSide()) {
			$this->getPage()->getCallbackClient()->setAttribute($this, 'alt', $value);
		}
	}

	/**
	 * Sets the alignment of the image with respective to other elements on the page.
	 * Possible values include: absbottom, absmiddle, baseline, bottom, left,
	 * middle, right, texttop, and top. If an empty string is passed in,
	 * imagealign attribute will not be rendered.
	 * @param string $value the alignment of the image
	 * @deprecated use the Style property to set the float and/or vertical-align CSS properties instead
	 */
	public function setImageAlign($value)
	{
		if (parent::getImageAlign() === $value) {
			return;
		}

		parent::setImageAlign($value);
		if ($this->getActiveControl()->canUpdateClientSide()) {
			$this->getPage()->getCallbackClient()->setAttribute($this, 'align', $value);
		}
	}

	/**
	 * @param string $value the URL of the image file
	 */
	public function setImageUrl($value)
	{
		if (parent::getImageUrl() === $value) {
			return;
		}

		parent::setImageUrl($value);
		if ($this->getActiveControl()->canUpdateClientSide()) {
			$this->getPage()->getCallbackClient()->setAttribute($this, 'src', $value);
		}
	}

	/**
	 * @param string $value the URL to the long description of the image.
	 * @deprecated use a WAI-ARIA alternative such as aria-describedby or aria-details instead.
	 */
	public function setDescriptionUrl($value)
	{
		if (parent::getDescriptionUrl() === $value) {
			return;
		}

		parent::setDescriptionUrl($value);
		if ($this->getActiveControl()->canUpdateClientSide()) {
			$this->getPage()->getCallbackClient()->setAttribute($this, 'longdesc', $value);
		}
	}

	/**
	 * Raises the callback event. This method is required by
	 * {@link ICallbackEventHandler ICallbackEventHandler} interface. If
	 * {@link getCausesValidation CausesValidation} is true, it will invoke the page's
	 * {@link TPage::validate} method first. It will raise
	 * {@link onClick OnClick} event first and then the {@link onCallback OnCallback} event.
	 * This method is mainly used by framework and control developers.
	 * @param TCallbackEventParameter $param the event parameter
	 */
	public function raiseCallbackEvent($param)
	{
		$this->raisePostBackEvent($param);
		$this->onCallback($param);
	}

	/**
	 * This method is invoked when a callback is requested. The method raises
	 * 'OnCallback' event to fire up the event handlers. If you override this
	 * method, be sure to call the parent implementation so that the event
	 * handler can be invoked.
	 * @param TCallbackEventParameter $param event parameter to be passed to the event handlers
	 */
	public function onCallback($param)
	{
		$this->raiseEvent('OnCallback', $this, $param);
	}

	/**
	 * Override parent implementation, no javascript is rendered here instead
	 * the javascript required for active control is registered in {@link addAttributesToRender}.
	 * @param mixed $writer
	 */
	protected function renderClientControlScript($writer)
	{
	}

	/**
	 * Ensure that the ID attribute is rendered and registers the javascript code
	 * for initializing the active control.
	 * @param mixed $writer
	 */
	protected function addAttributesToRender($writer)
	{
		parent::addAttributesToRender($writer);
		$writer->addAttribute('id', $this->getClientID());
		$this->getActiveControl()->registerCallbackClientScript(
			$this->getClientClassName(),
			$this->getPostBackOptions()
		);
	}

	/**
	 * @return string corresponding javascript class name for this TActiveLinkButton.
	 */
	protected function getClientClassName()
	{
		return 'Prado.WebUI.TActiveImageButton';
	}
}

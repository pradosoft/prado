<?php
/**
 * TActiveImage class file.
 *
 * @author Wei Zhuo <weizhuo[at]gamil[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\ActiveControls
 */

namespace Prado\Web\UI\ActiveControls;

use Prado\Web\UI\WebControls\TImage;

/**
 * TActiveImage class.
 *
 * TActiveImage allows the {@link setAlternateText AlternateText},
 * {@link setImageUrl ImageUrl}, and {@link setDescriptionUrl DescriptionUrl}
 * to be updated during a callback request.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @package Prado\Web\UI\ActiveControls
 * @since 3.1
 */
class TActiveImage extends TImage implements IActiveControl
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
}

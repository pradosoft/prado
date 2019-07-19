<?php
/**
 * TRatingList class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
 */

namespace Prado\Web\UI\WebControls;

use Prado\TPropertyValue;
use Prado\Exceptions\TInvalidDataValueException;

/**
 * TRatingList class.
 *
 * This class is EXPERIMENTAL.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @author Bradley Booms <bradley[dot]booms[at]gmail[dot]com>
 * @package Prado\Web\UI\WebControls
 * @since 3.0
 */
class TRatingList extends TRadioButtonList
{
	/**
	 * Script path relative to the TClientScriptManager::SCRIPT_PATH
	 */
	const SCRIPT_PATH = 'ratings';

	/**
	 * @var array list of published rating images.
	 */
	private $_ratingImages = [];

	/**
	 * Sets the default repeat direction to horizontal.
	 */
	public function __construct()
	{
		parent::__construct();
		$this->setRepeatDirection(TRepeatDirection::Horizontal);
	}

	/**
	 * @return bool whether the items in the column can be edited. Defaults to false.
	 */
	public function getReadOnly()
	{
		return $this->getViewState('ReadOnly', false);
	}

	/**
	 * @param bool $value whether the items in the column can be edited
	 */
	public function setReadOnly($value)
	{
		$this->setViewState('ReadOnly', TPropertyValue::ensureBoolean($value), false);
	}

	/**
	 * Wrapper for {@link setReadOnly ReadOnly} property.
	 * @return bool whether the rating list can be edited. Defaults to true.
	 */
	public function getAllowInput()
	{
		return !$this->getReadOnly();
	}

	/**
	 * Wrapper for {@link setReadOnly ReadOnly} property.
	 * @param bool $value whether the rating list can be edited
	 */
	public function setAllowInput($value)
	{
		$this->setReadOnly(!TPropertyValue::ensureBoolean($value));
	}

	/**
	 * Wrapper for {@link setReadOnly ReadOnly} property.
	 * @param bool $value whether the rating list can be edited
	 */
	public function setEnabled($value)
	{
		$this->setReadOnly(!TPropertyValue::ensureBoolean($value));
	}

	/**
	 * The repeat layout must be Table.
	 * @param string $value repeat layout type
	 * @throws TInvaliddataValueException when repeat layout is not Table.
	 */
	public function setRepeatLayout($value)
	{
		if ($value !== TRepeatLayout::Table) {
			throw new TInvalidDataValueException('ratinglist_table_layout_only');
		} else {
			parent::setRepeatLayout($value);
		}
	}

	/**
	 * @return float rating value.
	 */
	public function getRating()
	{
		$rating = $this->getViewState('Rating', null);
		if ($rating === null) {
			return $this->getSelectedIndex() + 1;
		} else {
			return $rating;
		}
	}

	/**
	 * @param float $value rating value, also sets the selected Index
	 */
	public function setRating($value)
	{
		$value = TPropertyValue::ensureFloat($value);
		$this->setViewState('Rating', $value, null);
		$index = $this->getRatingIndex($value);
		parent::setSelectedIndex($index);
	}

	public function setSelectedIndex($value)
	{
		$this->setRating($value + 1);
		parent::setSelectedIndex($value);
	}

	/**
	 * @param float $rating rating value
	 * @return int rating as integer
	 */
	protected function getRatingIndex($rating)
	{
		$interval = $this->getHalfRatingInterval();
		$base = (int) $rating - 1;
		$remainder = $rating - $base - 1;
		return $remainder > $interval[1] ? $base + 1 : $base;
	}

	/**
	 * @param int $param change the rating selection index
	 */
	public function onSelectedIndexChanged($param)
	{
		$value = $this->getRating();
		$value = TPropertyValue::ensureInteger($value);
		$this->setRating($value);
		parent::onSelectedIndexChanged($param);
	}

	/**
	 * @return string control or html element ID for displaying a caption.
	 */
	public function getCaptionID()
	{
		return $this->getViewState('CaptionID', '');
	}

	/**
	 * @param string $value control or html element ID for displaying a caption.
	 */
	public function setCaptionID($value)
	{
		$this->setViewState('CaptionID', $value, '');
	}

	protected function getCaptionControl()
	{
		if (($id = $this->getCaptionID()) !== '') {
			if ($control = $this->getPage()->findControl($id)) {
				return $control;
			}
			if ($control = $this->getNamingContainer()->findControl($id)) {
				return $control;
			}
		}
		throw new TInvalidDataValueException(
			'ratinglist_invalid_caption_id',
			$id,
			$this->getID()
		);
	}

	/**
	 * @return string caption text. Default is "Rate It:".
	 */
	public function getCaption()
	{
		return $this->getCaptionControl()->getText();
	}

	/**
	 * @param mixed $value
	 * @return TRatingListStyle current rating style
	 */
	public function setCaption($value)
	{
		$this->getCaptionControl()->setText($value);
	}

	/**
	 * @param string $value set the rating style, default is "default"
	 */
	public function setRatingStyle($value)
	{
		$this->setViewState('RatingStyle', $value, 'default');
	}

	/**
	 * @return TRatingListStyle current rating style
	 */
	public function getRatingStyle()
	{
		return $this->getViewState('RatingStyle', 'default');
	}

	/**
	 * @return string rating style css class name.
	 */
	protected function getRatingStyleCssClass()
	{
		return 'TRatingList_' . $this->getRatingStyle();
	}

	/**
	 * Sets the interval such that those rating values within the interval
	 * will be considered as a half star rating.
	 * @param array $value rating display half value interval, default is array(0.3, 0.7);
	 */
	public function setHalfRatingInterval($value)
	{
		$this->setViewState(
			'HalfRating',
			TPropertyValue::ensureArray($value),
			[0.3, 0.7]
		);
	}

	/**
	 * @return array rating display half value interval, default is array(0.3, 0.7);
	 */
	public function getHalfRatingInterval()
	{
		return $this->getViewState('HalfRating', [0.3, 0.7]);
	}

	/**
	 * @return array list of post back options.
	 */
	protected function getPostBackOptions()
	{
		$options = parent::getPostBackOptions();
		$options['AutoPostBack'] = $this->getAutoPostBack();
		$options['ReadOnly'] = $this->getReadOnly();
		$options['Style'] = $this->getRatingStyleCssClass();
		$options['CaptionID'] = $this->getCaptionControlID();
		$options['SelectedIndex'] = $this->getSelectedIndex();
		$options['Rating'] = $this->getRating();
		$options['HalfRating'] = $this->getHalfRatingInterval();
		return $options;
	}

	/**
	 * @return string find the client ID of the caption control.
	 */
	protected function getCaptionControlID()
	{
		if (($id = $this->getCaptionID()) !== '') {
			if ($control = $this->getParent()->findControl($id)) {
				if ($control->getVisible(true)) {
					return $control->getClientID();
				}
			} else {
				return $id;
			}
		}
		return '';
	}

	/**
	 * Publish the the rating style css file and rating image files.
	 * @param mixed $param
	 */
	public function onPreRender($param)
	{
		parent::onPreRender($param);
		$this->publishStyle($this->getRatingStyle());
		$this->_ratingImages = $this->publishImages($this->getRatingStyle());
		$this->registerClientScript();
	}

	/**
	 * @param string $style rating style name
	 * @return string URL of the css style file
	 */
	protected function publishStyle($style)
	{
		$cs = $this->getPage()->getClientScript();
		$url = $this->getAssetUrl($style . '.css');
		if (!$cs->isStyleSheetFileRegistered($url)) {
			$cs->registerStyleSheetFile($url, $url);
		}
		return $url;
	}

	/**
	 * @param string $style rating style name
	 * @param string $fileExt rating image file extension, default is '.gif'
	 * @return array URL of publish the rating images
	 */
	protected function publishImages($style, $fileExt = '.gif')
	{
		$types = ['blank', 'selected', 'half', 'combined'];
		$files = [];
		foreach ($types as $type) {
			$files[$type] = $this->getAssetUrl("{$style}_{$type}{$fileExt}");
		}
		return $files;
	}

	/**
	 * Registers the relevant JavaScript.
	 */
	protected function registerClientScript()
	{
		$cs = $this->getPage()->getClientScript();
		$cs->registerPradoScript('ratings');
	}

	/**
	 * @param string $file asset file in the self::SCRIPT_PATH directory.
	 * @return string asset file url.
	 */
	protected function getAssetUrl($file = '')
	{
		$base = $this->getPage()->getClientScript()->getPradoScriptAssetUrl();
		return $base . '/' . self::SCRIPT_PATH . '/' . $file;
	}

	/**
	 * Add rating style class name to the class attribute
	 * when {@link setReadOnly ReadOnly} property is true and when the
	 * {@link setCssClass CssClass} property is empty.
	 * @param THtmlWriter $writer renderer
	 */
	public function render($writer)
	{
		$writer->addAttribute('id', $this->getClientID());
		$this->getPage()->getClientScript()->registerPostBackControl(
			$this->getClientClassName(),
			$this->getPostBackOptions()
		);
		parent::render($writer);
	}

	/**
	 * Gets the name of the javascript class responsible for performing postback for this control.
	 * This method overrides the parent implementation.
	 * @return string the javascript class name
	 */
	protected function getClientClassName()
	{
		return 'Prado.WebUI.TRatingList';
	}
}

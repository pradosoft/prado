<?php
/**
 * TBulletedList class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
 */

namespace Prado\Web\UI\WebControls;

use Prado\Exceptions\TNotSupportedException;
use Prado\TPropertyValue;
use Prado\Web\THttpUtility;

/**
 * TBulletedList class
 *
 * TBulletedList displays items in a bullet format.
 * The bullet style is specified by {@link setBulletStyle BulletStyle}. When
 * the style is 'CustomImage', the {@link setBackImageUrl BulletImageUrl}
 * specifies the image used as bullets.
 *
 * TBulletedList displays the item texts in three different modes, specified
 * via {@link setDisplayMode DisplayMode}. When the mode is Text, the item texts
 * are displayed as static texts; When the mode is 'HyperLink', each item
 * is displayed as a hyperlink whose URL is given by the item value, and the
 * {@link setTarget Target} property can be used to specify the target browser window;
 * When the mode is 'LinkButton', each item is displayed as a link button which
 * posts back to the page if a user clicks on that and the event {@link onClick OnClick}
 * will be raised under such a circumstance.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web\UI\WebControls
 * @since 3.0
 */
class TBulletedList extends TListControl implements \Prado\Web\UI\IPostBackEventHandler
{
	/**
	 * @var bool cached property value of Enabled
	 */
	private $_isEnabled;
	/**
	 * @var TPostBackOptions postback options
	 */
	private $_postBackOptions;

	private $_currentRenderItemIndex;

	/**
	 * Raises the postback event.
	 * This method is required by {@link IPostBackEventHandler} interface.
	 * If {@link getCausesValidation CausesValidation} is true, it will
	 * invoke the page's {@link TPage::validate validate} method first.
	 * It will raise {@link onClick OnClick} events.
	 * This method is mainly used by framework and control developers.
	 * @param TEventParameter $param the event parameter
	 */
	public function raisePostBackEvent($param)
	{
		if ($this->getCausesValidation()) {
			$this->getPage()->validate($this->getValidationGroup());
		}
		$this->onClick(new TBulletedListEventParameter((int) $param));
	}

	/**
	 * @return string tag name of the bulleted list
	 */
	protected function getTagName()
	{
		switch ($this->getBulletStyle()) {
			case TBulletStyle::Numbered:
			case TBulletStyle::LowerAlpha:
			case TBulletStyle::UpperAlpha:
			case TBulletStyle::LowerRoman:
			case TBulletStyle::UpperRoman:
				return 'ol';
		}
		return 'ul';
	}

	/**
	 * Gets the name of the javascript class responsible for performing postback for this control.
	 * This method overrides the parent implementation.
	 * @return string the javascript class name
	 */
	protected function getClientClassName()
	{
		return 'Prado.WebUI.TBulletedList';
	}

	/**
	 * Adds attribute name-value pairs to renderer.
	 * This overrides the parent implementation with additional bulleted list specific attributes.
	 * @param THtmlWriter $writer the writer used for the rendering purpose
	 */
	protected function addAttributesToRender($writer)
	{
		$needStart = false;
		switch ($this->getBulletStyle()) {
			case TBulletStyle::None:
				$writer->addStyleAttribute('list-style-type', 'none');
				$needStart = true;
				break;
			case TBulletStyle::Numbered:
				$writer->addStyleAttribute('list-style-type', 'decimal');
				$needStart = true;
				break;
			case TBulletStyle::LowerAlpha:
				$writer->addStyleAttribute('list-style-type', 'lower-alpha');
				$needStart = true;
				break;
			case TBulletStyle::UpperAlpha:
				$writer->addStyleAttribute('list-style-type', 'upper-alpha');
				$needStart = true;
				break;
			case TBulletStyle::LowerRoman:
				$writer->addStyleAttribute('list-style-type', 'lower-roman');
				$needStart = true;
				break;
			case TBulletStyle::UpperRoman:
				$writer->addStyleAttribute('list-style-type', 'upper-roman');
				$needStart = true;
				break;
			case TBulletStyle::Disc:
				$writer->addStyleAttribute('list-style-type', 'disc');
				break;
			case TBulletStyle::Circle:
				$writer->addStyleAttribute('list-style-type', 'circle');
				break;
			case TBulletStyle::Square:
				$writer->addStyleAttribute('list-style-type', 'square');
				break;
			case TBulletStyle::CustomImage:
				$url = $this->getBulletImageUrl();
				$writer->addStyleAttribute('list-style-image', "url($url)");
				break;
		}
		if ($needStart && ($start = $this->getFirstBulletNumber()) != 1) {
			$writer->addAttribute('start', "$start");
		}
		parent::addAttributesToRender($writer);
	}

	/**
	 * @return string image URL used for bullets when {@link getBulletStyle BulletStyle} is 'CustomImage'.
	 */
	public function getBulletImageUrl()
	{
		return $this->getViewState('BulletImageUrl', '');
	}

	/**
	 * @param string $value image URL used for bullets when {@link getBulletStyle BulletStyle} is 'CustomImage'.
	 */
	public function setBulletImageUrl($value)
	{
		$this->setViewState('BulletImageUrl', $value, '');
	}

	/**
	 * @return TBulletStyle style of bullets. Defaults to TBulletStyle::NotSet.
	 */
	public function getBulletStyle()
	{
		return $this->getViewState('BulletStyle', TBulletStyle::NotSet);
	}

	/**
	 * @param TBulletStyle $value style of bullets.
	 */
	public function setBulletStyle($value)
	{
		$this->setViewState('BulletStyle', TPropertyValue::ensureEnum($value, 'Prado\\Web\\UI\\WebControls\\TBulletStyle'), TBulletStyle::NotSet);
	}

	/**
	 * @return TBulletedListDisplayMode display mode of the list. Defaults to TBulletedListDisplayMode::Text.
	 */
	public function getDisplayMode()
	{
		return $this->getViewState('DisplayMode', TBulletedListDisplayMode::Text);
	}

	/**
	 * @param mixed $value
	 * @return TBulletedListDisplayMode display mode of the list.
	 */
	public function setDisplayMode($value)
	{
		$this->setViewState('DisplayMode', TPropertyValue::ensureEnum($value, 'Prado\\Web\\UI\\WebControls\\TBulletedListDisplayMode'), TBulletedListDisplayMode::Text);
	}

	/**
	 * @return int starting index when {@link getBulletStyle BulletStyle} is one of
	 * the following: 'Numbered', 'LowerAlpha', 'UpperAlpha', 'LowerRoman', 'UpperRoman'.
	 * Defaults to 1.
	 */
	public function getFirstBulletNumber()
	{
		return $this->getViewState('FirstBulletNumber', 1);
	}

	/**
	 * @param int $value starting index when {@link getBulletStyle BulletStyle} is one of
	 * the following: 'Numbered', 'LowerAlpha', 'UpperAlpha', 'LowerRoman', 'UpperRoman'.
	 */
	public function setFirstBulletNumber($value)
	{
		$this->setViewState('FirstBulletNumber', TPropertyValue::ensureInteger($value), 1);
	}

	/**
	 * Raises 'OnClick' event.
	 * This method is invoked when the {@link getDisplayMode DisplayMode} is 'LinkButton'
	 * and end-users click on one of the buttons.
	 * @param TBulletedListEventParameter $param event parameter.
	 */
	public function onClick($param)
	{
		$this->raiseEvent('OnClick', $this, $param);
	}

	/**
	 * @return string the target window or frame to display the Web page content
	 * linked to when {@link getDisplayMode DisplayMode} is 'HyperLink' and one of
	 * the hyperlinks is clicked.
	 */
	public function getTarget()
	{
		return $this->getViewState('Target', '');
	}

	/**
	 * @param string $value the target window or frame to display the Web page content
	 * linked to when {@link getDisplayMode DisplayMode} is 'HyperLink' and one of
	 * the hyperlinks is clicked.
	 */
	public function setTarget($value)
	{
		$this->setViewState('Target', $value, '');
	}

	/**
	 * Renders the control.
	 * @param THtmlWriter $writer the writer for the rendering purpose.
	 */
	public function render($writer)
	{
		if ($this->getHasItems()) {
			parent::render($writer);
		}
	}

	/**
	 * Renders the body contents.
	 * @param THtmlWriter $writer the writer for the rendering purpose.
	 */
	public function renderContents($writer)
	{
		$this->_isEnabled = $this->getEnabled(true);
		$this->_postBackOptions = $this->getPostBackOptions();
		$writer->writeLine();
		foreach ($this->getItems() as $index => $item) {
			if ($item->getHasAttributes()) {
				$writer->addAttributes($item->getAttributes());
			}
			$writer->renderBeginTag('li');
			$this->renderBulletText($writer, $item, $index);
			$writer->renderEndTag();
			$writer->writeLine();
		}
	}

	/**
	 * Renders each item
	 * @param THtmlWriter $writer writer for the rendering purpose
	 * @param TListItem $item item to be rendered
	 * @param int $index index of the item being rendered
	 */
	protected function renderBulletText($writer, $item, $index)
	{
		switch ($this->getDisplayMode()) {
			case TBulletedListDisplayMode::Text:
				$this->renderTextItem($writer, $item, $index);
				break;
			case TBulletedListDisplayMode::HyperLink:
				$this->renderHyperLinkItem($writer, $item, $index);
				break;
			case TBulletedListDisplayMode::LinkButton:
				$this->renderLinkButtonItem($writer, $item, $index);
				break;
		}
	}

	protected function renderTextItem($writer, $item, $index)
	{
		if ($item->getEnabled()) {
			$writer->write(THttpUtility::htmlEncode($item->getText()));
		} else {
			$writer->addAttribute('disabled', 'disabled');
			$writer->renderBeginTag('span');
			$writer->write(THttpUtility::htmlEncode($item->getText()));
			$writer->renderEndTag();
		}
	}

	protected function renderHyperLinkItem($writer, $item, $index)
	{
		if (!$this->_isEnabled || !$item->getEnabled()) {
			$writer->addAttribute('disabled', 'disabled');
		} else {
			$writer->addAttribute('href', $item->getValue());
			if (($target = $this->getTarget()) !== '') {
				$writer->addAttribute('target', $target);
			}
		}
		if (($accesskey = $this->getAccessKey()) !== '') {
			$writer->addAttribute('accesskey', $accesskey);
		}
		$writer->renderBeginTag('a');
		$writer->write(THttpUtility::htmlEncode($item->getText()));
		$writer->renderEndTag();
	}

	protected function renderLinkButtonItem($writer, $item, $index)
	{
		if (!$this->_isEnabled || !$item->getEnabled()) {
			$writer->addAttribute('disabled', 'disabled');
		} else {
			$this->_currentRenderItemIndex = $index;
			$writer->addAttribute('id', $this->getClientID() . $index);
			$writer->addAttribute('href', "javascript:;//" . $this->getClientID() . $index);
			$cs = $this->getPage()->getClientScript();
			$cs->registerPostBackControl($this->getClientClassName(), $this->getPostBackOptions());
		}
		if (($accesskey = $this->getAccessKey()) !== '') {
			$writer->addAttribute('accesskey', $accesskey);
		}
		$writer->renderBeginTag('a');
		$writer->write(THttpUtility::htmlEncode($item->getText()));
		$writer->renderEndTag();
	}

	/**
	 * @return array postback options used for linkbuttons.
	 */
	protected function getPostBackOptions()
	{
		$options['ValidationGroup'] = $this->getValidationGroup();
		$options['CausesValidation'] = $this->getCausesValidation();
		$options['EventTarget'] = $this->getUniqueID();
		$options['EventParameter'] = $this->_currentRenderItemIndex;
		$options['ID'] = $this->getClientID() . $this->_currentRenderItemIndex;
		$options['StopEvent'] = true;
		return $options;
	}

	protected function canCauseValidation()
	{
		$group = $this->getValidationGroup();
		$hasValidators = $this->getPage()->getValidators($group)->getCount() > 0;
		return $this->getCausesValidation() && $hasValidators;
	}

	/**
	 * @param mixed $value
	 * @throws TNotSupportedException if this method is invoked
	 */
	public function setAutoPostBack($value)
	{
		throw new TNotSupportedException('bulletedlist_autopostback_unsupported');
	}

	/**
	 * @param mixed $index
	 * @throws TNotSupportedException if this method is invoked
	 */
	public function setSelectedIndex($index)
	{
		throw new TNotSupportedException('bulletedlist_selectedindex_unsupported');
	}

	/**
	 * @param mixed $indices
	 * @throws TNotSupportedException if this method is invoked
	 */
	public function setSelectedIndices($indices)
	{
		throw new TNotSupportedException('bulletedlist_selectedindices_unsupported');
	}

	/**
	 * @param mixed $value
	 * @throws TNotSupportedException if this method is invoked
	 */
	public function setSelectedValue($value)
	{
		throw new TNotSupportedException('bulletedlist_selectedvalue_unsupported');
	}

	/**
	 * @param mixed $values
	 * @throws TNotSupportedException if this method is invoked
	 */
	public function setSelectedValues($values)
	{
		throw new TNotSupportedException('bulletedlist_selectedvalue_unsupported');
	}
}

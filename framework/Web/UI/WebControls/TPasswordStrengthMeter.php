<?php

/**
 * TPasswordStrengthMeter class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\Exceptions\TConfigurationException;
use Prado\I18N\core\CultureInfoUnits;
use Prado\I18N\TI18NControlTrait;
use Prado\TPropertyValue;
use Prado\Web\Javascripts\TJavaScript;

/**
 * TPasswordStrengthMeter class
 *
 * TPasswordStrengthMeter shows the estimated strength of the password typed in
 * the control named by {@see setControlToMeasure ControlToMeasure}. Its bar
 * fills from the left as the estimated entropy grows toward
 * {@see setMaxEntropy MaxEntropy}, and the color of the fill shifts continuously
 * from red at 0 bits to green at MaxEntropy. After the bar:
 * - {@see setShowText ShowText} (default true) → the {@see TPasswordStrength} level name;
 * - {@see setShowBits ShowBits} (default false) → the estimate in bits, such as
 *   "62 bits". The unit name comes from the CLDR "digital-bit" patterns of the
 *   {@see getCulture Culture} (the application culture by default), so it is
 *   "62 Bit" in German and "62 бита" in Russian; the browser selects the plural
 *   form with Intl.PluralRules and formats the number with Intl.NumberFormat.
 *   {@see setBitsFormat BitsFormat} replaces the patterns with one format.
 *
 * The estimate is {@see TPasswordStrengthValidator::estimateEntropy()}, computed
 * in the browser on each input. The meter informs; pair it with a
 * {@see TPasswordStrengthValidator} to enforce a minimum strength.
 *
 * ```xml
 * <com:TTextBox ID="Password" TextMode="Password" />
 * <com:TPasswordStrengthMeter ControlToMeasure="Password" />
 * <com:TPasswordStrengthValidator ControlToValidate="Password" />
 * ```
 *
 * The element has `role="meter"` with `aria-valuenow` in bits and the level
 * name as `aria-valuetext`. Its accessible name defaults to "Password strength";
 * set the "aria-label" attribute to change it. The bar and the texts are hidden
 * from assistive technology, which reads the meter values instead. The fill
 * length and the level name keep the strength readable without color.
 *
 * The default style sheet is published from the assets directory; set
 * {@see setCssUrl CssUrl} to replace it, or to an empty string to style the
 * meter elsewhere.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TPasswordStrengthMeter extends TWebControl
{
	use TI18NControlTrait;

	/**
	 * The default level names, from VeryWeak to VeryStrong.
	 */
	public const DEFAULT_LEVEL_TEXTS = 'Very weak,Weak,Medium,Strong,Very strong';

	/**
	 * @return string the tag name of the meter, "div".
	 */
	protected function getTagName()
	{
		return 'div';
	}

	/**
	 * @return bool true; the client script finds the meter by its ID.
	 */
	public function getEnsureId()
	{
		return true;
	}

	/**
	 * @return string the ID path of the password control. Defaults to ''.
	 */
	public function getControlToMeasure()
	{
		return $this->getViewState('ControlToMeasure', '');
	}

	/**
	 * Sets the ID path of the password control, relative to the naming container of the meter.
	 * @param string $value the ID path of the password control.
	 */
	public function setControlToMeasure($value)
	{
		$this->setViewState('ControlToMeasure', TPropertyValue::ensureString($value), '');
	}

	/**
	 * @return int the estimated entropy in bits that fills the bar. Defaults to 120.
	 */
	public function getMaxEntropy()
	{
		return $this->getViewState('MaxEntropy', 120);
	}

	/**
	 * Sets the estimated entropy in bits that fills the bar and turns it green.
	 * @param int $value the entropy in bits, at least 1.
	 */
	public function setMaxEntropy($value)
	{
		$this->setViewState('MaxEntropy', max(1, TPropertyValue::ensureInteger($value)), 120);
	}

	/**
	 * @return bool whether the level name follows the bar. Defaults to true.
	 */
	public function getShowText()
	{
		return $this->getViewState('ShowText', true);
	}

	/**
	 * Sets whether the {@see TPasswordStrength} level name follows the bar.
	 * @param bool $value whether the level name shows.
	 */
	public function setShowText($value)
	{
		$this->setViewState('ShowText', TPropertyValue::ensureBoolean($value), true);
	}

	/**
	 * @return bool whether the estimate in bits follows the bar. Defaults to false.
	 */
	public function getShowBits()
	{
		return $this->getViewState('ShowBits', false);
	}

	/**
	 * Sets whether the estimate in bits, formatted by {@see setBitsFormat BitsFormat}, follows the bar.
	 * @param bool $value whether the bits show.
	 */
	public function setShowBits($value)
	{
		$this->setViewState('ShowBits', TPropertyValue::ensureBoolean($value), false);
	}

	/**
	 * @return string the format of the bits text; "{0}" is the rounded bits.
	 *   Defaults to '', the CLDR patterns of the culture.
	 */
	public function getBitsFormat()
	{
		return $this->getViewState('BitsFormat', '');
	}

	/**
	 * Sets one format for the bits text, such as "{0} b", in place of the CLDR
	 * patterns of the culture. "{0}" is replaced with the formatted estimate
	 * rounded to whole bits. An empty format uses the culture.
	 * @param string $value the format of the bits text.
	 */
	public function setBitsFormat($value)
	{
		$this->setViewState('BitsFormat', TPropertyValue::ensureString($value), '');
	}

	/**
	 * Gets the plural patterns of the bits text, keyed by CLDR plural category.
	 * {@see getBitsFormat BitsFormat} gives the single "other" pattern; otherwise
	 * the long "digital-bit" unit patterns of the {@see getCulture Culture} apply,
	 * then the short ones, then "{0} bits".
	 * @return array<string, string> the patterns, each with a "{0}" number placeholder.
	 */
	public function getBitsPatterns(): array
	{
		if (($format = $this->getBitsFormat()) !== '') {
			return [CultureInfoUnits::UNIT_OTHER_PATTERN => $format];
		}
		$cultureInfo = $this->getCultureInfo();
		return $cultureInfo->getUnitPatterns(CultureInfoUnits::TYPE_DIGITAL_BIT)
			?: $cultureInfo->getUnitPatterns(CultureInfoUnits::TYPE_DIGITAL_BIT, CultureInfoUnits::WIDTH_SHORT)
			?: [CultureInfoUnits::UNIT_OTHER_PATTERN => '{0} bits'];
	}

	/**
	 * @return string the comma separated level names. Defaults to {@see DEFAULT_LEVEL_TEXTS}.
	 */
	public function getLevelTexts()
	{
		return $this->getViewState('LevelTexts', self::DEFAULT_LEVEL_TEXTS);
	}

	/**
	 * Sets the names of the five {@see TPasswordStrength} levels, from VeryWeak
	 * to VeryStrong, separated by commas. A missing name falls back to the default.
	 * @param string $value the comma separated level names.
	 */
	public function setLevelTexts($value)
	{
		$this->setViewState('LevelTexts', TPropertyValue::ensureString($value), self::DEFAULT_LEVEL_TEXTS);
	}

	/**
	 * @return string[] the five level names, from VeryWeak to VeryStrong.
	 */
	public function getLevelTextList(): array
	{
		$defaults = explode(',', self::DEFAULT_LEVEL_TEXTS);
		$texts = array_map('trim', explode(',', $this->getLevelTexts()));
		foreach ($defaults as $i => $default) {
			if (($texts[$i] ?? '') === '') {
				$texts[$i] = $default;
			}
		}
		return array_slice($texts, 0, count($defaults));
	}

	/**
	 * @return string the URL of the style sheet; "default" publishes the bundled one. Defaults to "default".
	 */
	public function getCssUrl()
	{
		return $this->getViewState('CssUrl', 'default');
	}

	/**
	 * Sets the URL of the style sheet. "default" publishes the bundled style
	 * sheet and an empty string registers none.
	 * @param string $value the URL of the style sheet.
	 */
	public function setCssUrl($value)
	{
		$this->setViewState('CssUrl', TPropertyValue::ensureString($value), 'default');
	}

	/**
	 * Resolves {@see getControlToMeasure ControlToMeasure}.
	 * @throws TConfigurationException if ControlToMeasure is empty or names no control.
	 * @return \Prado\Web\UI\TControl the password control.
	 */
	public function getMeasureTarget()
	{
		if (($id = $this->getControlToMeasure()) !== '' && ($control = $this->findControl($id)) !== null) {
			return $control;
		}
		throw new TConfigurationException('passwordstrengthmeter_controltomeasure_invalid', $this->getID());
	}

	/**
	 * Registers the style sheet and the client script.
	 * @param mixed $param event parameter
	 */
	public function onPreRender($param)
	{
		parent::onPreRender($param);
		$this->registerStyleSheet();
		$this->registerClientScript();
	}

	/**
	 * Registers the style sheet named by {@see getCssUrl CssUrl}.
	 */
	protected function registerStyleSheet()
	{
		$url = $this->getCssUrl();
		if ($url === '') {
			return;
		}
		if ($url === 'default') {
			$url = $this->getApplication()->getAssetManager()->publishFilePath(__DIR__ . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'passwordstrengthmeter.css');
		}
		$this->getPage()->getClientScript()->registerStyleSheetFile($url, $url);
	}

	/**
	 * Registers the client script that updates the meter on each input.
	 */
	protected function registerClientScript()
	{
		$cs = $this->getPage()->getClientScript();
		$cs->registerPradoScript('validator');
		$options = TJavaScript::encode($this->getClientOptions());
		$cs->registerEndScript('prado:' . $this->getClientID(), "new Prado.WebUI.TPasswordStrengthMeter({$options});");
	}

	/**
	 * @return array the options of the client meter.
	 */
	protected function getClientOptions()
	{
		return [
			'ID' => $this->getClientID(),
			'ControlToMeasure' => $this->getMeasureTarget()->getClientID(),
			'MaxEntropy' => $this->getMaxEntropy(),
			'LevelTexts' => $this->getLevelTextList(),
			'LevelEntropy' => array_values(TPasswordStrengthValidator::STRENGTH_ENTROPY),
			'Culture' => str_replace('_', '-', $this->getCulture()),
			'BitsPatterns' => $this->getBitsPatterns(),
		];
	}

	/**
	 * Adds the meter role, its values, and its default accessible name.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer
	 */
	protected function addAttributesToRender($writer)
	{
		parent::addAttributesToRender($writer);
		$writer->addAttribute('id', $this->getClientID());
		$cssClass = trim('prado-password-meter ' . $this->getCssClass());
		$writer->addAttribute('class', $cssClass);
		$writer->addAttribute('role', 'meter');
		$writer->addAttribute('aria-valuemin', '0');
		$writer->addAttribute('aria-valuemax', (string) $this->getMaxEntropy());
		$writer->addAttribute('aria-valuenow', '0');
		$writer->addAttribute('aria-valuetext', $this->getLevelTextList()[0]);
		if (!$this->getHasAttributes() || (!$this->getAttributes()->contains('aria-label') && !$this->getAttributes()->contains('aria-labelledby'))) {
			$writer->addAttribute('aria-label', 'Password strength');
		}
	}

	/**
	 * Renders the empty bar, then the level text with ShowText and the bits text with ShowBits.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer
	 */
	public function renderContents($writer)
	{
		$writer->write('<span class="prado-password-meter-bar" aria-hidden="true"><span class="prado-password-meter-fill"></span></span>');
		if ($this->getShowText()) {
			$writer->write('<span class="prado-password-meter-text" aria-hidden="true"></span>');
		}
		if ($this->getShowBits()) {
			$writer->write('<span class="prado-password-meter-bits" aria-hidden="true"></span>');
		}
	}
}

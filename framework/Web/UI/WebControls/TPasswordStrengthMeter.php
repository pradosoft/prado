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
use Prado\TPropertyValue;
use Prado\Web\Javascripts\TJavaScript;

/**
 * TPasswordStrengthMeter class
 *
 * TPasswordStrengthMeter shows the estimated strength of the password typed in
 * the control named by {@see setControlToMeasure ControlToMeasure}. It renders a
 * row of {@see setNotches Notches} that fill from the left as the estimated
 * entropy grows toward {@see setMaxEntropy MaxEntropy}. The color of the filled
 * notches shifts continuously from red at 0 bits to green at MaxEntropy, and
 * the text after the bar names the {@see TPasswordStrength} level.
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
 * set the "aria-label" attribute to change it. The bar and the text are hidden
 * from assistive technology, which reads the meter values instead. The level
 * name keeps the strength readable without color.
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
	 * @return int the number of notches in the bar. Defaults to 20.
	 */
	public function getNotches()
	{
		return $this->getViewState('Notches', 20);
	}

	/**
	 * Sets the number of notches in the bar. More notches show finer steps of strength.
	 * @param int $value the number of notches, from 2 to 100.
	 */
	public function setNotches($value)
	{
		$this->setViewState('Notches', min(100, max(2, TPropertyValue::ensureInteger($value))), 20);
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
	 * Renders the empty notches and the level text.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer
	 */
	public function renderContents($writer)
	{
		$writer->write('<span class="prado-password-meter-bar" aria-hidden="true">');
		$writer->write(str_repeat('<span class="prado-password-meter-notch"></span>', $this->getNotches()));
		$writer->write('</span><span class="prado-password-meter-text" aria-hidden="true"></span>');
	}
}

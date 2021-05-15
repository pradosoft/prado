<?php
/**
 * TTimeAgo class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
 */

namespace Prado\Web\UI\WebControls;

use Prado\TPropertyValue;
use Prado\Web\Javascripts\TJavaScript;

/**
 * TTimeAgo class
 *
 * TimeAgo is shows time and date in a label as '# (seconds|minutes|etc) ago'.  This
 * embeds javascript to keep the time ago up to date.  As time updates,
 * the moments ago are kept up to date.  The resolution depends on how far ago
 * the moment is.
 *
 * Future time delimiters are also handled.  When clicking on a time ago label,
 * the entry turns into the date and time stamp for specifics.
 * @author Brad Anderson <belisoful@icloud.com>
 * @package Prado\Web\UI
 * @since 4.2.0
 */

class TTimeAgo extends TLabel
{
	/**
	 * Adds attribute name-value pairs to renderer.
	 * This overrides the parent implementation with additional button specific attributes.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 */
	protected function addAttributesToRender($writer)
	{
		if ($this->getPage()->getClientSupportsJavaScript() && $this->getEnabled(true)) {
			$writer->addAttribute('id', $this->getClientID());
		}
		parent::addAttributesToRender($writer);
	}

	/**
	 * Registers CSS and JS.
	 * This method is invoked right before the control rendering, if the control is visible.
	 * @param mixed $param event parameter
	 */
	public function onPreRender($param)
	{
		parent::onPreRender($param);
		if ($this->getPage()->getClientSupportsJavaScript() && $this->getEnabled(true)) {
			$this->registerClientScript();
		}
	}

	/**
	 * Registers the relevant JavaScript.
	 */
	protected function registerClientScript()
	{
		$options = TJavaScript::encode($this->getClientOptions());
		$className = $this->getClientClassName();
		$cs = $this->getPage()->getClientScript();
		$cs->registerPradoScript('timeago');
		$cs->registerEndScript('prado:' . $this->getClientID(), "new $className($options);");
	}

	/**
	 * Gets the name of the javascript class responsible for performing postback for this control.
	 * This method overrides the parent implementation.
	 * @return string the javascript class name
	 */
	protected function getClientClassName()
	{
		return 'Prado.WebUI.TTimeAgo';
	}

	/**
	 * @return array the JavaScript options for this control
	 */
	protected function getClientOptions()
	{
		$options['ID'] = $this->getClientID();
		$options['ServerTime'] = time();
		$options['OriginTime'] = $this->getTimeStamp();
		$options['ClickToChange'] = $this->getClickSeeDateTime();
		$options['UseRawTime'] = $this->getUseRawTime();
		
		return $options;
	}
	
	/**
	 *	getDateTime is the date of the time ago label
	 */
	public function getDateTime()
	{
		return $this->getViewState('datetime', time());
	}
	
	/**
	 *	setDateTime sets the date of the time ago label
	 * @param mixed $v
	 */
	public function setDateTime($v)
	{
		$this->setViewState('datetime', TPropertyValue::ensureString($v));
	}
	
	/**
	 *	getClickSeeDateTime returns whether or not to allow clicking to change the label to
	 * the the exact time and date. Clicking a second time changes the time ago back to it's
	 * continuous function.
	 */
	public function getClickSeeDateTime()
	{
		return $this->getViewState('clicksee', true);
	}
	
	/**
	 *	getClickSeeDateTime returns whether or not to allow clicking to change the label to
	 * the the exact time and date. Clicking a second time changes the time ago back to it's
	 * continuous function.
	 * @param mixed $v
	 */
	public function setClickSeeDateTime($v)
	{
		$this->setViewState('clicksee', TPropertyValue::ensureBoolean($v));
	}
	
	public function getUseRawTime()
	{
		return $this->getViewState('userawtime', true);
	}
	public function setUseRawTime($v)
	{
		$this->setViewState('userawtime', TPropertyValue::ensureBoolean($v));
	}
	
	public function getTimeStamp()
	{
		$dt = $this->getDateTime();
		if (!$dt) {
			$dt = '2000-01-01 00:00:00';
		}
		if (is_numeric($dt)) {
			return $dt;
		}
		return strtotime($dt);
	}
}
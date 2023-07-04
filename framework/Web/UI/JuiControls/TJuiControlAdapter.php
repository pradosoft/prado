<?php
/**
 * TJuiControlAdapter class file.
 *
 * @author Fabio Bas <ctrlaltca@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\JuiControls;

use Prado\Web\UI\ActiveControls\TActiveControlAdapter;

/**
 * TJuiControlAdapter class
 *
 * TJuiControlAdapter is the base adapter class for controls that are
 * derived from a jQuery-ui widget. It exposes convenience methods to
 * publish jQuery-UI javascript and css assets.
 *
 * @author Fabio Bas <ctrlaltca@gmail.com>
 * @since 3.3
 * @method IJuiOptions&\Prado\Web\UI\TControl getControl()
 */
class TJuiControlAdapter extends TActiveControlAdapter
{
	public const CSS_PATH = 'themes';
	public const BASE_CSS_FILENAME = 'jquery-ui.css';

	/**
	 * Replace default StateTracker with {@see \Prado\Web\UI\JuiControls\TJuiCallbackPageStateTracker} for
	 * options tracking in ViewState.
	 * @param \Prado\TEventParameter $param event parameter to be passed to the event handlers
	 */
	public function onInit($param)
	{
		parent::onInit($param);
		$this->setStateTracker('TJuiCallbackPageStateTracker');
	}

	/**
	 * @param string $value set the jquery-ui style
	 */
	public function setJuiBaseStyle($value)
	{
		$this->getControl()->setViewState('JuiBaseStyle', $value, 'base');
	}

	/**
	 * @return string current jquery-ui style
	 */
	public function getJuiBaseStyle()
	{
		return $this->getControl()->getViewState('JuiBaseStyle', 'base');
	}

	/**
	 * Inject jquery script and styles before render
	 * @param mixed $param
	 */
	public function onPreRender($param)
	{
		parent::onPreRender($param);
		$this->getPage()->getClientScript()->registerPradoScript('jqueryui');
		$this->publishJuiStyle(self::BASE_CSS_FILENAME);
	}

	/**
	 * @param string $file jQuery asset file in the jquery-ui directory.
	 * @return string jQuery asset url.
	 */
	protected function getAssetUrl($file = '')
	{
		$base = $this->getPage()->getClientScript()->getPradoScriptAssetUrl('jquery-ui');
		return $base . '/' . $file;
	}

	/**
	 * Publish the jQuery-ui style Css asset file.
	 * @param string $file name
	 * @return string Css file url.
	 */
	public function publishJuiStyle($file)
	{
		$url = $this->getAssetUrl(self::CSS_PATH . '/' . $this->getJuiBaseStyle() . '/' . $file);
		$cs = $this->getPage()->getClientScript();
		if (!$cs->isStyleSheetFileRegistered($url)) {
			$cs->registerStyleSheetFile($url, $url);
		}
		return $url;
	}

	/**
	 * Calls the parent implementation first and sets the parent control for the
	 * {@see \Prado\Web\UI\JuiControls\TJuiControlOptions} again afterwards since it was not serialized in viewstate.
	 */
	public function loadState()
	{
		parent::loadState();
		$this->getControl()->getOptions()->setControl($this->getControl());
	}
}

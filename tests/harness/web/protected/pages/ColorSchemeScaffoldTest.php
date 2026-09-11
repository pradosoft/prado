<?php

use Prado\Prado;

class ColorSchemeScaffoldTest extends TPage
{
	/**
	 * Publishes and registers the ActiveRecord scaffold stylesheet, which no
	 * control on this page would otherwise pull in.
	 * @param mixed $param event parameter
	 */
	public function onPreRender($param)
	{
		parent::onPreRender($param);
		$css = Prado::getFrameworkPath() . '/Data/ActiveRecord/Scaffold/style.css';
		$url = $this->getApplication()->getAssetManager()->publishFilePath($css);
		$this->getPage()->getClientScript()->registerStyleSheetFile($url, $url);
	}
}

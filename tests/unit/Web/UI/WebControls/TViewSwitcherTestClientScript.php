<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use Prado\Web\UI\TClientScriptManager;

/**
 * Client script manager that records what TAccordion and TTabPanel register.
 */
class TViewSwitcherTestClientScript extends TClientScriptManager
{
	public array $pradoScripts = [];
	public array $endScripts = [];
	public array $hiddenFields = [];
	public array $styleSheets = [];

	public function registerPradoScript($name)
	{
		$this->pradoScripts[] = $name;
	}

	public function registerEndScript($key, $script)
	{
		$this->endScripts[$key] = $script;
	}

	public function registerHiddenField($name, $value)
	{
		$this->hiddenFields[$name] = $value;
	}

	public function registerStyleSheetFile($key, $url, $media = '')
	{
		$this->styleSheets[$key] = $url;
	}
}

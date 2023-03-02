<?php

class MyJavascriptLib extends TComponent
{
	private $_packages = []; //keep track of all registrations

	private $_manager;

	protected function __construct(TPage $owner)
	{
		$this->_manager = $owner->getClientScript();
		$owner->onPreRenderComplete = [$this, 'registerScriptLoader'];
	}

	public static function registerPackage(TControl $control, $name)
	{
		static $instance;
		if ($instance === null) {
			$instance = new self($control->getPage());
		}
		$instance->_packages[$name] = true;
	}

	protected function registerScriptLoader()
	{
		$dir = __DIR__ . '/myscripts'; //contains my javascript files
		$scripts = array_keys($this->_packages);
		$url = $this->_manager->registerJavascriptPackages($dir, $scripts);
		$this->_manager->registerScriptFile($url, $url);
	}
}

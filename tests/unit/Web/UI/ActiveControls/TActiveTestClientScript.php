<?php

/**
 * TActiveTestClientScript class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Test\Unit\Web\UI\ActiveControls;

use Prado\Web\UI\TClientScriptManager;

/**
 * TActiveTestClientScript is a client script manager that records the callback
 * controls and the Prado script packages a control registers, and publishes no assets.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TActiveTestClientScript extends TClientScriptManager
{
	/**
	 * @var array the registered callback controls, each `[class, options]`
	 */
	public array $callbackControls = [];

	/**
	 * @var string[] the registered Prado script package names
	 */
	public array $pradoScripts = [];

	/**
	 * Records the Prado script package.
	 * @param string $name the package name
	 */
	public function registerPradoScript($name)
	{
		$this->pradoScripts[] = $name;
	}

	/**
	 * Records the callback control class and its options.
	 * @param string $class the client class
	 * @param array $options the client options
	 */
	public function registerCallbackControl($class, $options)
	{
		$this->callbackControls[] = [$class, $options];
	}
}

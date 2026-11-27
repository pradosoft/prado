<?php

/**
 * TTestRecordingClientScript class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Test\Unit\Harness\Web\UI;

use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\TClientScriptManager;
use Prado\Web\UI\TPage;

/**
 * TTestRecordingClientScript is a client script manager that records what controls register.
 *
 * {@see attachTo} creates one for a page and installs it as the page's client
 * script manager. The registration methods record their arguments and publish
 * no assets, so a control's pre-render and render run without an asset manager.
 *
 * | Recorded in | Method |
 * |---|---|
 * | `$pradoScripts` | {@see registerPradoScript} |
 * | `$pradoStyles` | {@see registerPradoStyle} |
 * | `$beginScripts` | {@see registerBeginScript} |
 * | `$endScripts` | {@see registerEndScript} |
 * | `$postBackControls` | {@see registerPostBackControl} |
 * | `$flushedControls` | {@see flushScriptFiles} |
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TTestRecordingClientScript extends TClientScriptManager
{
	/**
	 * @var string[] the names of the registered Prado script packages
	 */
	public array $pradoScripts = [];

	/**
	 * @var string[] the names of the registered Prado style packages
	 */
	public array $pradoStyles = [];

	/**
	 * @var array<string, string> the registered begin scripts by key
	 */
	public array $beginScripts = [];

	/**
	 * @var array<string, string> the registered end scripts by key
	 */
	public array $endScripts = [];

	/**
	 * @var array<int, array{string, array}> the registered postback controls as [client class, options]
	 */
	public array $postBackControls = [];

	/**
	 * @var array<int, ?\Prado\Web\UI\TControl> the controls that flushed the script files
	 */
	public array $flushedControls = [];

	/**
	 * Creates a recording client script manager and installs it on a page.
	 * @param TPage $page the page
	 * @return static the client script manager
	 */
	public static function attachTo(TPage $page): static
	{
		$cs = new static($page);
		PradoUnit::setProp($page, '_clientScript', $cs);
		return $cs;
	}

	/**
	 * Records a Prado script package.
	 * @param string $name the package name
	 */
	public function registerPradoScript($name)
	{
		$this->pradoScripts[] = $name;
	}

	/**
	 * Records a Prado style package.
	 * @param string $name the package name
	 */
	public function registerPradoStyle($name)
	{
		$this->pradoStyles[] = $name;
	}

	/**
	 * Records a begin script.
	 * @param string $key the script key
	 * @param string $script the script
	 */
	public function registerBeginScript($key, $script)
	{
		$this->beginScripts[$key] = $script;
	}

	/**
	 * Records an end script.
	 * @param string $key the script key
	 * @param string $script the script
	 */
	public function registerEndScript($key, $script)
	{
		$this->endScripts[$key] = $script;
	}

	/**
	 * Records a postback control.
	 * @param string $jsClass the client class
	 * @param array $options the client options
	 */
	public function registerPostBackControl($jsClass, $options)
	{
		$this->postBackControls[] = [$jsClass, $options];
	}

	/**
	 * Records the control that flushes the script files and writes a marker.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer
	 * @param ?\Prado\Web\UI\TControl $control the control that flushes the files
	 */
	public function flushScriptFiles($writer, $control = null)
	{
		$this->flushedControls[] = $control;
		$writer->write('<!--script files-->');
	}
}

<?php

/**
 * TServiceWorker class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\Exceptions\TConfigurationException;
use Prado\TPropertyValue;
use Prado\Web\Javascripts\TJavaScript;
use Prado\Web\Services\ServiceWorker\TServiceWorkerDefinition;
use Prado\Web\Services\ServiceWorker\TServiceWorkerModule;

/**
 * TServiceWorker class
 *
 * TServiceWorker registers the page's service worker of
 * {@see TServiceWorkerModule} through `Prado.ServiceWorker.register()` in the
 * `serviceworker` script package. It renders no markup.
 * ```php
 * <com:THead>
 *   <com:TServiceWorker />
 * </com:THead>
 * <com:TServiceWorker Worker="admin" />
 * ```
 * With the module's {@see TServiceWorkerModule::setAutoRegister AutoRegister},
 * a page without a TServiceWorker gets one, so a template places it only to
 * set {@see setWorker Worker} or {@see setModuleID ModuleID}.
 *
 * The worker is {@see setWorker Worker}, else the page's selection
 * ({@see TServiceWorkerModule::getPageWorkerName()}). It registers when the
 * module and the worker are enabled and the worker's scope controls the page's
 * URL. It registers once per page load, not in callbacks; a second
 * TServiceWorker of the page replaces the registration of the first.
 *
 * The registration runs at the end of the page form. A page without a
 * {@see TForm} renders no end scripts, so there the control renders the
 * pending script files and the registration where it is placed, in the head
 * when the module added it.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TServiceWorker extends \Prado\Web\UI\TControl
{
	/**
	 * The key of the registration script.
	 */
	public const SCRIPT_KEY = 'prado:serviceworker';

	/** @var ?string the registration rendered in place on a page without a form */
	private ?string $_script = null;

	/**
	 * @return string the ID of the TServiceWorkerModule, or `Auto` for the first
	 *   one. Defaults to `Auto`.
	 */
	public function getModuleID()
	{
		return $this->getViewState('ModuleID', TServiceWorkerModule::AUTO);
	}

	/**
	 * @param string $value the ID of the TServiceWorkerModule, or `Auto` for the first one
	 */
	public function setModuleID($value)
	{
		$value = trim(TPropertyValue::ensureString($value));
		$this->setViewState('ModuleID', $value === '' ? TServiceWorkerModule::AUTO : $value, TServiceWorkerModule::AUTO);
	}

	/**
	 * @return string the worker name. Defaults to empty, which selects the page's worker.
	 */
	public function getWorker()
	{
		return $this->getViewState('Worker', '');
	}

	/**
	 * @param string $value the worker name; empty selects the page's worker
	 */
	public function setWorker($value)
	{
		$this->setViewState('Worker', trim(TPropertyValue::ensureString($value)), '');
	}

	/**
	 * Returns the module of {@see getModuleID ModuleID}.
	 * @throws TConfigurationException if ModuleID names no TServiceWorkerModule.
	 * @return ?TServiceWorkerModule the module, or null for `Auto` without one
	 */
	public function getServiceWorkerModule(): ?TServiceWorkerModule
	{
		return self::findModule($this->getApplication(), $this->getModuleID());
	}

	/**
	 * Returns the worker this control registers.
	 * @return ?TServiceWorkerDefinition the worker, or null when none registers
	 */
	public function getServiceWorkerDefinition(): ?TServiceWorkerDefinition
	{
		if (($module = $this->getServiceWorkerModule()) === null || !$module->getEnabled()) {
			return null;
		}
		return $module->getPageWorker($this->getPage(), $this->getWorker());
	}

	/**
	 * Registers the worker of the page: at the end of the page form, or, on a
	 * page without a form, for {@see render()}.
	 * @param \Prado\TEventParameter $param event parameter
	 */
	public function onPreRender($param)
	{
		parent::onPreRender($param);
		$this->_script = null;
		$page = $this->getPage();
		if (!$this->getEnabled(true) || $page->getIsCallback() || ($worker = $this->getServiceWorkerDefinition()) === null) {
			return;
		}
		$module = $this->getServiceWorkerModule();
		$url = TJavaScript::quoteString($module->getScriptUrl($worker));
		$options = TJavaScript::encode(['scope' => $worker->getScope(), 'worker' => $worker->getName()]);
		$script = "Prado.ServiceWorker.register($url, $options);";
		$cs = $page->getClientScript();
		$cs->registerPradoScript('serviceworker');
		if ($page->getForm() === null) {
			$this->_script = $script;
		} else {
			$cs->registerEndScript(self::SCRIPT_KEY, $script);
		}
	}

	/**
	 * Renders, on a page without a form, the pending script files and the
	 * registration. It renders nothing otherwise.
	 * @param \Prado\Web\UI\THtmlWriter $writer writer for the rendering purpose
	 */
	public function render($writer)
	{
		if ($this->_script !== null) {
			$this->getPage()->getClientScript()->renderAllPendingScriptFiles($writer);
			$writer->write(TJavaScript::renderScriptBlocks([$this->_script]));
		}
		parent::render($writer);
	}

	/**
	 * Returns a TServiceWorkerModule by ID.
	 * @param \Prado\TApplication $app the application
	 * @param string $id the module ID, or `Auto` for the first one
	 * @throws TConfigurationException if the ID names no TServiceWorkerModule.
	 * @return ?TServiceWorkerModule the module, or null for `Auto` without one
	 */
	public static function findModule($app, string $id): ?TServiceWorkerModule
	{
		if ($id === TServiceWorkerModule::AUTO) {
			$id = array_key_first($app->getModulesByType(TServiceWorkerModule::class));
			if ($id === null) {
				return null;
			}
		}
		$module = $app->getModule($id);
		if (!$module instanceof TServiceWorkerModule) {
			throw new TConfigurationException('serviceworker_module_invalid', static::class, $id);
		}
		return $module;
	}
}

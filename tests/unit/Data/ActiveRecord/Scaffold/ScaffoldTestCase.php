<?php

namespace Prado\Test\Unit\Data\ActiveRecord\Scaffold;

use Prado\Prado;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\Services\TPageService;
use Prado\Web\TAssetManager;
use Prado\Web\UI\TControl;
use Prado\Web\UI\TForm;
use Prado\Web\UI\WebControls\THead;
use Prado\Web\UI\WebControls\TRepeaterCommandEventParameter;
use Prado\Web\UI\WebControls\TRepeaterItem;
use Prado\Web\UI\TCommandEventParameter;

/**
 * Base of the scaffold control tests.
 *
 * Each test gets a fresh {@see ScaffoldTestPage} with a THead and a TForm, a page service for template
 * loading, and an asset manager publishing into a directory under the system temporary
 * directory. The application service and asset manager are restored afterwards.
 * PHP notices and deprecations not suppressed with `@` raise an ErrorException so a test fails on them.
 */
abstract class ScaffoldTestCase extends \PHPUnit\Framework\TestCase
{
	protected ScaffoldTestPage $page;
	protected TForm $form;
	private $_service;
	private $_assetManager;

	protected function setUp(): void
	{
		set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline): bool {
			if (!(error_reporting() & $errno)) {
				return false;
			}
			throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
		});

		$app = Prado::getApplication();
		$this->_service = $app->getService();
		$this->_assetManager = PradoUnit::getProp($app, '_assetManager');
		$app->setService(new TPageService());

		$dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'prado-activerecord-scaffold-assets';
		if (!is_dir($dir)) {
			mkdir($dir, 0o777, true);
		}
		Prado::setPathOfAlias('ScaffoldTestAssets', $dir);
		$assets = new TAssetManager();
		$assets->setBasePath('ScaffoldTestAssets');
		$assets->setBaseUrl('/assets');
		$assets->init(null);
		$app->setAssetManager($assets);

		ScaffoldFixture::reset();

		$this->page = new ScaffoldTestPage();
		$this->page->getControls()->add(new THead());
		$this->form = new TForm();
		$this->form->setID('form');
		$this->page->getControls()->add($this->form);
	}

	protected function tearDown(): void
	{
		$app = Prado::getApplication();
		$app->setService($this->_service);
		PradoUnit::setProp($app, '_assetManager', $this->_assetManager);
		restore_error_handler();
	}

	/**
	 * Adds a control to the page form.
	 * @template T of TControl
	 * @param T $control
	 * @return T
	 */
	protected function add(TControl $control): TControl
	{
		$this->form->getControls()->add($control);
		return $control;
	}

	protected function command(string $name, $parameter = null): TCommandEventParameter
	{
		return new TCommandEventParameter($name, $parameter);
	}

	protected function itemCommand(TRepeaterItem $item, string $name): TRepeaterCommandEventParameter
	{
		return new TRepeaterCommandEventParameter($item, $this, $this->command($name));
	}

	/**
	 * @return string[] the URLs of the registered style sheet files.
	 */
	protected function styleSheets(): array
	{
		return array_values(PradoUnit::getProp($this->page->getClientScript(), '_styleSheetFiles'));
	}
}

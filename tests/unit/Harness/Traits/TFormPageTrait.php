<?php

/**
 * TFormPageTrait file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Test\Unit\Harness\Traits;

use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\TClientScriptManager;
use Prado\Web\UI\TControl;
use Prado\Web\UI\TForm;
use Prado\Web\UI\TPage;

/**
 * TFormPageTrait builds a page with a form and a client script manager.
 *
 * Client script options read the form's client ID, and client script
 * registration needs a client script manager. A bare {@see TPage} has neither
 * outside a page service.
 *
 * @since 4.4.0
 */
trait TFormPageTrait
{
	/**
	 * Builds a page with a form with the ID `Form` and a client script manager,
	 * and adds the controls to the page.
	 * @param TControl ...$controls the controls to add to the page.
	 * @return TPage the page.
	 */
	protected function createFormPage(TControl ...$controls): TPage
	{
		$page = new TPage();
		PradoUnit::setProp($page, '_clientScript', new TClientScriptManager($page));
		$form = new TForm();
		$form->setID('Form');
		$page->getControls()->add($form);
		$page->setForm($form);
		foreach ($controls as $control) {
			$page->getControls()->add($control);
		}
		return $page;
	}

	/**
	 * Returns the end script that the page registers with the key.
	 * @param TPage $page the page.
	 * @param string $key the end script key.
	 * @return ?string the script, or null when no script has the key.
	 */
	protected function getEndScript(TPage $page, string $key): ?string
	{
		return PradoUnit::getProp($page->getClientScript(), '_endScripts')[$key] ?? null;
	}
}

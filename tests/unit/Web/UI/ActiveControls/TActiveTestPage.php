<?php

/**
 * TActiveTestPage class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Test\Unit\Web\UI\ActiveControls;

use Prado\Test\Unit\Harness\Web\UI\TTestCallbackPage;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\TControl;
use Prado\Web\UI\TForm;

/**
 * TActiveTestPage is a callback test page with a form, a recording client script
 * manager ({@see TActiveTestClientScript}), and a recording page adapter
 * ({@see TActiveTestPageAdapter}).
 *
 * {@see addControl} adds a control to the form and sets its control stage, so the
 * control updates the client in a callback once the stage is at least
 * CS_CHILD_INITIALIZED, and has loaded its post data at CS_STATE_LOADED.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TActiveTestPage extends TTestCallbackPage
{
	/**
	 * @var TActiveTestClientScript the client script manager of the page
	 */
	public TActiveTestClientScript $cs;

	/**
	 * @var TActiveTestPageAdapter the page adapter
	 */
	public TActiveTestPageAdapter $pageAdapter;

	/**
	 * @var TForm the form of the page
	 */
	public TForm $form;

	/**
	 * @param bool $callback whether the page reports a callback request
	 */
	public function __construct(bool $callback = true)
	{
		parent::__construct();
		$this->callback = $callback;
		$this->cs = new TActiveTestClientScript($this);
		PradoUnit::setProp($this, '_clientScript', $this->cs);
		$this->pageAdapter = new TActiveTestPageAdapter($this);
		$this->setAdapter($this->pageAdapter);
		$this->form = new TForm();
		$this->form->setID('Form');
		$this->getControls()->add($this->form);
		$this->setForm($this->form);
	}

	/**
	 * Adds the control to the form with the ID and sets its control stage.
	 * @param TControl $control the control
	 * @param string $id the control ID
	 * @param int $stage the control stage
	 * @return TControl the control
	 */
	public function addControl(TControl $control, string $id, int $stage = TControl::CS_LOADED): TControl
	{
		$control->setID($id);
		$this->form->getControls()->add($control);
		PradoUnit::invoke($control, 'setControlStage', $stage);
		return $control;
	}
}

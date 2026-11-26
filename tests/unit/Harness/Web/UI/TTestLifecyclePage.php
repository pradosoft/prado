<?php

/**
 * TTestLifecyclePage class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Test\Unit\Harness\Web\UI;

use Prado\Web\UI\TControl;
use Prado\Web\UI\TForm;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TButton;
use Prado\Web\UI\WebControls\TTextBox;

/**
 * TTestLifecyclePage is a page whose controls a test builds in code, inside a form
 * with the ID `Form`.
 *
 * The page keeps its state in {@see TTestPageStatePersister} and has CSRF validation
 * off, so a test runs it once for a GET and again for a postback or callback.
 * {@see $build} creates the controls on every run; {@see $load} and {@see $preRender}
 * run at those stages. {@see $events} collects what the controls record.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TTestLifecyclePage extends TPage
{
	/**
	 * @var array the events the controls record
	 */
	public array $events = [];

	/**
	 * @var ?callable builds the controls of the form, receives the page and the form
	 */
	public $build;

	/**
	 * @var ?callable runs on load, receives the page
	 */
	public $load;

	/**
	 * @var ?callable runs on pre-render, receives the page
	 */
	public $preRender;

	/**
	 * Turns CSRF validation off and keeps the page state in {@see TTestPageStatePersister}.
	 */
	public function __construct()
	{
		parent::__construct();
		$this->setEnableCsrfValidation(false);
		$this->setStatePersisterClass(TTestPageStatePersister::class);
	}

	/**
	 * Creates the form and runs {@see $build}.
	 */
	public function createChildControls()
	{
		$form = new TForm();
		$form->setID('Form');
		$this->getControls()->add($form);
		if ($this->build) {
			($this->build)($this, $form);
		}
	}

	/**
	 * Runs {@see $load} after the page loads.
	 * @param \Prado\TEventParameter $param the event parameter
	 */
	public function onLoad($param)
	{
		parent::onLoad($param);
		if ($this->load) {
			($this->load)($this);
		}
	}

	/**
	 * Runs {@see $preRender} after the page pre-renders.
	 * @param \Prado\TEventParameter $param the event parameter
	 */
	public function onPreRender($param)
	{
		parent::onPreRender($param);
		if ($this->preRender) {
			($this->preRender)($this);
		}
	}

	/**
	 * Adds a text box that records its changes as `changed:ID=text`.
	 * @param TControl $parent the parent of the text box
	 * @param string $id the ID of the text box
	 * @return TTextBox the text box
	 */
	public function addTextBox(TControl $parent, string $id): TTextBox
	{
		$box = new TTextBox();
		$box->setID($id);
		$box->attachEventHandler('OnTextChanged', fn () => $this->events[] = 'changed:' . $id . '=' . $box->getText());
		$parent->getControls()->add($box);
		return $box;
	}

	/**
	 * Adds a button that records its clicks as `click:ID`.
	 * @param TControl $parent the parent of the button
	 * @param string $id the ID of the button
	 * @return TButton the button
	 */
	public function addButton(TControl $parent, string $id): TButton
	{
		$button = new TButton();
		$button->setID($id);
		$button->attachEventHandler('OnClick', fn () => $this->events[] = 'click:' . $id);
		$parent->getControls()->add($button);
		return $button;
	}
}

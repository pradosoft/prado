<?php

/**
 * TActiveWebTemplate class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\ActiveControls;

use Prado\TPropertyValue;
use Prado\Web\UI\TControl;
use Prado\Web\UI\WebControls\TWebTemplate;

/**
 * TActiveWebTemplate class.
 *
 * TActiveWebTemplate is the active control counterpart to {@see TWebTemplate}.
 * It drives the client-side template from a callback: stamping copies, updating
 * the copies already on the page, and replacing the template markup itself.
 * Each method issues a client-side command when
 * {@see \Prado\Web\UI\ActiveControls\TBaseActiveControl::setEnableUpdate
 * ActiveControl.EnableUpdate} is `true` (the default), and does nothing outside
 * a callback.
 *
 * Stamping methods:
 * - {@see stampInto} — append a copy to a target.
 * - {@see prependInto} — insert a copy as a target's first children.
 * - {@see replaceContentOf} — replace a target's children with one copy.
 * - {@see repeatInto} — one copy per row of data.
 *
 * Instance methods take the UID of a stamped copy:
 * - {@see updateInstance} / {@see updateAll} — merge data and write it into the
 *   recorded placeholder positions, leaving everything else in the copy intact.
 * - {@see refreshInstance} / {@see refreshAll} — rebuild copies from the
 *   template's current markup, which discards state held in the old nodes.
 * - {@see removeInstance} — remove a copy from the page.
 *
 * {@see setContent} replaces the template markup; pair it with
 * {@see refreshAll} to rebuild the copies already on the page.
 *
 * A UID is allocated on the client, so the server learns it only when client
 * code sends it back, such as in a callback parameter. Read it from the
 * `pradoInstance` property of the array a stamping method returns, or from
 * `Prado.WebUI.TWebTemplate.instanceOf(node)` inside an event handler.
 *
 * ```php
 * public function row_added($sender, $param)
 * {
 *     $this->rowTemplate->stampInto('listBody', ['name' => 'Ada', 'id' => 7]);
 * }
 *
 * public function row_renamed($sender, $param)
 * {
 *     $this->rowTemplate->updateInstance($param->getCallbackParameter(), ['name' => 'Grace']);
 * }
 * ```
 *
 * The commands reach the client through the wrapper registered by
 * {@see TWebTemplate::getEnableClientScript EnableClientScript}, so they have no
 * effect when that property is disabled or when
 * {@see TWebTemplate::getShadowRootMode ShadowRootMode} is set.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 * @method TActiveControlAdapter getAdapter()
 */
class TActiveWebTemplate extends TWebTemplate implements IActiveControl
{
	/**
	 * Creates a new callback control, sets the adapter to
	 * TActiveControlAdapter. If you override this class, be sure to set the
	 * adapter appropriately by, for example, by calling this constructor.
	 */
	public function __construct()
	{
		parent::__construct();
		$this->setAdapter(new TActiveControlAdapter($this));
	}

	/**
	 * @return TBaseActiveControl basic active control options.
	 */
	public function getActiveControl()
	{
		return $this->getAdapter()->getBaseActiveControl();
	}

	/**
	 * Issues a command against the client-side template wrapper.
	 * @param string $method the wrapper method to invoke
	 * @param array $arguments the arguments to pass
	 */
	protected function callTemplateMethod(string $method, array $arguments = [])
	{
		if (!$this->getActiveControl()->canUpdateClientSide()) {
			return;
		}
		$this->getPage()->getCallbackClient()->callClientFunction(
			'Prado.WebUI.TWebTemplate.command',
			[$this, $method, $arguments]
		);
	}

	/**
	 * Resolves a stamping target into a client-side element ID.
	 * @param string|TControl $target the target control or element ID
	 * @return string the client-side element ID
	 */
	protected function resolveTarget($target): string
	{
		if ($target instanceof TControl) {
			return $target->getClientID();
		}
		return TPropertyValue::ensureString($target);
	}

	/**
	 * Appends a stamped copy of the content as the last children of the target.
	 * @param string|TControl $target the target control or element ID
	 * @param array $data values keyed by placeholder path
	 */
	public function stampInto($target, array $data = [])
	{
		$this->callTemplateMethod('appendTo', [$this->resolveTarget($target), $data]);
	}

	/**
	 * Inserts a stamped copy of the content as the first children of the target.
	 * @param string|TControl $target the target control or element ID
	 * @param array $data values keyed by placeholder path
	 */
	public function prependInto($target, array $data = [])
	{
		$this->callTemplateMethod('prependTo', [$this->resolveTarget($target), $data]);
	}

	/**
	 * Replaces all children of the target with a stamped copy of the content.
	 * @param string|TControl $target the target control or element ID
	 * @param array $data values keyed by placeholder path
	 */
	public function replaceContentOf($target, array $data = [])
	{
		$this->callTemplateMethod('replaceContentOf', [$this->resolveTarget($target), $data]);
	}

	/**
	 * Stamps one copy of the content per row of data.
	 * @param string|TControl $target the target control or element ID
	 * @param array $rows one data array per copy
	 * @param bool $keep whether to keep the existing children of the target
	 */
	public function repeatInto($target, array $rows, bool $keep = false)
	{
		$this->callTemplateMethod('repeatInto', [$this->resolveTarget($target), $rows, $keep]);
	}

	/**
	 * Merges values into a stamped copy and writes them into its recorded
	 * placeholder positions. Input, focus, and listeners inside the copy survive.
	 * @param string $uid the instance UID allocated on the client
	 * @param array $data values to merge into the instance data
	 */
	public function updateInstance(string $uid, array $data)
	{
		$this->callTemplateMethod('updateInstance', [$uid, $data]);
	}

	/**
	 * Merges the same values into every stamped copy of this template.
	 * @param array $data values to merge into each instance's data
	 */
	public function updateAll(array $data)
	{
		$this->callTemplateMethod('updateAll', [$data]);
	}

	/**
	 * Rebuilds a stamped copy from the template's current markup, keeping its
	 * UID and position. The replacement discards state held in the old nodes.
	 * @param string $uid the instance UID allocated on the client
	 * @param array $data values to merge before rebuilding
	 */
	public function refreshInstance(string $uid, array $data = [])
	{
		$this->callTemplateMethod('refreshInstance', [$uid, $data]);
	}

	/**
	 * Rebuilds every stamped copy from the template's current markup.
	 */
	public function refreshAll()
	{
		$this->callTemplateMethod('refreshAll');
	}

	/**
	 * Removes a stamped copy from the page.
	 * @param string $uid the instance UID allocated on the client
	 */
	public function removeInstance(string $uid)
	{
		$this->callTemplateMethod('removeInstance', [$uid]);
	}

	/**
	 * Replaces the template markup on the client. Copies already stamped keep
	 * the markup they were stamped from until {@see refreshAll} rebuilds them.
	 * @param string $html the markup to parse into the content fragment
	 * @param bool $refresh whether to rebuild the stamped copies afterwards
	 */
	public function setContent(string $html, bool $refresh = false)
	{
		$this->callTemplateMethod('setContent', [$html]);
		if ($refresh) {
			$this->refreshAll();
		}
	}

	/**
	 * Adds attribute id to the renderer so the client can locate the element.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 */
	protected function addAttributesToRender($writer)
	{
		$writer->addAttribute('id', $this->getClientID());
		parent::addAttributesToRender($writer);
	}
}

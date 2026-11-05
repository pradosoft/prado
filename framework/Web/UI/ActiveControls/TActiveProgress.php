<?php

/**
 * TActiveProgress class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\ActiveControls;

use Prado\TPropertyValue;
use Prado\Web\UI\WebControls\TProgress;

/**
 * TActiveProgress class.
 *
 * TActiveProgress is the active control counterpart to {@see TProgress}. The
 * {@see TProgress::setValue Value} and {@see TProgress::setMax Max} properties
 * can be changed server-side during a callback and the `<progress>` element is
 * updated on the client automatically when
 * {@see \Prado\Web\UI\ActiveControls\TBaseActiveControl::setEnableUpdate
 * ActiveControl.EnableUpdate} is `true` (the default). Setting
 * {@see TProgress::setValue Value} to `null` removes the `value` attribute,
 * returning the bar to its indeterminate state.
 *
 * A common pattern pairs TActiveProgress with a
 * {@see \Prado\Web\UI\ActiveControls\TTimeTriggeredCallback} polling a
 * server-side task for completion updates.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 * @method TActiveControlAdapter getAdapter()
 */
class TActiveProgress extends TProgress implements IActiveControl
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
	 * Sets how much of the task has completed. On callback response, the
	 * `value` attribute is updated on the client; a `null` value removes the
	 * attribute for an indeterminate bar.
	 * @param null|float|string $value the completed amount, 0 or greater;
	 *   null or empty string displays an indeterminate progress bar
	 */
	public function setValue($value)
	{
		$old = parent::getValue();
		parent::setValue($value);
		if ($old === $this->getValue()) {
			return;
		}
		if ($this->getActiveControl()->canUpdateClientSide()) {
			$cs = $this->getPage()->getCallbackClient();
			if (($value = $this->getValue()) === null) {
				$cs->removeAttribute($this, 'value');
			} else {
				$cs->setAttribute($this, 'value', TPropertyValue::ensureString($value));
			}
		}
	}

	/**
	 * Sets how much work the task requires in total. On callback response, the
	 * `max` attribute is updated on the client.
	 * @param float|string $value the total amount of work, greater than 0
	 */
	public function setMax($value)
	{
		$old = parent::getMax();
		parent::setMax($value);
		if ($old === $this->getMax()) {
			return;
		}
		if ($this->getActiveControl()->canUpdateClientSide()) {
			$this->getPage()->getCallbackClient()->setAttribute($this, 'max', TPropertyValue::ensureString($this->getMax()));
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

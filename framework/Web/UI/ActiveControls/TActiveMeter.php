<?php

/**
 * TActiveMeter class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\ActiveControls;

use Prado\TPropertyValue;
use Prado\Web\UI\WebControls\TMeter;

/**
 * TActiveMeter class.
 *
 * TActiveMeter is the active control counterpart to {@see TMeter}. The
 * {@see TMeter::setValue Value}, {@see TMeter::setMin Min},
 * {@see TMeter::setMax Max}, {@see TMeter::setLow Low},
 * {@see TMeter::setHigh High}, and {@see TMeter::setOptimum Optimum}
 * properties can be changed server-side during a callback and the `<meter>`
 * element is updated on the client automatically when
 * {@see \Prado\Web\UI\ActiveControls\TBaseActiveControl::setEnableUpdate
 * ActiveControl.EnableUpdate} is `true` (the default). Setting
 * {@see TMeter::setLow Low}, {@see TMeter::setHigh High}, or
 * {@see TMeter::setOptimum Optimum} to `null` removes the corresponding
 * attribute.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 * @method TActiveControlAdapter getAdapter()
 */
class TActiveMeter extends TMeter implements IActiveControl
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
	 * Updates an attribute on the client-side when its value changed during a
	 * callback. A `null` value removes the attribute.
	 * @param string $name the attribute name
	 * @param ?float $old the property value before the change
	 * @param ?float $new the property value after the change
	 */
	protected function updateClientAttribute(string $name, $old, $new)
	{
		if ($old === $new || !$this->getActiveControl()->canUpdateClientSide()) {
			return;
		}
		$cs = $this->getPage()->getCallbackClient();
		if ($new === null) {
			$cs->removeAttribute($this, $name);
		} else {
			$cs->setAttribute($this, $name, TPropertyValue::ensureString($new));
		}
	}

	/**
	 * Sets the measured value. On callback response, the `value` attribute is
	 * updated on the client.
	 * @param float|string $value the measured value
	 */
	public function setValue($value)
	{
		$old = parent::getValue();
		parent::setValue($value);
		$this->updateClientAttribute('value', $old, $this->getValue());
	}

	/**
	 * Sets the lower bound of the range. On callback response, the `min`
	 * attribute is updated on the client.
	 * @param float|string $value the lower bound of the range
	 */
	public function setMin($value)
	{
		$old = parent::getMin();
		parent::setMin($value);
		$this->updateClientAttribute('min', $old, $this->getMin());
	}

	/**
	 * Sets the upper bound of the range. On callback response, the `max`
	 * attribute is updated on the client.
	 * @param float|string $value the upper bound of the range
	 */
	public function setMax($value)
	{
		$old = parent::getMax();
		parent::setMax($value);
		$this->updateClientAttribute('max', $old, $this->getMax());
	}

	/**
	 * Sets the upper bound of the "low" segment. On callback response, the
	 * `low` attribute is updated on the client; null removes it.
	 * @param null|float|string $value the upper bound of the "low" segment
	 */
	public function setLow($value)
	{
		$old = parent::getLow();
		parent::setLow($value);
		$this->updateClientAttribute('low', $old, $this->getLow());
	}

	/**
	 * Sets the lower bound of the "high" segment. On callback response, the
	 * `high` attribute is updated on the client; null removes it.
	 * @param null|float|string $value the lower bound of the "high" segment
	 */
	public function setHigh($value)
	{
		$old = parent::getHigh();
		parent::setHigh($value);
		$this->updateClientAttribute('high', $old, $this->getHigh());
	}

	/**
	 * Sets the optimum point in the range. On callback response, the `optimum`
	 * attribute is updated on the client; null removes it.
	 * @param null|float|string $value the optimum point in the range
	 */
	public function setOptimum($value)
	{
		$old = parent::getOptimum();
		parent::setOptimum($value);
		$this->updateClientAttribute('optimum', $old, $this->getOptimum());
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

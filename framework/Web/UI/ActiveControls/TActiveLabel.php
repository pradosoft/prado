<?php

/**
 * TActiveLabel class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\ActiveControls;

/**
 * Load active control adapter.
 */
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Prado;
use Prado\Web\UI\WebControls\TLabel;

/**
 * TActiveLabel class
 *
 * The active control counterpart of TLabel component. When
 * {@see \Prado\Web\UI\ActiveControls\TBaseActiveControl::setEnableUpdate ActiveControl.EnableUpdate}
 * property is true the during a callback request, setting {@see setText Text}
 * property will also set the text of the label on the client upon callback
 * completion. Similarly, setting {@see setForControl ForControl} will also set
 * the client-side "for" attribute on the label.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @since 3.1
 * @method TActiveControlAdapter getAdapter()
 */
class TActiveLabel extends TLabel implements IActiveControl
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
	 * On callback response, the inner HTML of the label is updated, HTML-encoded when
	 * {@see getEncode Encode} is true.
	 * @param string $value the text value of the label
	 */
	public function setText($value)
	{
		if (parent::getText() === $value) {
			return;
		}

		parent::setText($value);
		if ($this->getActiveControl()->canUpdateClientSide()) {
			$this->getPage()->getCallbackClient()->update($this, $this->encodeText($value));
		}
	}

	/**
	 * Sets the ID of the control that the label is associated with.
	 * The control must be locatable via {@see \Prado\Web\UI\TControl::findControl} using the ID.
	 * On callback response, the For attribute of the label is updated. The label
	 * renders a `<label>` with an associated control and a `<span>` without one, so
	 * a change between an empty and a non-empty ID replaces the client element.
	 * @param string $value the associated control ID
	 * @throws TInvalidDataValueException if the ID locates no control in a callback
	 */
	public function setForControl($value)
	{
		if (($old = parent::getForControl()) === $value) {
			return;
		}

		parent::setForControl($value);
		if ($this->getActiveControl()->canUpdateClientSide()) {
			$control = ($value === '') ? null : $this->findControl($value);
			if ($value !== '' && $control === null) {
				throw new TInvalidDataValueException('label_associatedcontrol_invalid', $value);
			}
			if ($control !== null && $old !== '') {
				$this->getPage()->getCallbackClient()->setAttribute($this, 'for', $control->getClientID());
			} else {
				$this->getActiveControl()->replaceClientContent($this, $this);
			}
		}
	}

	/**
	 * Adds attribute id to the renderer.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 */
	protected function addAttributesToRender($writer)
	{
		$writer->addAttribute('id', $this->getClientID());
		parent::addAttributesToRender($writer);
	}
}

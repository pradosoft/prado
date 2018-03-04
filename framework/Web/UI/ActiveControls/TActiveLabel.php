<?php
/**
 * TActiveLabel class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @copyright Copyright &copy; 2005-2016 The PRADO Group
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\ActiveControls
 */

namespace Prado\Web\UI\ActiveControls;

/**
 * Load active control adapter.
 */
use Prado\Prado;
use Prado\Web\UI\WebControls\TLabel;

/**
 * TActiveLabel class
 *
 * The active control counterpart of TLabel component. When
 * {@link TBaseActiveControl::setEnableUpdate ActiveControl.EnableUpdate}
 * property is true the during a callback request, setting {@link setText Text}
 * property will also set the text of the label on the client upon callback
 * completion. Similarly, setting {@link setForControl ForControl} will also set
 * the client-side "for" attribute on the label.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @package Prado\Web\UI\ActiveControls
 * @since 3.1
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
	 * On callback response, the inner HTML of the label is updated.
	 * @param string $value the text value of the label
	 */
	public function setText($value)
	{
		if (parent::getText() === $value) {
			return;
		}

		parent::setText($value);
		if ($this->getActiveControl()->canUpdateClientSide()) {
			$this->getPage()->getCallbackClient()->update($this, $value);
		}
	}

	/**
	 * Sets the ID of the control that the label is associated with.
	 * The control must be locatable via {@link TControl::findControl} using the ID.
	 * On callback response, the For attribute of the label is updated.
	 * @param string $value the associated control ID
	 */
	public function setForControl($value)
	{
		if (parent::getForControl() === $value) {
			return;
		}

		parent::setForControl($value);
		if ($this->getActiveControl()->canUpdateClientSide()) {
			$id = $this->findControl($value)->getClientID();
			$this->getPage()->getCallbackClient()->setAttribute($this, 'for', $id);
		}
	}

	/**
	 * Adds attribute id to the renderer.
	 * @param THtmlWriter $writer the writer used for the rendering purpose
	 */
	protected function addAttributesToRender($writer)
	{
		$writer->addAttribute('id', $this->getClientID());
		parent::addAttributesToRender($writer);
	}
}

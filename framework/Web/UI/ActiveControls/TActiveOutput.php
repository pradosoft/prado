<?php

/**
 * TActiveOutput class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\ActiveControls;

use Prado\TPropertyValue;
use Prado\Web\THttpUtility;
use Prado\Web\UI\WebControls\TOutput;

/**
 * TActiveOutput class.
 *
 * TActiveOutput is the active control counterpart of {@see TOutput}. During a
 * callback, setting {@see setText Text} replaces the content of the client
 * `<output>`, and setting {@see setForControl ForControl} updates its `for`
 * attribute. The update requires
 * {@see \Prado\Web\UI\ActiveControls\TBaseActiveControl::setEnableUpdate ActiveControl.EnableUpdate}.
 * ```html
 * <com:TActiveTextBox ID="Price" TextMode="Number" AutoPostBack="true"
 *     OnCallback="calculate" />
 * <com:TActiveTextBox ID="Quantity" TextMode="Number" AutoPostBack="true"
 *     OnCallback="calculate" />
 * <com:TLabel ForControl="Total" Text="Total" />
 * <com:TActiveOutput ID="Total" ForControl="Price Quantity" />
 * ```
 * ```php
 * public function calculate($sender, $param)
 * {
 *     $this->Total->setText(sprintf('%.2f', $this->Price->getText() * $this->Quantity->getText()));
 * }
 * ```
 *
 * Accessibility: the `<output>` is a polite live region, so screen readers
 * announce the new result when the callback completes. The element renders
 * when its Text is empty, so it is in the page before the result arrives.
 * See {@see TOutput} for the attributes not to add.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 * @method TActiveControlAdapter getAdapter()
 */
class TActiveOutput extends TOutput implements IActiveControl
{
	/**
	 * Creates a new active control and sets the adapter to TActiveControlAdapter.
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
	 * @return bool true; the client locates the element by its ID
	 */
	public function getEnsureId()
	{
		return true;
	}

	/**
	 * Sets the result text. On callback response, the HTML-encoded text
	 * replaces the content of the client element.
	 * @param string $value the result text
	 */
	public function setText($value)
	{
		$value = TPropertyValue::ensureString($value);
		if (parent::getText() === $value) {
			return;
		}
		parent::setText($value);
		if ($this->getActiveControl()->canUpdateClientSide()) {
			$this->getPage()->getCallbackClient()->update($this, THttpUtility::htmlEncode($value));
		}
	}

	/**
	 * Sets the IDs of the controls the result derives from. On callback
	 * response, the `for` attribute of the client element is updated, or
	 * removed when no visible control remains.
	 * @param string $value the control IDs, separated by spaces or commas
	 */
	public function setForControl($value)
	{
		$old = parent::getForControl();
		parent::setForControl($value);
		if ($old === parent::getForControl() || !$this->getActiveControl()->canUpdateClientSide()) {
			return;
		}
		$client = $this->getPage()->getCallbackClient();
		if (($for = $this->getForAttributeValue()) !== '') {
			$client->setAttribute($this, 'for', $for);
		} else {
			$client->removeAttribute($this, 'for');
		}
	}
}

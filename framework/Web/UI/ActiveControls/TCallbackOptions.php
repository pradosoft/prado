<?php
/**
 * TCallbackOptions component class file.
 *
 * @author Wei Zhuo <weizhuo[at]gamil[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\ActiveControls
 */

namespace Prado\Web\UI\ActiveControls;

use Prado\Prado;
use Prado\Web\UI\TControl;

/**
 * TCallbackOptions class.
 *
 * TCallbackOptions allows common set of callback client-side options
 * to be attached to other active controls.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @package Prado\Web\UI\ActiveControls
 * @since 3.1
 */
class TCallbackOptions extends TControl
{
	/**
	 * @var TCallbackClientSide client side callback options.
	 */
	private $_clientSide;

	/**
	 * Callback client-side options can be set by setting the properties of
	 * the ClientSide property. E.g. <com:TCallbackOptions ClientSide.OnSuccess="..." />
	 * See {@link TCallbackClientSide} for details on the properties of
	 * ClientSide.
	 * @return TCallbackClientSide client-side callback options.
	 */
	public function getClientSide()
	{
		if ($this->_clientSide === null) {
			$this->_clientSide = $this->createClientSide();
		}
		return $this->_clientSide;
	}

	/**
	 * @return TCallbackClientSide callback client-side options.
	 */
	protected function createClientSide()
	{
		return new TCallbackClientSide;
	}
}

<?php

/**
 * TTestCallbackPage class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Test\Unit\Harness\Web\UI;

use Prado\Web\UI\ActiveControls\TCallbackClientScript;
use Prado\Web\UI\TPage;

/**
 * TTestCallbackPage is a TPage whose callback state a test sets, without a request.
 *
 * {@see getCallbackClient} returns one TCallbackClientScript for the page, so a
 * test reads the client functions an active control queued through
 * {@see getClientFunctions}. An active control updates the client only when its
 * control stage is at least CS_CHILD_INITIALIZED.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TTestCallbackPage extends TPage
{
	/**
	 * @var bool whether the page reports a callback request
	 */
	public bool $callback = true;

	/**
	 * @var ?TCallbackClientScript the callback client of the page
	 */
	private ?TCallbackClientScript $_callbackClient = null;

	/**
	 * @return bool whether the page reports a callback request
	 */
	public function getIsCallback()
	{
		return $this->callback;
	}

	/**
	 * @return TCallbackClientScript the one callback client of the page
	 */
	public function getCallbackClient()
	{
		return $this->_callbackClient ??= new TCallbackClientScript();
	}

	/**
	 * @return array the queued client functions, each `[functionName => params]`
	 */
	public function getClientFunctions(): array
	{
		return $this->getCallbackClient()->getClientFunctionsToExecute();
	}
}

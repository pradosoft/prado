<?php

use Prado\Web\UI\ActiveControls\TActiveButton;
use Prado\Web\UI\ActiveControls\TActiveLinkButton;
use Prado\Web\UI\ActiveControls\TCallbackEventParameter;
use Prado\Web\UI\WebControls\TButton;
use Prado\Web\UI\WebControls\TLinkButton;
use PHPUnit\Framework\TestCase;

/**
 * Covers the event parameter that raisePostBackEvent() hands to OnClick.
 *
 * A button click carries no payload of its own, so OnClick is raised with null
 * whatever raisePostBackEvent() receives: the postback parameter string, the
 * TCallbackEventParameter an active subclass forwards, or null. A handler reads
 * the client-supplied value from OnCallback, which receives that parameter, and
 * OnCommand carries the command properties of the button.
 */
class TButtonPostBackEventTest extends TestCase
{
	/**
	 * Attaches an OnClick handler and returns the raised parameter by reference.
	 * @param mixed $button the button control
	 * @param mixed $raised receives the parameter passed to the handler
	 */
	private function captureClick($button, &$raised): void
	{
		$raised = 'not-raised';
		$button->attachEventHandler('OnClick', function ($sender, $param) use (&$raised) {
			$raised = $param;
		});
	}

	public static function buttonProvider(): array
	{
		return [
			'TButton' => [TButton::class],
			'TLinkButton' => [TLinkButton::class],
			'TActiveButton' => [TActiveButton::class],
			'TActiveLinkButton' => [TActiveLinkButton::class],
		];
	}

	/**
	 * The callback parameter is not click data, so it stops at raisePostBackEvent().
	 * Forwarding it would make the OnClick parameter depend on the request type and
	 * would hand OnClick the response channel of the callback.
	 * @dataProvider buttonProvider
	 */
	public function testCallbackEventParameterDoesNotReachOnClick(string $class)
	{
		$button = new $class();
		$button->setCausesValidation(false);
		$this->captureClick($button, $raised);

		$button->raisePostBackEvent($this->createMock(TCallbackEventParameter::class));

		$this->assertNull($raised);
	}

	/**
	 * @dataProvider buttonProvider
	 */
	public function testPostBackParameterStringDoesNotReachOnClick(string $class)
	{
		$button = new $class();
		$button->setCausesValidation(false);
		$this->captureClick($button, $raised);

		$button->raisePostBackEvent('raw postback parameter');

		$this->assertNull($raised);
	}

	/**
	 * @dataProvider buttonProvider
	 */
	public function testNullParameterReachesOnClickAsNull(string $class)
	{
		$button = new $class();
		$button->setCausesValidation(false);
		$this->captureClick($button, $raised);

		$button->raisePostBackEvent(null);

		$this->assertNull($raised);
	}

	/**
	 * @dataProvider buttonProvider
	 */
	public function testOnCommandStillReceivesACommandParameter(string $class)
	{
		$button = new $class();
		$button->setCausesValidation(false);
		$button->setCommandName('doThing');
		$button->setCommandParameter('42');

		$raised = null;
		$button->attachEventHandler('OnCommand', function ($sender, $param) use (&$raised) {
			$raised = $param;
		});
		$button->raisePostBackEvent($this->createMock(TCallbackEventParameter::class));

		$this->assertInstanceOf(\Prado\Web\UI\TCommandEventParameter::class, $raised);
		$this->assertSame('doThing', $raised->getCommandName());
		$this->assertSame('42', $raised->getCommandParameter());
	}
}

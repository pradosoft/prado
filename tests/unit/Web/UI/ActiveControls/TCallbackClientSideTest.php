<?php

namespace Prado\Test\Unit\Web\UI\ActiveControls;

use PHPUnit\Framework\TestCase;
use Prado\Web\Javascripts\TJavaScriptLiteral;
use Prado\Web\UI\ActiveControls\TCallbackClientSide;
use Prado\Web\UI\ActiveControls\TCallbackOptions;
use Prado\Web\UI\WebControls\TLabel;

/**
 * The client-side options of a callback: the event handlers, the request options,
 * and {@see TCallbackOptions}, which holds a set of them for other controls.
 */
class TCallbackClientSideTest extends TestCase
{
	public static function handlerProvider(): array
	{
		return [
			'OnPreDispatch' => ['OnPreDispatch', 'onPreDispatch'],
			'OnUninitialized' => ['OnUninitialized', 'onUninitialized'],
			'OnLoading' => ['OnLoading', 'onLoading'],
			'OnLoaded' => ['OnLoaded', 'onLoaded'],
			'OnInteractive' => ['OnInteractive', 'onInteractive'],
			'OnComplete' => ['OnComplete', 'onComplete'],
			'OnSuccess' => ['OnSuccess', 'onSuccess'],
			'OnFailure' => ['OnFailure', 'onFailure'],
			'OnException' => ['OnException', 'onException'],
		];
	}

	/**
	 * @dataProvider handlerProvider
	 */
	public function testHandlerWrapsTheCodeInAFunction(string $property, string $option)
	{
		$client = new TCallbackClientSide();
		$this->assertNull($client->{'get' . $property}());
		$client->{'set' . $property}('go();');
		$value = $client->{'get' . $property}();
		$this->assertInstanceOf(TJavaScriptLiteral::class, $value);
		$this->assertSame('function(sender, parameter){ go(); }', (string) $value);
		$this->assertSame($value, $client->getOptions()->itemAt($option));
	}

	public function testHandlerKeepsAJavaScriptLiteral()
	{
		$client = new TCallbackClientSide();
		$literal = new TJavaScriptLiteral('handler');
		$client->setOnSuccess($literal);
		$this->assertSame($literal, $client->getOnSuccess());
	}

	public function testRequestOptionsAreTyped()
	{
		$client = new TCallbackClientSide();
		$this->assertNull($client->getPostState());
		$this->assertNull($client->getRequestTimeOut());
		$this->assertNull($client->getRetryLimit());
		$this->assertNull($client->getPostBackParameter());
		$client->setPostState('false');
		$client->setRequestTimeOut('3000');
		$client->setRetryLimit('2');
		$client->setPostBackParameter('p');
		$this->assertFalse($client->getPostState());
		$this->assertSame(3000, $client->getRequestTimeOut());
		$this->assertSame(2, $client->getRetryLimit());
		$this->assertSame('p', $client->getPostBackParameter());
		$this->assertSame(
			['PostInputs' => false, 'RequestTimeOut' => 3000, 'RetryLimit' => 2, 'EventParameter' => 'p'],
			$client->getOptions()->toArray()
		);
	}

	public function testEnablePageStateUpdateDefaultsToTrue()
	{
		$client = new TCallbackClientSide();
		$this->assertTrue($client->getEnablePageStateUpdate());
		$client->setEnablePageStateUpdate('false');
		$this->assertFalse($client->getEnablePageStateUpdate());
	}

	public function testPostBackTargetTakesAControlOrAnID()
	{
		$client = new TCallbackClientSide();
		$this->assertNull($client->getPostBackTarget());
		$label = new TLabel();
		$label->setID('Target');
		$client->setPostBackTarget($label);
		$this->assertSame($label->getUniqueID(), $client->getPostBackTarget());
		$client->setPostBackTarget('Other');
		$this->assertSame('Other', $client->getPostBackTarget());
	}

	public function testCallbackOptionsCreatesItsClientSideOnce()
	{
		$options = new TCallbackOptions();
		$client = $options->getClientSide();
		$this->assertInstanceOf(TCallbackClientSide::class, $client);
		$this->assertSame($client, $options->getClientSide());
	}
}

<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Web\Javascripts\TJavaScriptLiteral;
use Prado\Web\UI\WebControls\TValidatorClientSide;

class TValidatorClientSideTest extends TestCase
{
	public function testEventsAreNullUntilSet()
	{
		$clientSide = new TValidatorClientSide();
		$this->assertNull($clientSide->getOnValidate());
		$this->assertNull($clientSide->getOnValidationSuccess());
		$this->assertNull($clientSide->getOnValidationError());
		$this->assertSame(0, $clientSide->getOptions()->getCount());
	}

	public static function provideEvents(): array
	{
		return [
			'OnValidate' => ['OnValidate'],
			'OnValidationSuccess' => ['OnValidationSuccess'],
			'OnValidationError' => ['OnValidationError'],
		];
	}

	/**
	 * @param string $event the client event.
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('provideEvents')]
	public function testEventWrapsTheScriptInAFunction(string $event)
	{
		$clientSide = new TValidatorClientSide();
		$clientSide->{'set' . $event}('alert(1);');
		$function = $clientSide->{'get' . $event}();
		$this->assertInstanceOf(TJavaScriptLiteral::class, $function);
		$this->assertSame('function(sender, parameter){ alert(1); }', (string) $function);
		$this->assertSame($function, $clientSide->getOptions()->itemAt($event));

		$literal = new TJavaScriptLiteral('handler');
		$clientSide->{'set' . $event}($literal);
		$this->assertSame($literal, $clientSide->{'get' . $event}(), 'A JavaScript literal is used as is.');
	}

	public function testObserveChanges()
	{
		$clientSide = new TValidatorClientSide();
		$this->assertTrue($clientSide->getObserveChanges());
		$clientSide->setObserveChanges('false');
		$this->assertFalse($clientSide->getObserveChanges());
		$this->assertSame(['ObserveChanges' => false], $clientSide->getOptions()->toArray());
		$clientSide->setObserveChanges(true);
		$this->assertTrue($clientSide->getObserveChanges());
	}
}

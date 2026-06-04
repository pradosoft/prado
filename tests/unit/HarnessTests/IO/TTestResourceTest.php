<?php

/**
 * TTestResourceTest class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

use Prado\IO\IResource;
use Prado\IO\TResource;

/**
 * Tests for the {@see TTestResource} harness — the instantiable TResource used to
 * exercise the abstract base.  Pins its instrumentation contract (close-call counting
 * and forced result), which the TResource unit tests depend on.
 *
 * @package System.HarnessTests.IO
 */
class TTestResourceTest extends PHPUnit\Framework\TestCase
{
	public function testIsConcreteTResource(): void
	{
		$r = new TTestResource();
		$this->assertInstanceOf(TResource::class, $r);
		$this->assertInstanceOf(IResource::class, $r);
		$this->assertFalse($r->isOpen());
	}

	public function testCloseResourceCalledWhenOwned(): void
	{
		$r = new TTestResource(TTestIOHelper::memoryResource());
		$this->assertSame(0, $r->closeResourceCalls);
		$this->assertTrue($r->closeStream());
		$this->assertSame(1, $r->closeResourceCalls);
		$this->assertFalse($r->isOpen());
	}

	public function testCloseResourceNotCalledWhenBorrowed(): void
	{
		$res = TTestIOHelper::memoryResource();
		$r = new TTestResource();
		$r->attachResource($res, false);   // borrowed: not owned
		$result = $r->closeStream();
		$this->assertSame(0, $r->closeResourceCalls);   // primitive skipped for a borrowed handle
		$this->assertFalse($result);
		$this->assertTrue(is_resource($res));            // the borrowed handle stays open
		TTestIOHelper::closeAny($res);
	}

	public function testForcedCloseResourceReturn(): void
	{
		$r = new TTestResource(TTestIOHelper::memoryResource());
		$r->closeResourceReturn = false;
		$this->assertFalse($r->closeStream());   // forced failure result
		$this->assertSame(1, $r->closeResourceCalls);
		$this->assertFalse($r->isOpen());
	}
}

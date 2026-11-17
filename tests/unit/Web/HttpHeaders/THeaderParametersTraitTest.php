<?php

/**
 * THeaderParametersTraitTest
 *
 * Unit tests for {@see \Prado\Web\HttpHeaders\THeaderParametersTrait}.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 */

namespace Prado\Test\Unit\Web\HttpHeaders;

use Prado\Web\HttpHeaders\THeaderParametersTrait;

/**
 * A header value object built only from the trait.
 */
class THeaderParametersTraitTestValue implements \ArrayAccess
{
	use THeaderParametersTrait;
}

class THeaderParametersTraitTest extends \PHPUnit\Framework\TestCase
{
	private THeaderParametersTraitTestValue $obj;

	protected function setUp(): void
	{
		$this->obj = new THeaderParametersTraitTestValue();
	}

	// -----------------------------------------------------------------------
	// setParameters — string form
	// -----------------------------------------------------------------------

	public function testSetParametersStringKeepsSemicolonInQuotedValue(): void
	{
		$this->obj->setParameters('filename="a;b.pdf"; charset=UTF-8');
		$this->assertSame(['filename' => 'a;b.pdf', 'charset' => 'UTF-8'], $this->obj->getParameters());
	}

	public function testSetParametersStringKeepsSemicolonInSingleQuotedValue(): void
	{
		$this->obj->setParameters("name='x;y'; b=1");
		$this->assertSame(['name' => 'x;y', 'b' => '1'], $this->obj->getParameters());
	}

	public function testSetParametersStringRemovesBackslashEscapesInDoubleQuotes(): void
	{
		$this->obj->setParameters('filename="say \\"hi\\" \\\\ now"');
		$this->assertSame('say "hi" \\ now', $this->obj->getParameter('filename'));
	}

	public function testSetParametersStringSkipsEmptyValues(): void
	{
		$this->obj->setParameters('a=; b=""; c=1');
		$this->assertSame(['c' => '1'], $this->obj->getParameters());
	}

	public function testSetParametersStringTrimsUnpairedQuotes(): void
	{
		$this->obj->setParameters('a="open; b=x\'');
		$this->assertSame(['a' => 'open', 'b' => 'x'], $this->obj->getParameters());
	}

	public function testSetParametersStringSkipsPartsWithoutAssignment(): void
	{
		$this->obj->setParameters('; bare; Charset = UTF-8 ;');
		$this->assertSame(['charset' => 'UTF-8'], $this->obj->getParameters());
	}

	public function testContentDispositionRoundTripsQuotedFilename(): void
	{
		$cd = new \Prado\Web\TContentDisposition('attachment');
		$cd->setFilename('my "q;1" \\ file.pdf');
		$parsed = new \Prado\Web\TContentDisposition((string) $cd);
		$this->assertSame('my "q;1" \\ file.pdf', $parsed->getFilename());
	}

	// -----------------------------------------------------------------------
	// setParameters — array form
	// -----------------------------------------------------------------------

	public function testSetParametersArraySkipsEmptyValues(): void
	{
		$this->obj->setParameters([' A ' => 'x', 'b' => '', 'c' => null, 'd' => 0]);
		$this->assertSame(['a' => 'x', 'd' => '0'], $this->obj->getParameters());
	}

	// -----------------------------------------------------------------------
	// Single parameters, clear, and ArrayAccess
	// -----------------------------------------------------------------------

	public function testSetGetHasAndRemoveParameter(): void
	{
		$this->obj->setParameter(' Charset ', 'UTF-8');
		$this->assertTrue($this->obj->hasParameter('CHARSET'));
		$this->assertSame('UTF-8', $this->obj->getParameter('charset'));
		$this->obj->setParameter('charset', '');
		$this->assertFalse($this->obj->hasParameter('charset'));
		$this->obj->setParameter('a', '1');
		$this->obj->removeParameter('A');
		$this->assertNull($this->obj->getParameter('a'));
	}

	public function testClearParameters(): void
	{
		$this->obj->setParameters(['a' => '1', 'b' => '2']);
		$this->obj->clearParameters();
		$this->assertSame([], $this->obj->getParameters());
	}

	public function testArrayAccess(): void
	{
		$this->obj['Charset'] = 'UTF-8';
		$this->assertTrue(isset($this->obj['charset']));
		$this->assertSame('UTF-8', $this->obj['CHARSET']);
		$this->assertNull($this->obj['missing']);
		unset($this->obj['charset']);
		$this->assertFalse(isset($this->obj['charset']));
	}
}

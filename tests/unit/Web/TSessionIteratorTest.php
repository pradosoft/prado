<?php

namespace Prado\Test\Unit\Web;

use Prado\Web\TSessionIterator;

class TSessionIteratorTest extends \PHPUnit\Framework\TestCase
{
	private bool $hadSession = false;
	private mixed $priorSession = null;

	protected function setUp(): void
	{
		$this->hadSession = isset($_SESSION);
		$this->priorSession = $_SESSION ?? null;
	}

	protected function tearDown(): void
	{
		if ($this->hadSession) {
			$_SESSION = $this->priorSession;
		} else {
			unset($_SESSION);
		}
	}

	public function testIteratesTheSessionValues()
	{
		$_SESSION = ['a' => 1, 'b' => 2, 'c' => 3];
		$this->assertSame(['a' => 1, 'b' => 2, 'c' => 3], iterator_to_array(new TSessionIterator()));
	}

	public function testSkipsKeysRemovedDuringIteration()
	{
		$_SESSION = ['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4];
		$iterator = new TSessionIterator();
		$iterator->rewind();
		$this->assertTrue($iterator->valid());
		$this->assertSame('a', $iterator->key());
		$this->assertSame(1, $iterator->current());
		unset($_SESSION['b'], $_SESSION['c']);
		$iterator->next();
		$this->assertSame('d', $iterator->key());
		$this->assertSame(4, $iterator->current());
		unset($_SESSION['a']);
		$iterator->next();
		$this->assertFalse($iterator->valid());
		$this->assertFalse($iterator->key());
	}

	public function testSkipsOneRemovedKey()
	{
		$_SESSION = ['a' => 1, 'b' => 2, 'c' => 3];
		$iterator = new TSessionIterator();
		$iterator->rewind();
		unset($_SESSION['b']);
		$iterator->next();
		$this->assertSame('c', $iterator->key());
		$this->assertSame(3, $iterator->current());
	}

	public function testSkipsRemovedTrailingKeysToTheEnd()
	{
		$_SESSION = ['a' => 1, 'b' => 2];
		$iterator = new TSessionIterator();
		$iterator->rewind();
		unset($_SESSION['b']);
		$iterator->next();
		$this->assertFalse($iterator->valid());
		$this->assertNull($iterator->current());
	}

	public function testKeepsNullValues()
	{
		$_SESSION = ['a' => null, 'b' => null, 'c' => 3];
		$this->assertSame(['a' => null, 'b' => null, 'c' => 3], iterator_to_array(new TSessionIterator()));
	}

	public function testRewindSkipsRemovedLeadingKeys()
	{
		$_SESSION = ['a' => 1, 'b' => 2];
		$iterator = new TSessionIterator();
		unset($_SESSION['a']);
		$this->assertSame(['b' => 2], iterator_to_array($iterator));
	}

	public function testEmptySession()
	{
		$_SESSION = [];
		$iterator = new TSessionIterator();
		$iterator->rewind();
		$this->assertFalse($iterator->valid());
	}
}

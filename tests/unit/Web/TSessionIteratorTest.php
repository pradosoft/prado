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
}

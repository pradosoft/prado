<?php

namespace Prado\Test\Unit\Util\Email\Shell;

class TShellEmailerLogBehaviorTest extends \PHPUnit\Framework\TestCase
{
	public function testConstruct()
	{
		$behavior = new \Prado\Util\Email\Shell\TShellEmailerLogBehavior();
		$this->assertNull($behavior->getOutputWriter());
	}

	public function testConstructWithWriter()
	{
		$writer = $this->createMock(\Prado\IO\ITextWriter::class);
		$behavior = new \Prado\Util\Email\Shell\TShellEmailerLogBehavior($writer);
		$this->assertSame($writer, $behavior->getOutputWriter());
	}

	public function testGetOutputWriter()
	{
		$writer = $this->createMock(\Prado\IO\ITextWriter::class);
		$behavior = new \Prado\Util\Email\Shell\TShellEmailerLogBehavior($writer);
		$this->assertSame($writer, $behavior->getOutputWriter());
	}

	public function testSetOutputWriter()
	{
		$writer1 = $this->createMock(\Prado\IO\ITextWriter::class);
		$writer2 = $this->createMock(\Prado\IO\ITextWriter::class);
		$behavior = new \Prado\Util\Email\Shell\TShellEmailerLogBehavior($writer1);
		$behavior->setOutputWriter($writer2);
		$this->assertSame($writer2, $behavior->getOutputWriter());
	}
}
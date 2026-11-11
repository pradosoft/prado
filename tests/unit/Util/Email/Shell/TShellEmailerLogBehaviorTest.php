<?php

/**
 * @package Prado\Util\Email\Tests
 */
class TShellEmailerLogBehaviorTest extends \PHPUnit\Framework\TestCase
{
	public function testConstruct()
	{
		$behavior = new \Prado\Util\Cron\TShellEmailerLogBehavior();
		$this->assertNull($behavior->getOutputWriter());
	}

	public function testConstructWithWriter()
	{
		$writer = $this->createMock(\Prado\IO\ITextWriter::class);
		$behavior = new \Prado\Util\Cron\TShellEmailerLogBehavior($writer);
		$this->assertSame($writer, $behavior->getOutputWriter());
	}

	public function testGetOutputWriter()
	{
		$writer = $this->createMock(\Prado\IO\ITextWriter::class);
		$behavior = new \Prado\Util\Cron\TShellEmailerLogBehavior($writer);
		$this->assertSame($writer, $behavior->getOutputWriter());
	}

	public function testSetOutputWriter()
	{
		$writer1 = $this->createMock(\Prado\IO\ITextWriter::class);
		$writer2 = $this->createMock(\Prado\IO\ITextWriter::class);
		$behavior = new \Prado\Util\Cron\TShellEmailerLogBehavior($writer1);
		$behavior->setOutputWriter($writer2);
		$this->assertSame($writer2, $behavior->getOutputWriter());
	}
}
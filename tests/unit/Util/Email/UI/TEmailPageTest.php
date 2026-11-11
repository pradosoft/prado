<?php

namespace Prado\Test\Unit\Util\Email\UI;

class TEmailPageTest extends \PHPUnit\Framework\TestCase
{
	public function testGetEmail()
	{
		$page = new \Prado\Util\Email\UI\TEmailPage();
		$this->assertInstanceOf(\Prado\Util\Email\TEmail::class, $page->getEmail());
	}

	public function testGetEmailerModule()
	{
		$page = new \Prado\Util\Email\UI\TEmailPage();
		$this->assertNull($page->getEmailerModule());
	}

	public function testSetEmailerModule()
	{
		$module = $this->createMock(\Prado\Util\Email\TEmailModule::class);
		$page = new \Prado\Util\Email\UI\TEmailPage();
		$page->setEmailerModule($module);
		$this->assertSame($module, $page->getEmailerModule());
	}
}
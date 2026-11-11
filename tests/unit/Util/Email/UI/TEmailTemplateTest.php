<?php

/**
 * @package Prado\Util\Email\Tests
 */
class TEmailTemplateTest extends \PHPUnit\Framework\TestCase
{
	public function testGetPublishStyle()
	{
		$template = new \Prado\Util\Email\UI\TEmailTemplate();
		$this->assertNull($template->getPublishStyle());
	}

	public function testSetPublishStyle()
	{
		$template = new \Prado\Util\Email\UI\TEmailTemplate();
		$template->setPublishStyle('URL');
		$this->assertEquals('URL', $template->getPublishStyle());
	}

	public function testGetAbsoluteUrl()
	{
		$template = new \Prado\Util\Email\UI\TEmailTemplate();
		$this->assertNull($template->getAbsoluteUrl());
	}

	public function testSetAbsoluteUrl()
	{
		$template = new \Prado\Util\Email\UI\TEmailTemplate();
		$template->setAbsoluteUrl('https://example.com');
		$this->assertEquals('https://example.com', $template->getAbsoluteUrl());
	}
}
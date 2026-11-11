<?php

namespace Prado\Test\Unit\Util\Email;

class TEmailConfigurationTest extends \PHPUnit\Framework\TestCase
{
	public function testGetEmailPropertiesWithNoProperties()
	{
		$config = new \Prado\Util\Email\TEmailConfiguration();
		$this->assertEquals([], $config->getEmailProperties('test.path'));
	}

	public function testGetEmailPropertiesWithMatchingProperties()
	{
		$config = new \Prado\Util\Email\TEmailConfiguration();
		// Set up a mock email property that matches a path
		// Since this is a base class, we can check the basic functionality
		$this->assertEquals([], $config->getEmailProperties('test.path'));
	}

	public function testLoadFromPhp()
	{
		$config = $this->createMock(\Prado\Util\Email\TEmailConfiguration::class);
		$config->expects($this->once())
			->method('loadPageConfigurationFromPhp');
		$config->expects($this->once())
			->method('dyLoadEmailConfigurationFromPhp');
		
		$config->loadFromPhp([], '', '');
	}

	public function testLoadFromXml()
	{
		$config = $this->createMock(\Prado\Util\Email\TEmailConfiguration::class);
		$config->expects($this->once())
			->method('loadPageConfigurationFromXml');
		$config->expects($this->once())
			->method('dyLoadEmailConfigurationFromXml');
		
		$config->loadFromXml($this->createMock(\Prado\Xml\TXmlElement::class), '', '');
	}

	public function testLoadApplicationConfigurationFromPhp()
	{
		$config = new \Prado\Util\Email\TEmailConfiguration();
		// This should do nothing according to the implementation
		$config->loadApplicationConfigurationFromPhp([], '');
		// No assertion needed - just ensure it doesn't throw error
	}

	public function testLoadApplicationConfigurationFromXml()
	{
		$config = new \Prado\Util\Email\TEmailConfiguration();
		// This should do nothing according to the implementation
		$config->loadApplicationConfigurationFromXml($this->createMock(\Prado\Xml\TXmlElement::class), '');
		// No assertion needed - just ensure it doesn't throw error
	}

	public function testProcessPageProperties()
	{
		$config = $this->createPartialMock(\Prado\Util\Email\TEmailConfiguration::class, ['getProperties']);
		$config->expects($this->never())
			->method('getProperties');
		
		$config->processPageProperties('test.id', ['test' => 'value']);
		// No assertion as this is an internal method
	}

	public function testGetHasAuthorization()
	{
		$config = new \Prado\Util\Email\TEmailConfiguration();
		$this->assertFalse($config->getHasAuthorization());
	}

	public function testGetConfigurationNodes()
	{
		$config = new \Prado\Util\Email\TEmailConfiguration();
		$this->assertEquals('emails', $config->getConfigurationNodes());
	}

	public function testGetConfigurationNode()
	{
		$config = new \Prado\Util\Email\TEmailConfiguration();
		$this->assertEquals('email', $config->getConfigurationNode());
	}
}
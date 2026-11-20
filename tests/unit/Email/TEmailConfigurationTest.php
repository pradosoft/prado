<?php

namespace Prado\Test\Unit\Email;

use PHPUnit\Framework\TestCase;
use Prado\Email\TEmailConfiguration;
use Prado\Exceptions\TConfigurationException;
use Prado\Xml\TXmlDocument;

class TEmailConfigurationTest extends TestCase
{
	public function testEmptyConfiguration()
	{
		$config = new TEmailConfiguration();
		$config->load(null);
		$this->assertSame([], $config->getEmailProperties('Help.Welcome'));
	}

	public function testLoadXml()
	{
		$xml = new TXmlDocument();
		$xml->loadFromString('<emails MasterClass="Application.Layout" Email.From="shop@example.com">'
			. '<email id="Help/*" Email.From="help@example.com" />'
			. '<email id="Help.Welcome" Email.List="welcome" MasterClass="Application.HelpLayout" />'
			. '</emails>');
		$config = new TEmailConfiguration();
		$config->load($xml);
		$this->assertSame(['MasterClass' => 'Application.Layout', 'Email.From' => 'shop@example.com'], $config->getEmailProperties('Order.Shipped'));
		$this->assertSame(['MasterClass' => 'Application.Layout', 'Email.From' => 'help@example.com'], $config->getEmailProperties('Help.Reset'));
		$this->assertSame(['MasterClass' => 'Application.HelpLayout', 'Email.From' => 'help@example.com', 'Email.List' => 'welcome'], $config->getEmailProperties('Help.Welcome'));
	}

	public function testLoadPhp()
	{
		$config = new TEmailConfiguration();
		$config->load(['properties' => ['Theme' => 'Mail'], 'rules' => ['*' => ['Email.ReplyTo' => 'reply@example.com'], 'Order.*' => ['Theme' => 'Orders']]]);
		$this->assertSame(['Theme' => 'Mail', 'Email.ReplyTo' => 'reply@example.com'], $config->getEmailProperties('Simple'));
		$this->assertSame(['Theme' => 'Orders', 'Email.ReplyTo' => 'reply@example.com'], $config->getEmailProperties('Order.Shipped'));
	}

	public function testRuleWithoutIdThrows()
	{
		$xml = new TXmlDocument();
		$xml->loadFromString('<emails><email Theme="Mail" /></emails>');
		$this->expectException(TConfigurationException::class);
		(new TEmailConfiguration())->load($xml);
	}

	public function testMatches()
	{
		$this->assertTrue(TEmailConfiguration::matches('*', 'Help.Welcome'));
		$this->assertTrue(TEmailConfiguration::matches('Help/*', 'Help.Welcome'));
		$this->assertTrue(TEmailConfiguration::matches('Help.*', 'Help/Welcome'));
		$this->assertTrue(TEmailConfiguration::matches('help.welcome', 'Help.Welcome'));
		$this->assertTrue(TEmailConfiguration::matches('*.Welcome', 'Help.Welcome'));
		$this->assertFalse(TEmailConfiguration::matches('Help/*', 'Helper.Welcome'));
		$this->assertFalse(TEmailConfiguration::matches('Help', 'Help.Welcome'));
		$this->assertFalse(TEmailConfiguration::matches('Order.*', 'Help.Welcome'));
		$this->assertFalse(TEmailConfiguration::matches('HelpxWelcome', 'Help.Welcome'));
		$this->assertFalse(TEmailConfiguration::matches('Help.Welcome', 'HelpxWelcome'));
	}
}

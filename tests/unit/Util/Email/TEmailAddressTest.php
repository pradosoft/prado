<?php

/**
 * @package Prado\Util\Email\Tests
 */
class TEmailAddressTest extends \PHPUnit\Framework\TestCase
{
	public function testConstructWithoutAddress()
	{
		$email = new \Prado\Util\Email\TEmailAddress();
		$this->assertNull($email->getName());
		$this->assertNull($email->getEmail());
	}

	public function testConstructWithEmailOnly()
	{
		$email = new \Prado\Util\Email\TEmailAddress('test@example.com');
		$this->assertNull($email->getName());
		$this->assertEquals('test@example.com', $email->getEmail());
	}

	public function testConstructWithNameAndEmail()
	{
		$email = new \Prado\Util\Email\TEmailAddress('test@example.com', 'Test User');
		$this->assertEquals('Test User', $email->getName());
		$this->assertEquals('test@example.com', $email->getEmail());
	}

	public function testGetName()
	{
		$email = new \Prado\Util\Email\TEmailAddress('test@example.com', 'Test User');
		$this->assertEquals('Test User', $email->getName());
	}

	public function testSetName()
	{
		$email = new \Prado\Util\Email\TEmailAddress('test@example.com');
		$email->setName('New User');
		$this->assertEquals('New User', $email->getName());
	}

	public function testGetEmail()
	{
		$email = new \Prado\Util\Email\TEmailAddress('test@example.com', 'Test User');
		$this->assertEquals('test@example.com', $email->getEmail());
	}

	public function testSetEmail()
	{
		$email = new \Prado\Util\Email\TEmailAddress('test@example.com', 'Test User');
		$email->setEmail('new@example.com');
		$this->assertEquals('new@example.com', $email->getEmail());
	}

	public function testParseSimpleEmail()
	{
		$email = new \Prado\Util\Email\TEmailAddress();
		$email->parse('test@example.com');
		$this->assertNull($email->getName());
		$this->assertEquals('test@example.com', $email->getEmail());
	}

	public function testParseEmailWithName()
	{
		$email = new \Prado\Util\Email\TEmailAddress();
		$email->parse('John <john@example.com>');
		$this->assertEquals('John', $email->getName());
		$this->assertEquals('john@example.com', $email->getEmail());
	}

	public function testParseEmailWithNameWithQuotes()
	{
		$email = new \Prado\Util\Email\TEmailAddress();
		$email->parse('"John Doe" <john@example.com>');
		$this->assertEquals('John Doe', $email->getName());
		$this->assertEquals('john@example.com', $email->getEmail());
	}

	public function testParseEmailWithNameAndNoBrackets()
	{
		$email = new \Prado\Util\Email\TEmailAddress();
		$email->parse('John Doe john@example.com');
		$this->assertEquals('John Doe', $email->getName());
		$this->assertEquals('john@example.com', $email->getEmail());
	}

	public function testParseArrayAddress()
	{
		$email = new \Prado\Util\Email\TEmailAddress();
		$email->parse(['john@example.com', 'John Doe']);
		$this->assertEquals('John Doe', $email->getName());
		$this->assertEquals('john@example.com', $email->getEmail());
	}

	public function testFormat()
	{
		$email = new \Prado\Util\Email\TEmailAddress('test@example.com', 'Test User');
		$this->assertEquals('"Test User" <test@example.com>', $email->format());
	}

	public function testFormatWithoutName()
	{
		$email = new \Prado\Util\Email\TEmailAddress('test@example.com');
		$this->assertEquals('<test@example.com>', $email->format());
	}

	public function testToString()
	{
		$email = new \Prado\Util\Email\TEmailAddress('test@example.com', 'Test User');
		$this->assertEquals('"Test User" <test@example.com>', (string)$email);
	}

	public function testToStringWithoutName()
	{
		$email = new \Prado\Util\Email\TEmailAddress('test@example.com');
		$this->assertEquals('<test@example.com>', (string)$email);
	}
}
<?php

/**
 * @package Prado\Util\Email\Tests
 */
class TEmailEventParameterTest extends \PHPUnit\Framework\TestCase
{
	public function testConstructWithoutParameters()
	{
		$parameter = new \Prado\Util\Email\TEmailEventParameter();
		$this->assertNull($parameter->getEmail());
		$this->assertNull($parameter->getMany());
	}

	public function testConstructWithEmail()
	{
		$email = $this->createMock(\Prado\Util\Email\TEmail::class);
		$parameter = new \Prado\Util\Email\TEmailEventParameter($email);
		$this->assertSame($email, $parameter->getEmail());
		$this->assertNull($parameter->getMany());
	}

	public function testConstructWithEmailAndMany()
	{
		$email = $this->createMock(\Prado\Util\Email\TEmail::class);
		$parameter = new \Prado\Util\Email\TEmailEventParameter($email, true);
		$this->assertSame($email, $parameter->getEmail());
		$this->assertTrue($parameter->getMany());
	}

	public function testGetEmail()
	{
		$email = $this->createMock(\Prado\Util\Email\TEmail::class);
		$parameter = new \Prado\Util\Email\TEmailEventParameter($email);
		$this->assertSame($email, $parameter->getEmail());
	}

	public function testSetEmail()
	{
		$email1 = $this->createMock(\Prado\Util\Email\TEmail::class);
		$email2 = $this->createMock(\Prado\Util\Email\TEmail::class);
		$parameter = new \Prado\Util\Email\TEmailEventParameter($email1);
		$parameter->setEmail($email2);
		$this->assertSame($email2, $parameter->getEmail());
	}

	public function testGetMany()
	{
		$parameter = new \Prado\Util\Email\TEmailEventParameter(null, true);
		$this->assertTrue($parameter->getMany());
	}

	public function testSetMany()
	{
		$parameter = new \Prado\Util\Email\TEmailEventParameter();
		$parameter->setMany(false);
		$this->assertFalse($parameter->getMany());
	}
}
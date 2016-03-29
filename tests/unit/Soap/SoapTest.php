<?php

ini_set("soap.wsdl_cache_enabled",0);

require_once(dirname(__FILE__).'/ContactManager.php');

/**
 * @package System.Soap
 */
class SoapTestCase extends PHPUnit_Framework_TestCase
{
	function getWsdlUri()
	{
		$script = str_replace('unit.php', 'ws.php',$_SERVER['SCRIPT_NAME']);
		return "http://".$_SERVER['HTTP_HOST'].$script.'?soap=contacts.wsdl';
	}

	function getClient()
	{
		return new SoapClient($this->getWsdlUri());
	}

	function testContactArray()
	{
		$result = $this->getClient()->getContacts();
		$this->assertEquals(count($result), 1);
		$obj = $result->Contact;
		$this->assertEquals($obj->name, "me");
		$this->assertEquals($obj->id, 1);
		$this->assertEquals($obj->address->street, "sesamstreet");
		$this->assertNull($obj->address->nr);
		$this->assertNull($obj->address->zipcode);
		$this->assertEquals($obj->address->city, "sesamcity");
		$this->assertEquals($obj->email, "me@you.com");
	}

	function testGetContactThrowsException()
	{
		try
		{
			$result = $this->getClient()->getContact(1);
			$this->fail();
		}
		catch (SoapFault $f)
		{
			$this->pass();
		}
	}

	function testGetNewContact()
	{
		$obj = $this->getClient()->newContact();
		$this->assertNull($obj->name);
		$this->assertNull($obj->id);
		$this->assertNull($obj->address);
		$this->assertNull($obj->email);
	}

	function testSaveContactReturnsTrue()
	{
		$c = new Contact;
		$result = $this->getClient()->saveContact($c);
		$this->assertTrue($result);
	}

	function getMixedArray()
	{
		$result = $this->getClient()>getList();
		$expected = array(array(1,2), array("12", 1.2));
		$this->assertEquals($result, $expected);
	}

	function testEmptyArray()
	{
		$result = $this->getClient()->getEmptyArray();
		$this->assertTrue(is_array($result));
		$this->assertEquals(count($result), 0);
	}

	function testUnknownFunctionThrowsException()
	{
		try
		{
			$this->getClient()->test();
			$this->fail();
		}
		catch (SoapFault $f)
		{
			$this->pass();
		}
	}
}


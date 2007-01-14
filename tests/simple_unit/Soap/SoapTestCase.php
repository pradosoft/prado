<?php

ini_set("soap.wsdl_cache_enabled",0);

require_once(dirname(__FILE__).'/ContactManager.php');

class SoapTestCase extends UnitTestCase
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
		$this->assertEqual(count($result), 1);
		$obj = $result[0];
		$this->assertEqual($obj->name, "me");
		$this->assertEqual($obj->id, 1);
		$this->assertEqual($obj->address->street, "sesamstreet");
		$this->assertNull($obj->address->nr);
		$this->assertNull($obj->address->zipcode);
		$this->assertEqual($obj->address->city, "sesamcity");
		$this->assertEqual($obj->email, "me@you.com");
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
		$this->assertEqual($result, $expected);
	}

	function testEmptyArray()
	{
		$result = $this->getClient()->getEmptyArray();
		$this->assertTrue(is_array($result));
		$this->assertEqual(count($result), 0);
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

?>
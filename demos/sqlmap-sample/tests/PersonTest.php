<?php

class PersonTest extends UnitTestCase
{
	function testPersonList()
	{
		//try it
		$people = TMapper::instance()->queryForList("SelectAll");

		//test it
		$this->assertNotNull($people, "Person list is not returned");
		$this->assertTrue(count($people) > 0, "Person list is empty");
		$person = $people[0];
		$this->assertNotNull($person, "Person not returned");
	}

	function testPersonUpdate()
	{
		$expect = "wei";
		$edited = "Nah";
		
		//get it;
		$person = TMapper::instance()->queryForObject("Select", 1);

		//test it
		$this->assertNotNull($person);
		$this->assertEqual($expect, $person->FirstName);

		//change it
		$person->FirstName = $edited;
		TMapper::instance()->update("Update", $person);

		//get it again
		$person = TMapper::instance()->queryForObject("Select", 1);

		//test it
		$this->assertEqual($edited, $person->FirstName);

		//change it back
		$person->FirstName = $expect;
		TMapper::instance()->update("Update", $person);
	}

	function testPersonDelete()
	{
		//insert it
		$person = new Person;
		$person->ID = -1;
		TMapper::instance()->insert("Insert", $person);

		//delte it
		$count = TMapper::instance()->delete("Delete", -1);
		$this->assertEqual(1, $count);
	}
}

?>
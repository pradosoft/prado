<?php

class testHangMan extends SeleniumTestCase
{
	function setup()
	{
		$this->open('../../demos/hangman/index.php');
	}

	function testHangManGame()
	{
		$this->assertLocation('hangman/index.php');
		$this->assertTextPresent('Prado Hangman Game');

		//use xpath to select input with value "HardLevel",
		//i.e the radio button with value "HardLevel"
		$this->click('//input[@value="HardLevel"]');
		$this->clickAndWait('//input[@value="Play!"]');

		//try 3 alphabets that sure doesn't exists
		$this->clickAndWait('link=X');
		$this->assertTextPresent('made 1 bad guesses');

		$this->clickAndWait('link=J');
		$this->assertTextPresent('made 2 bad guesses');

		$this->clickAndWait('link=Q');
		$this->assertTextPresent('You Lose!');
	}
}

?>
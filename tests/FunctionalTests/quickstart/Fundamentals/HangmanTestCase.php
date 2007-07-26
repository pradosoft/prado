<?php

class HangmanTestCase extends SeleniumTestCase
{
	function test ()
	{
		$this->open("../../demos/quickstart/index.php?page=Fundamentals.Samples.Hangman.Home&amp;notheme=true&amp;lang=en", "");
		$this->verifyTitle("Hangman Game", "");
		$this->verifyTextPresent("Medium game; you are allowed 5 misses.", "");
		$this->clickAndWait("//input[@type='submit' and @value='Play!']", "");
		$this->verifyTextPresent("You must choose a difficulty level", "");
		$this->clickAndWait("//input[@type='submit' and @value='Play!']", "");
		$this->click("//input[@name='ctl0\$body\$LevelSelection' and @value='3']", "");
		$this->clickAndWait("//input[@type='submit' and @value='Play!']", "");
		$this->verifyTextPresent("Please make a guess", "");
		$this->verifyTextPresent("maximum of 3", "");
		$this->clickAndWait("link=B", "");
		$this->clickAndWait("link=F", "");
		$this->clickAndWait("link=Give up?", "");
		$this->verifyTextPresent("You Lose", "");
		$this->clickAndWait("link=Start Again", "");
		$this->clickAndWait("//input[@type='submit' and @value='Play!']", "");
		$this->verifyTextPresent("Please make a guess", "");
		$this->verifyTextPresent("maximum of 3", "");
		$this->clickAndWait("link=Give up?", "");
		$this->verifyTextPresent("You Lose", "");
		$this->clickAndWait("link=Start Again", "");
		$this->click("//input[@name='ctl0\$body\$LevelSelection' and @value='5']", "");
		$this->clickAndWait("//input[@type='submit' and @value='Play!']", "");
		$this->verifyTextPresent("maximum of 5", "");
	}
}

?>
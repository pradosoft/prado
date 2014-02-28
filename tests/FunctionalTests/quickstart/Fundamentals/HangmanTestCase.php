<?php

class QuickstartHangmanTestCase extends PradoGenericSelenium2Test
{
	function test ()
	{
		$this->url("../../demos/quickstart/index.php?page=Fundamentals.Samples.Hangman.Home&amp;notheme=true&amp;lang=en");
		$this->verifyTitle("Hangman Game", "");
		$this->assertTextPresent("Medium game; you are allowed 5 misses.", "");
		$this->clickAndWait("//input[@type='submit' and @value='Play!']", "");
		$this->assertTextPresent("You must choose a difficulty level", "");
		$this->clickAndWait("//input[@type='submit' and @value='Play!']", "");
		$this->click("//input[@name='ctl0\$body\$LevelSelection' and @value='3']", "");
		$this->clickAndWait("//input[@type='submit' and @value='Play!']", "");
		$this->assertTextPresent("Please make a guess", "");
		$this->assertTextPresent("maximum of 3", "");
		$this->clickAndWait("link=B", "");
		$this->clickAndWait("link=F", "");
		$this->clickAndWait("link=Give up?", "");
		$this->assertTextPresent("You Lose", "");
		$this->clickAndWait("link=Start Again", "");
		$this->clickAndWait("//input[@type='submit' and @value='Play!']", "");
		$this->assertTextPresent("Please make a guess", "");
		$this->assertTextPresent("maximum of 3", "");
		$this->clickAndWait("link=Give up?", "");
		$this->assertTextPresent("You Lose", "");
		$this->clickAndWait("link=Start Again", "");
		$this->click("//input[@name='ctl0\$body\$LevelSelection' and @value='5']", "");
		$this->clickAndWait("//input[@type='submit' and @value='Play!']", "");
		$this->assertTextPresent("maximum of 5", "");
	}
}

<?php

class QuickstartHangmanTestCase extends PradoDemosSelenium2Test
{
	public function test()
	{
		$this->url("quickstart/index.php?page=Fundamentals.Samples.Hangman.Home&amp;notheme=true&amp;lang=en");
		$this->assertTitle("Hangman Game");
		$this->assertSourceContains("Medium game; you are allowed 5 misses.");
		$this->byXPath("//input[@type='submit' and @value='Play!']")->click();
		$this->assertSourceContains("You must choose a difficulty level");
		$this->byXPath("//input[@type='submit' and @value='Play!']")->click();
		$this->pause(50);
		$this->byXPath("//input[@name='ctl0\$body\$LevelSelection' and @value='3']")->click();
		$this->pause(50);
		$this->byXPath("//input[@type='submit' and @value='Play!']")->click();
		$this->assertSourceContains("Please make a guess");
		$this->assertSourceContains("maximum of 3");
		$this->byLinkText("B")->click();
		$this->pause(50);
		$this->byLinkText("F")->click();
		$this->pause(50);
		$this->byLinkText("Give up?")->click();
		$this->assertSourceContains("You Lose");
		$this->byLinkText("Start Again")->click();
		$this->pause(50);
		$this->byXPath("//input[@type='submit' and @value='Play!']")->click();
		$this->assertSourceContains("Please make a guess");
		$this->assertSourceContains("maximum of 3");
		$this->byLinkText("Give up?")->click();
		$this->assertSourceContains("You Lose");
		$this->byLinkText("Start Again")->click();
		$this->pause(50);
		$this->byXPath("//input[@name='ctl0\$body\$LevelSelection' and @value='5']")->click();
		$this->pause(50);
		$this->byXPath("//input[@type='submit' and @value='Play!']")->click();
		$this->assertSourceContains("maximum of 5");
	}
}

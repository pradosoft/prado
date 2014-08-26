<?php

class QuickstartHangmanTestCase extends PradoGenericSelenium2Test
{
	function test ()
	{
		$this->url("../../demos/quickstart/index.php?page=Fundamentals.Samples.Hangman.Home&amp;notheme=true&amp;lang=en");
		$this->assertEquals("Hangman Game", $this->title());
		$this->assertContains("Medium game; you are allowed 5 misses.", $this->source());
		$this->byXPath("//input[@type='submit' and @value='Play!']")->click();
		$this->assertContains("You must choose a difficulty level", $this->source());
		$this->byXPath("//input[@type='submit' and @value='Play!']")->click();
		$this->byXPath("//input[@name='ctl0\$body\$LevelSelection' and @value='3']")->click();
		$this->byXPath("//input[@type='submit' and @value='Play!']")->click();
		$this->assertContains("Please make a guess", $this->source());
		$this->assertContains("maximum of 3", $this->source());
		$this->byLinkText("B")->click();
		$this->byLinkText("F")->click();
		$this->byLinkText("Give up?")->click();
		$this->assertContains("You Lose", $this->source());
		$this->byLinkText("Start Again")->click();
		$this->byXPath("//input[@type='submit' and @value='Play!']")->click();
		$this->assertContains("Please make a guess", $this->source());
		$this->assertContains("maximum of 3", $this->source());
		$this->byLinkText("Give up?")->click();
		$this->assertContains("You Lose", $this->source());
		$this->byLinkText("Start Again")->click();
		$this->byXPath("//input[@name='ctl0\$body\$LevelSelection' and @value='5']")->click();
		$this->byXPath("//input[@type='submit' and @value='Play!']")->click();
		$this->assertContains("maximum of 5", $this->source());
	}
}

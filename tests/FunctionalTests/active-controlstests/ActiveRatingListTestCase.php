<?php
/**
 * ActiveRatingListTestCase.php
 * 
 * @author Bradley Booms <Bradley.Booms@nsighttel.com>
 * @version Creation Date: Oct 22, 2008
 */

/**
 * ActiveRatingListTestCase.php class
 * 
 * 
 * 
 * Properties
 * -
 * 
 * @author Bradley Booms <Bradley.Booms@nsighttel.com>
 * @version Modified Date: Oct 22, 2008
 * 
 * Modifications:
 */
class ActiveRatingListTestCase extends SeleniumTestCase
{
	function testCheckBoxes()
	{
		// Verify we're on the right page.
		$this->open("active-controls/index.php?page=ActiveRatingListCheckBoxesTest");
		$this->verifyTextPresent("TActiveRatingList Check Boxes Test Case");
		$this->assertCheckBoxes("RatingList", array(2), 6);
		
		// Change the list and make sure the radio buttons get updated properly.
		$this->clickTD("RatingList_c4");
		$this->pause(800);
		$this->assertCheckBoxes("RatingList", array(4), 6);
		
		$this->clickTD("RatingList_c2");
		$this->pause(800);
		$this->assertCheckBoxes("RatingList", array(2), 6);
	}

	function testRating()
	{
		// Verify we're on the right page.
		$this->open("active-controls/index.php?page=ActiveRatingListRatingTest");
		$this->verifyTextPresent("TActiveRatingList Rating Test Case");
		
		// Check the list, make sure it starts out with 5 stars.
		$this->assertText("Status", "Rating: 5");
		
		// Click on 1 star and make sure the Rating property updates.
		$this->clickTD("RatingList_c0");
		$this->pause(800);
		$this->assertText("Status", "Rating: 1");
		
		// Then set Rating to three on the server side and make sure it's correct.
		$this->click("SetRating");
		$this->pause(800);
		$this->assertText("Status", "Rating: 3");
	}

	function testSelectedIndex()
	{
		// Verify we're on the right page.
		$this->open("active-controls/index.php?page=ActiveRatingListSelectedIndexTest");
		$this->verifyTextPresent("TActiveRatingList SelectedIndex Test Case");
		$this->assertText("Status", " SelectedIndex: 1");
		
		// Click on 5 stars and make sure the SelectedIndex property updates.
		$this->clickTD("RatingList_c4");
		$this->pause(800);
		$this->assertText("Status", " SelectedIndex: 4");
		
		// Then set SelectedIndex to 5 on the server side and make sure it's correct.
		$this->click("SetSelectedIndex");
		$this->pause(800);
		$this->assertText("Status", " SelectedIndex: 5");
	}

	function testAutoPostBack()
	{
		// Verify we're on the right page.
		$this->open("active-controls/index.php?page=ActiveRatingListAutoPostBackTest");
		$this->verifyTextPresent("TActiveRatingList AutoPostBack Test Case");
		$this->assertText("Status", "AutoPostback=false");
		
		// Make sure that it doesn't auto post when clicked.
		$this->clickTD("RatingList_c3");
		$this->pause(800);
		$this->assertText("Status", "AutoPostback=false");
		
		// Then submit with an active button and make sure it updates.
		$this->click("Submit");
		$this->pause(800);
		$this->assertText("Status", "4 : Good");
	}

	function testAllowInput()
	{
		// Verify we're on the right page.
		$this->open("active-controls/index.php?page=ActiveRatingListAllowInputTest");
		$this->verifyTextPresent("TActiveRatingList AllowInput Test Case");
		$this->assertText("Status", "AllowInput=false");
		$this->assertCheckBoxes("RatingList", array(3), 6);

		// Make sure that clicking doesn't change anything.
		$this->clickTD("RatingList_c5");
		$this->pause(800);
		$this->assertText("Status", "AllowInput=false");
		$this->assertCheckBoxes("RatingList", array(3), 6);
	}

	function testReadOnly()
	{
		// Verify we're on the right page.
		$this->open("active-controls/index.php?page=ActiveRatingListReadOnlyTest");
		$this->verifyTextPresent("TActiveRatingList ReadOnly Test Case");
		$this->assertText("Status", "ReadOnly=true");
		$this->assertCheckBoxes("RatingList", array(0), 6);

		$this->clickTD("RatingList_c4");
		$this->pause(800);
		$this->assertText("Status", "ReadOnly=true");
		$this->assertCheckBoxes("RatingList", array(0), 6);
		
		// Then set ReadOnly to false, and make sure it works.
		$this->click("Writable");
		$this->pause(800);
		$this->assertText("Status", "ReadOnly=false");
		$this->assertCheckBoxes("RatingList", array(0), 6);
		
		
		$this->clickTD("RatingList_c1");
		$this->pause(800);
		$this->assertText("Status", "2 : Fair");
		$this->assertCheckBoxes("RatingList", array(1), 6);
		
		// Then set ReadOnly to true, and make sure it doesn't work anymore.
		$this->click("ReadOnly");
		$this->pause(800);
		$this->assertText("Status", "ReadOnly=true");
		$this->assertCheckBoxes("RatingList", array(1), 6);
		
		
		$this->clickTD("RatingList_c2");
		$this->pause(800);
		$this->assertText("Status", "ReadOnly=true");
		$this->assertCheckBoxes("RatingList", array(1), 6);
	}

	function testEnabled()
	{
		// Verify we're on the right page.
		$this->open("active-controls/index.php?page=ActiveRatingListEnabledTest");
		$this->verifyTextPresent("TActiveRatingList Enabled Test Case");
		$this->assertText("Status", "Enabled=false");
		$this->assertCheckBoxes("RatingList", array(5), 6);

		$this->clickTD("RatingList_c4");
		$this->pause(800);
		$this->assertText("Status", "Enabled=false");
		$this->assertCheckBoxes("RatingList", array(5), 6);
		
		// Then set Enable to true, and make sure it works.
		$this->click("Enable");
		$this->pause(800);
		$this->assertText("Status", "Enabled=true");
		$this->assertCheckBoxes("RatingList", array(5), 6);
		
		
		$this->clickTD("RatingList_c3");
		$this->pause(800);
		$this->assertText("Status", "4 : Good");
		$this->assertCheckBoxes("RatingList", array(3), 6);
		
		// Then set Enable to false, and make sure it doesn't work anymore.
		$this->click("Disable");
		$this->pause(800);
		$this->assertText("Status", "Enabled=false");
		$this->assertCheckBoxes("RatingList", array(3), 6);
		
		
		$this->clickTD("RatingList_c5");
		$this->pause(800);
		$this->assertText("Status", "Enabled=false");
		$this->assertCheckBoxes("RatingList", array(3), 6);
	}
		
	function testHoverCaption()
	{
		// Verify we're on the right page.
		$this->open("active-controls/index.php?page=ActiveRatingListHoverCaptionTest");
		$this->verifyTextPresent("TActiveRatingList Hover Caption Test Case");
		$this->assertText("Status", "CaptionID='Status'");
		$this->assertElementPresent("//input[@id='RatingList_c0']/../..[contains(@class, 'rating_selected')]");
		$this->assertElementPresent("//input[@id='RatingList_c1']/../..[contains(@class, 'rating_selected')]");
		$this->assertElementPresent("//input[@id='RatingList_c2']/../..[contains(@class, 'rating_selected')]");
		$this->assertElementPresent("//input[@id='RatingList_c3']/../..[contains(@class, 'rating_half')]");
		$this->assertElementPresent("//input[@id='RatingList_c3']/../..[contains(@class, 'rating')]");
		$this->assertElementPresent("//input[@id='RatingList_c4']/../..[contains(@class, 'rating')]");
		$this->assertElementPresent("//input[@id='RatingList_c5']/../..[contains(@class, 'rating')]");
		
		$this->mouseOver("//input[@id='RatingList_c4']/../../");
		$this->assertText("Status", "Excellent");
		$this->assertElementPresent("//input[@id='RatingList_c0']/../..[contains(@class, 'rating_hover')]");
		$this->assertElementPresent("//input[@id='RatingList_c1']/../..[contains(@class, 'rating_hover')]");
		$this->assertElementPresent("//input[@id='RatingList_c2']/../..[contains(@class, 'rating_hover')]");
		$this->assertElementPresent("//input[@id='RatingList_c3']/../..[contains(@class, 'rating_hover')]");
		$this->assertElementPresent("//input[@id='RatingList_c4']/../..[contains(@class, 'rating_hover')]");
		$this->assertElementNotPresent("//input[@id='RatingList_c5']/../..[contains(@class, 'rating_hover')]");
		$this->assertElementPresent("//input[@id='RatingList_c5']/../..[contains(@class, 'rating')]");
		
		$this->mouseOut("//input[@id='RatingList_c4']/../../");
		$this->assertText("Status", "CaptionID='Status'");
		$this->assertElementPresent("//input[@id='RatingList_c0']/../..[contains(@class, 'rating_selected')]");
		$this->assertElementPresent("//input[@id='RatingList_c1']/../..[contains(@class, 'rating_selected')]");
		$this->assertElementPresent("//input[@id='RatingList_c2']/../..[contains(@class, 'rating_selected')]");
		$this->assertElementNotPresent("//input[@id='RatingList_c3']/../..[contains(@class, 'rating_selected')]");
		$this->assertElementPresent("//input[@id='RatingList_c3']/../..[contains(@class, 'rating')]");
		$this->assertElementPresent("//input[@id='RatingList_c4']/../..[contains(@class, 'rating')]");
		$this->assertElementPresent("//input[@id='RatingList_c5']/../..[contains(@class, 'rating')]");
		
		
		$this->mouseOver("//input[@id='RatingList_c1']/../../");
		$this->assertText("Status", "Fair");
		
		$this->click("//input[@id='RatingList_c1']/../../");
		$this->pause(800);
		$this->assertText("Status", "2 : Fair");
		$this->assertElementPresent("//input[@id='RatingList_c0']/../..[contains(@class, 'rating_selected')]");
		$this->assertElementPresent("//input[@id='RatingList_c1']/../..[contains(@class, 'rating_selected')]");
		$this->assertElementNotPresent("//input[@id='RatingList_c2']/../..[contains(@class, 'rating_selected')]");
		$this->assertElementPresent("//input[@id='RatingList_c2']/../..[contains(@class, 'rating')]");
		$this->assertElementPresent("//input[@id='RatingList_c3']/../..[contains(@class, 'rating')]");
		$this->assertElementPresent("//input[@id='RatingList_c4']/../..[contains(@class, 'rating')]");
		$this->assertElementPresent("//input[@id='RatingList_c5']/../..[contains(@class, 'rating')]");
	}
	
	function clickTD($clientID){
		$this->click("//input[@id='{$clientID}']/../..");
	}

	function assertCheckBoxes($clientID, $checks, $total = 5)
	{
		for($i = 0; $i < $total; $i++)
		{
			if(in_array($i, $checks))
				$this->assertChecked("{$clientID}_c{$i}");
			else
				$this->assertNotChecked("{$clientID}_c{$i}");
		}
	}
}
?>
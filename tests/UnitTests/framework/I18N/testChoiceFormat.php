<?php
Prado::using('System.I18N.core.ChoiceFormat');

class testChoiceFormat extends UnitTestCase
{
	function testChoiceFormat()
	{
		$this->UnitTestCase();
	}

	function testChoices()
	{
		$choice = new ChoiceFormat();
		$string = '[0] are no files |[1] is one file |(1,Inf] are {number} files';
		
		$want = 'are no files';
		$this->assertEqual($want, $choice->format($string, 0));

		$want = 'is one file';
		$this->assertEqual($want, $choice->format($string, 1));

		$want = 'are {number} files';
		$this->assertEqual($want, $choice->format($string, 5));

		$this->assertFalse($choice->format($string, -1));

		$string = '{1,2} one two |{3,4} three four |[2,5] two to five inclusive';
		$this->assertEqual($choice->format($string,1),'one two');
		$this->assertEqual($choice->format($string,2.1),'two to five inclusive');
		$this->assertEqual($choice->format($string,3),'three four');
	}
}

?>
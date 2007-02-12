<?php

Prado::using('System.I18N.core.ChoiceFormat');

class ChoiceFormatTest extends UnitTestCase
{
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

	function test_set_notation()
	{
		$choice = new ChoiceFormat();
		$string = '{n: n%2 == 0} are even numbers |{n: n >= 5} are not even and greater than or equal to 5';

		$want = 'are even numbers';
		$this->assertEqual($want, $choice->format($string, 0));
		$this->assertEqual($want, $choice->format($string, 2));
		$this->assertEqual($want, $choice->format($string, 4));
		$this->assertNotEqual($want, $choice->format($string, 1));

		$want = 'are not even and greater than or equal to 5';
		$this->assertEqual($want, $choice->format($string, 5));
	}

	function test_polish()
	{
		$choice = new ChoiceFormat();
		$string = '[1] plik |{2,3,4} pliki
		|[5,21] pliko\'w |{n: n % 10 > 1 && n %10 < 5} pliki |{n: n%10 >= 5 || n%10 <=1} pliko\'w';

		$wants = array( 'plik' => array(1),
						'pliki' => array(2,3,4,22,23,24),
						'pliko\'w' => array(5,6,7,11,12,15,17,20,21,25,26,30));
		foreach($wants as $want => $numbers)
		{
			foreach($numbers as $n)
				$this->assertEqual($want, $choice->format($string, $n));
		}
	}

	function test_russian()
	{
		$choice = new ChoiceFormat();
		$string = '
		{n: n % 10 == 1 && n % 100 != 11} test1
		|{n: n % 10 >= 2 && n % 10 <= 4 && ( n % 100 < 10 || n % 100 >= 20 )} test2
		|{n: 2} test3';

		$wants = array('test1' => array(1,21,31,41),
						'test2' => array(2,4, 22, 24, 32, 34),
						'test3' => array(0, 5,6,7,8,9,10,11,12,13,14, 20,25,26,30)
			);
		foreach($wants as $want => $numbers)
		{
			foreach($numbers as $n)
				$this->assertEqual($want, $choice->format($string, $n));
		}
	}

	function test_english()
	{
		$choice = new ChoiceFormat();
		$string = '[0] none |{n: n % 10 == 1} 1st |{n: n % 10 == 2} 2nd |{n: n % 10 == 3} 3rd |{n:n} th';

		$wants = array('none' => array(0),
						'1st' => array(1,11,21),
						'2nd' => array(2,12,22),
						'3rd' => array(3,13,23),
						'th' => array(4,5,6,7,14,15)
			);
		foreach($wants as $want => $numbers)
		{
			foreach($numbers as $n)
				$this->assertEqual($want, $choice->format($string, $n));
		}
	}
}

?>
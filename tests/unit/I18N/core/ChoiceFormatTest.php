<?php

namespace Prado\Test\Unit\I18N\core;

use Prado\I18N\core\ChoiceFormat;

class ChoiceFormatTest extends \PHPUnit\Framework\TestCase
{
	public function testChoices()
	{
		$choice = new ChoiceFormat();
		$string = '[0] are no files |[1] is one file |(1,Inf] are {number} files';

		$want = 'are no files';
		$this->assertEquals($want, $choice->format($string, 0));

		$want = 'is one file';
		$this->assertEquals($want, $choice->format($string, 1));

		$want = 'are {number} files';
		$this->assertEquals($want, $choice->format($string, 5));

		$this->assertFalse($choice->format($string, -1));

		$string = '{1,2} one two |{3,4} three four |[2,5] two to five inclusive';
		$this->assertEquals($choice->format($string, 1), 'one two');
		$this->assertEquals($choice->format($string, 2.1), 'two to five inclusive');
		$this->assertEquals($choice->format($string, 3), 'three four');
	}

	public function test_set_notation()
	{
		$choice = new ChoiceFormat();
		$string = '{n: n%2 == 0} are even numbers |{n: n >= 5} are not even and greater than or equal to 5';

		$want = 'are even numbers';
		$this->assertEquals($want, $choice->format($string, 0));
		$this->assertEquals($want, $choice->format($string, 2));
		$this->assertEquals($want, $choice->format($string, 4));
		$this->assertNotEquals($want, $choice->format($string, 1));

		$want = 'are not even and greater than or equal to 5';
		$this->assertEquals($want, $choice->format($string, 5));
	}

	public function test_polish()
	{
		$choice = new ChoiceFormat();
		$string = '[1] plik |{2,3,4} pliki
		|[5,21] pliko\'w |{n: n % 10 > 1 && n %10 < 5} pliki |{n: n%10 >= 5 || n%10 <=1} pliko\'w';

		$wants = ['plik' => [1],
						'pliki' => [2, 3, 4, 22, 23, 24],
						'pliko\'w' => [5, 6, 7, 11, 12, 15, 17, 20, 21, 25, 26, 30]];
		foreach ($wants as $want => $numbers) {
			foreach ($numbers as $n) {
				$this->assertEquals($want, $choice->format($string, $n));
			}
		}
	}

	public function test_russian()
	{
		$choice = new ChoiceFormat();
		$string = '
		{n: n % 10 == 1 && n % 100 != 11} test1
		|{n: n % 10 >= 2 && n % 10 <= 4 && ( n % 100 < 10 || n % 100 >= 20 )} test2
		|{n: 2} test3';

		$wants = ['test1' => [1, 21, 31, 41],
						'test2' => [2, 4, 22, 24, 32, 34],
						'test3' => [0, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 20, 25, 26, 30]
			];
		foreach ($wants as $want => $numbers) {
			foreach ($numbers as $n) {
				$this->assertEquals($want, $choice->format($string, $n));
			}
		}
	}

	public function test_english()
	{
		$choice = new ChoiceFormat();
		$string = '[0] none |{n: n % 10 == 1} 1st |{n: n % 10 == 2} 2nd |{n: n % 10 == 3} 3rd |{n:n} th';

		$wants = ['none' => [0],
						'1st' => [1, 11, 21],
						'2nd' => [2, 12, 22],
						'3rd' => [3, 13, 23],
						'th' => [4, 5, 6, 7, 14, 15]
			];
		foreach ($wants as $want => $numbers) {
			foreach ($numbers as $n) {
				$this->assertEquals($want, $choice->format($string, $n));
			}
		}
	}

	public function testSetNotationOperators()
	{
		$choice = new ChoiceFormat();
		$this->assertTrue($choice->isValid(0, '{n: !n}'));
		$this->assertFalse($choice->isValid(3, '{n: !n}'));
		$this->assertTrue($choice->isValid(2, '{n: -n < 0}'));
		$this->assertTrue($choice->isValid(2, '{n: (n + 1) % 3 == 0}'));
		$this->assertFalse($choice->isValid(2, '{n: n + 1 % 3 == 0}'));
		$this->assertTrue($choice->isValid(5, '{n: n - 2 >= 3 & n <= 5}'));
		$this->assertTrue($choice->isValid(4, '{n: n % 2 == 1 | n == 4}'));
		$this->assertTrue($choice->isValid(7, '{n: n & 1}'));
		$this->assertTrue($choice->isValid(1.5, '{n: n > 1.25}'));
		$this->assertTrue($choice->isValid(0, '{n: n == 0 || 10 % n == 0}'));
		$this->assertFalse($choice->isValid(0, '{n: n != 0 && 10 % n == 0}'));
		$this->assertFalse($choice->isValid(3, '{n: n % 0}'));
	}

	public function testSetNotationRejectsCode()
	{
		$choice = new ChoiceFormat();
		$this->assertFalse($choice->isValid(1, '{n: phpinfo()}'));
		$this->assertFalse($choice->isValid(1, '{n: n > 0 ? 1 : 0}'));
		$this->assertFalse($choice->isValid(1, '{n: n = 1}'));
		$this->assertFalse($choice->isValid(1, '{n: (n > 0}'));
		$this->assertFalse($choice->isValid(1, '{n: n > 0)}'));
		$this->assertFalse($choice->isValid(1, '{n: n >}'));
		$this->assertFalse($choice->isValid(1, '{n: 1 2}'));
	}
}

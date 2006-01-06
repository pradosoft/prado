<?php

/**
 * ${classname}
 *
 * ${description}
 *
 * @author Wei Zhuo<weizhuo[at]gmail[dot]com>
 * @version $Revision: 1.66 $  $Date: ${DATE} ${TIME} $
 * @package ${package}
 */
class HomePageTest extends SeleniumTestCase
{
	function testHomePage()
	{
		$this->open("tests.php");
		$this->verifyTitle("Prado Functional Tests");
	}
}

?>
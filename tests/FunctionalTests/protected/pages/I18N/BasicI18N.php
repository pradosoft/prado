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
class BasicI18N extends TPage
{
}

/**
 * ${classname}
 *
 * ${description}
 *
 * @author Wei Zhuo<weizhuo[at]gmail[dot]com>
 * @version $Revision: 1.66 $  $Date: ${DATE} ${TIME} $
 * @package ${package}
 */
class BasicI18NTestCase extends SeleniumTestCase
{
	function testI18N()
	{
		$page = Prado::getApplication()->getTestPage(__FILE__);
		$this->open($page);
	}
}

?>
<?php
/**
 * IRepeatInfoUser, TRepeatInfo class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package System.Web.UI.WebControls
 */

/**
 * TRepeatLayout class.
 * TRepeatLayout defines the enumerable type for the possible layouts
 * that repeated contents can take.
 *
 * The following enumerable values are defined:
 * - Table: the repeated contents are organized using an HTML table
 * - Flow: the repeated contents are organized using HTML spans and breaks
 * - Raw: the repeated contents are stacked together without any additional decorations
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package System.Web.UI.WebControls
 * @since 3.0.4
 */
class TRepeatLayout extends TEnumerable
{
	const Table='Table';
	const Flow='Flow';
	const Raw='Raw';
}
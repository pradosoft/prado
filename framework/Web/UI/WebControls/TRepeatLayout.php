<?php

/**
 * IRepeatInfoUser, TRepeatInfo class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * TRepeatLayout class.
 * TRepeatLayout defines the enumerable type for the possible layouts
 * that repeated contents can take.
 *
 * The following enumerable values are defined:
 * - Table: the repeated contents are organized using an HTML table
 * - Flow: the repeated contents are organized using HTML spans and breaks
 * - Raw: the repeated contents are stacked together without any additional decorations
 * - OrderedList: the repeated contents are the items of an HTML `<ol>` (@since 4.4.0)
 * - UnorderedList: the repeated contents are the items of an HTML `<ul>` (@since 4.4.0)
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0.4
 */
class TRepeatLayout extends \Prado\TEnumerable
{
	public const Table = 'Table';
	public const Flow = 'Flow';
	public const Raw = 'Raw';
	public const OrderedList = 'OrderedList';
	public const UnorderedList = 'UnorderedList';
}

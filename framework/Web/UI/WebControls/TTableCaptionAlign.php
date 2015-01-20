<?php
/**
 * TTable and TTableRowCollection class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package System.Web.UI.WebControls
 */


/**
 * TTableCaptionAlign class.
 * TTableCaptionAlign defines the enumerable type for the possible alignments
 * that a table caption can take.
 *
 * The following enumerable values are defined:
 * - NotSet: alignment not specified
 * - Top: top aligned
 * - Bottom: bottom aligned
 * - Left: left aligned
 * - Right: right aligned
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package System.Web.UI.WebControls
 * @since 3.0.4
 */
class TTableCaptionAlign extends TEnumerable
{
	const NotSet='NotSet';
	const Top='Top';
	const Bottom='Bottom';
	const Left='Left';
	const Right='Right';
}
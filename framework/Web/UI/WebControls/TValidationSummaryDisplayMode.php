<?php
/**
 * TValidationSummary class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package System.Web.UI.WebControls
 */

/**
 * TValidationSummaryDisplayMode class.
 * TValidationSummaryDisplayMode defines the enumerable type for the possible modes
 * that a {@link TValidationSummary} can organize and display the collected error messages.
 *
 * The following enumerable values are defined:
 * - SimpleList: the error messages are displayed as a list without any decorations.
 * - SingleParagraph: the error messages are concatenated together into a paragraph.
 * - BulletList: the error messages are displayed as a bulleted list.
 * - HeaderOnly: only the HeaderText will be display.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package System.Web.UI.WebControls
 * @since 3.0.4
 */
class TValidationSummaryDisplayMode extends TEnumerable
{
	const SimpleList='SimpleList';
	const SingleParagraph='SingleParagraph';
	const BulletList='BulletList';
	const HeaderOnly='HeaderOnly';
}
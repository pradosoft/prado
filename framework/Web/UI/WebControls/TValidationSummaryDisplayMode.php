<?php
/**
 * TValidationSummary class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
 */

namespace Prado\Web\UI\WebControls;

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
 * @package Prado\Web\UI\WebControls
 * @since 3.0.4
 */
class TValidationSummaryDisplayMode extends \Prado\TEnumerable
{
	const SimpleList = 'SimpleList';
	const SingleParagraph = 'SingleParagraph';
	const BulletList = 'BulletList';
	const HeaderOnly = 'HeaderOnly';
}

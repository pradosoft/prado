<?php

/**
 * TDataGridResponsiveMode class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * TDataGridResponsiveMode class.
 * TDataGridResponsiveMode defines the enumerable type for how a {@see TDataGrid}
 * fits a viewport narrower than its table.
 *
 * The following enumerable values are defined:
 * - None: the table renders as is and may overflow the page.
 * - Scroll: the table renders in a named, focusable region that scrolls horizontally.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TDataGridResponsiveMode extends \Prado\TEnumerable
{
	public const None = 'None';
	public const Scroll = 'Scroll';
}

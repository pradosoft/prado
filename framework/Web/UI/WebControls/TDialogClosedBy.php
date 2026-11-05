<?php

/**
 * TDialogClosedBy class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * TDialogClosedBy class.
 *
 * TDialogClosedBy defines the user actions that close a {@see TDialog}, rendered
 * as its `closedby` attribute.
 *
 * | Value | `closedby` | The dialog closes on |
 * |---|---|---|
 * | Auto | not rendered | the browser default: a close request (Esc) for a modal dialog, nothing for a non-modal one |
 * | Any | `any` | a click outside the dialog (light dismiss) or a close request |
 * | CloseRequest | `closerequest` | a close request |
 * | None | `none` | neither; only a button or script closes it |
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TDialogClosedBy extends \Prado\TEnumerable
{
	public const Auto = 'Auto';
	public const Any = 'Any';
	public const CloseRequest = 'CloseRequest';
	public const None = 'None';
}

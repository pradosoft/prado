<?php

/**
 * TActiveUpdateMode class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\ActiveControls;

/**
 * TActiveUpdateMode class.
 *
 * TActiveUpdateMode defines how a callback response updates the markup of an
 * active control that renders again, such as a {@see TActivePanel}.
 * - Default → the page's {@see \Prado\Web\UI\TPage::getCallbackUpdateMode CallbackUpdateMode}.
 * - Replace → new nodes replace the old ones (`outerHTML`/`innerHTML`).
 * - Morph → the existing nodes are patched to match the new markup through
 *   idiomorph, so they keep their listeners, focus, caret, and scroll position.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TActiveUpdateMode extends \Prado\TEnumerable
{
	public const Default = 'Default';
	public const Replace = 'Replace';
	public const Morph = 'Morph';
}

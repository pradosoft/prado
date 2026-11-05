<?php

/**
 * TEnterKeyHint class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * TEnterKeyHint enumeration.
 *
 * TEnterKeyHint defines the allowed values for the HTML `enterkeyhint` attribute,
 * which hints at the action label (or icon) to present for the Enter key on virtual
 * keyboards. Use with {@see TWebControl::setEnterKeyHint}.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
class TEnterKeyHint extends \Prado\TEnumerable
{
	/** Virtual keyboard shows a "done" action; typically closes the keyboard. */
	public const Done = 'Done';
	/** Virtual keyboard shows a "return" / newline action. */
	public const Enter = 'Enter';
	/** Virtual keyboard shows a "go" action; typically navigates to a URL. */
	public const Go = 'Go';
	/** Virtual keyboard shows a "next" action; moves focus to the next field. */
	public const Next = 'Next';
	/** Virtual keyboard shows a "previous" action; moves focus to the previous field. */
	public const Previous = 'Previous';
	/** Virtual keyboard shows a "search" action; typically submits a search form. */
	public const Search = 'Search';
	/** Virtual keyboard shows a "send" action; typically sends a message. */
	public const Send = 'Send';
}

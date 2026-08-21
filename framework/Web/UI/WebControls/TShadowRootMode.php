<?php

/**
 * TShadowRootMode class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * TShadowRootMode enumeration.
 *
 * TShadowRootMode specifies the encapsulation mode of the shadow root that a
 * {@see TWebTemplate} declares through the `shadowrootmode` attribute.
 *
 * | Constant | `shadowrootmode` | Result |
 * |---|---|---|
 * | `NotSet` | *(omitted)* | An ordinary inert template; content is stamped from script. |
 * | `Open` | `open` | The parser attaches a shadow root reachable via `element.shadowRoot`. |
 * | `Closed` | `closed` | The parser attaches a shadow root; `element.shadowRoot` is `null`. |
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @see https://html.spec.whatwg.org/multipage/scripting.html#attr-template-shadowrootmode
 * @since 4.4.0
 */
class TShadowRootMode extends \Prado\TEnumerable
{
	/** No `shadowrootmode` attribute; the template stays an inert content holder. */
	public const NotSet = 'NotSet';

	/** The shadow root is accessible from script through `element.shadowRoot`. */
	public const Open = 'Open';

	/** The shadow root is not accessible from script; `element.shadowRoot` is `null`. */
	public const Closed = 'Closed';
}

<?php

/**
 * TLazyPanelTrigger class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\ActiveControls;

/**
 * TLazyPanelTrigger class.
 *
 * TLazyPanelTrigger defines when a {@see TLazyPanel} requests its content.
 * - Load → when the page loads.
 * - Visible → when the panel enters the viewport, extended by the panel's
 *   {@see TLazyPanel::getRootMargin RootMargin}. A browser without
 *   `IntersectionObserver` requests the content when the page loads.
 * - Manual → when the client calls `Prado.WebUI.TLazyPanel.load(id)`.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TLazyPanelTrigger extends \Prado\TEnumerable
{
	public const Load = 'Load';
	public const Visible = 'Visible';
	public const Manual = 'Manual';
}

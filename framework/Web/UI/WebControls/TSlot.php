<?php

/**
 * TSlot class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\TPropertyValue;

/**
 * TSlot class
 *
 * TSlot represents the HTML5 `<slot>` element. A `<slot>` is a placeholder
 * inside a shadow tree that the host element's light DOM children fill. The
 * browser renders the assigned children in place of the slot; with no
 * assigned children, the slot renders its own content as fallback.
 *
 * Properties:
 * - <b>Name</b>, string — the slot name rendered as the `name` attribute. An
 *   empty name (the default) omits the attribute and makes this the default
 *   slot, which receives the children without a `slot` attribute.
 *
 * A light DOM child is assigned to a named slot through its `slot` attribute.
 * Prado web controls render that attribute from
 * {@see TWebControl::getSlot TWebControl::Slot}.
 *
 * A slot functions only inside a shadow tree. The shadow tree comes from a
 * {@see TWebTemplate} with {@see TWebTemplate::getShadowRootMode ShadowRootMode}
 * set, or from a template whose content script attaches with
 * `attachShadow()`. Outside a shadow tree, the slot renders its fallback
 * content in place.
 *
 * ## Accessibility
 *
 * The slot element has no role and no box (`display: contents`); assistive
 * technology reads the assigned children in their slotted position. The HTML
 * accessibility mapping permits no `role` or `aria-*` attribute on `<slot>`,
 * so {@see TWebControl::getRole Role} and {@see TWebControl::getAria Aria}
 * are left unset. ID references such as `aria-labelledby` do not cross the
 * shadow boundary; label slotted content in the light DOM.
 *
 * Template usage:
 * ```html
 * <div>
 *     <com:TWebTemplate ShadowRootMode="Open">
 *         <h2><com:TSlot Name="title">Untitled</com:TSlot></h2>
 *         <com:TSlot>No content.</com:TSlot>
 *     </com:TWebTemplate>
 *     <com:TLabel Slot="title" Text="Report" />
 *     <p>Body text fills the default slot.</p>
 * </div>
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @see https://html.spec.whatwg.org/multipage/scripting.html#the-slot-element
 * @since 4.4.0
 */
class TSlot extends TWebControl
{
	/**
	 * @return string tag name
	 */
	protected function getTagName()
	{
		return 'slot';
	}

	/**
	 * @return string the slot name; empty string for the default slot.
	 */
	public function getName()
	{
		return $this->getViewState('Name', '');
	}

	/**
	 * Sets the slot name. Light DOM children whose `slot` attribute matches the
	 * name are assigned to this slot. An empty name makes this the default slot.
	 * @param string $value the slot name
	 */
	public function setName($value)
	{
		$this->setViewState('Name', trim(TPropertyValue::ensureString($value)), '');
	}

	/**
	 * Adds the `name` attribute to the renderer when a slot name is set.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 */
	protected function addAttributesToRender($writer)
	{
		parent::addAttributesToRender($writer);
		if (($name = $this->getName()) !== '') {
			$writer->addAttribute('name', $name);
		}
	}
}

<?php

/**
 * TSafetyCover class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\TPropertyValue;
use Prado\Web\UI\IFilterRenderable;
use Prado\Web\UI\IRenderable;
use Prado\Web\UI\ITemplate;
use Prado\Web\UI\TControl;

/**
 * TSafetyCover class
 *
 * TSafetyCover is a panel whose body content sits behind an overlay. A click on
 * the overlay pulses the panel and moves the overlay aside, opening the content
 * to interaction. The overlay returns after a timeout or after the pointer
 * leaves the panel. It guards content whose controls act immediately, such as a
 * delete button, by requiring one deliberate click before the content is
 * reachable.
 *
 * The control models the hinged cover over a physical switch. It prevents
 * accidental activation and closes itself afterward. It is not an access
 * control: the guarded content is present in the page and any script can open
 * it. Use {@see \Prado\Security\TAuthManager} and the authorization rules to
 * restrict who may act.
 *
 * The content of the {@see setOverlayTemplate OverlayTemplate} renders on the
 * overlay, such as a "click to unlock" message. The template instantiates into
 * the control tree during OnInit, so template controls participate in the page
 * lifecycle.
 *
 * Properties:
 * - <b>OverlayTemplate</b>, {@see \Prado\Web\UI\ITemplate} — content shown on
 *   the overlay. Defaults to null.
 * - <b>OverlayColor</b>, string — CSS color of the overlay, such as `#c00` or
 *   `rgba(255,0,0,0.65)`. Renders as an inline `background-color` on the
 *   overlay, overriding the stylesheet. Defaults to empty, which keeps the
 *   stylesheet color.
 * - <b>OverlayCssClass</b>, string — CSS class(es) added to the visible face,
 *   for styling it per instance beyond color. Defaults to empty.
 * - <b>OverlayEffect</b>, {@see TSafetyCoverEffect} — the geometric transition
 *   the overlay makes as the control opens and closes: `Slide` (default),
 *   `Collapse`, or `None`.
 * - <b>OverlayFade</b>, bool — whether the overlay also fades between opaque and
 *   transparent, combined with the `OverlayEffect` geometry. Defaults to false.
 * - <b>OverlayDirection</b>, {@see TSafetyCoverDirection} — the edge the overlay
 *   moves or collapses toward for the `Slide` and `Collapse` effects: `Up`
 *   (default), `Down`, `Left`, `Right`, or the content-direction-aware
 *   `Forward` and `Backward`. Ignored by `None`.
 * - <b>OpenDelay</b>, int — milliseconds between the click and the overlay
 *   moving aside; the panel pulses for this whole span. Defaults to 800.
 * - <b>AutoCloseDelay</b>, int — milliseconds before the cover returns on its
 *   own, measured from opening, or from the last interaction when
 *   {@see setKeepOpenWhileActive KeepOpenWhileActive} is set. Defaults to 6000.
 * - <b>KeepOpenWhileActive</b>, bool — whether interaction with the open content
 *   resets the `AutoCloseDelay` timer, keeping the cover open through a complex
 *   interaction. Defaults to false.
 * - <b>MouseOutTimeout</b>, int — milliseconds after the pointer leaves the
 *   panel before the overlay returns. Re-entering the panel cancels the pending
 *   close. Defaults to 1000.
 * - <b>AnimationDuration</b>, int — milliseconds the open and close animation of
 *   the face takes. Defaults to 250.
 * - <b>AccessibleLabel</b>, string — accessible-name override for the guard,
 *   rendered as its `aria-label`. Defaults to empty, which labels the guard from
 *   its visible face content instead.
 * - <b>CssUrl</b>, string — URL of the stylesheet for the control. The value
 *   'default' (the default) publishes the bundled stylesheet; an empty string
 *   registers no stylesheet.
 *
 * ## Accessibility
 *
 * The guard renders as a `role="button"` with `tabindex="0"`, an accessible name
 * (from its visible face by default, or {@see getAccessibleLabel AccessibleLabel}
 * when set), and `aria-expanded`/`aria-controls` describing and pointing at the
 * content. It drops to `tabindex="-1"` while open. A keyboard or
 * assistive-technology user focuses the guard and presses Enter or Space to
 * reveal the content, the same gesture the mouse performs by clicking.
 *
 * The `pointer-events` guard blocks only the mouse, so the client-side wrapper
 * additionally marks the content `inert` while the cover is closed (with an
 * `aria-hidden` and tabindex fallback for browsers without `inert`). This keeps
 * the guarded controls out of the tab order and the accessibility tree until the
 * cover opens, so keyboard and AT users cannot reach them behind the cover. On a
 * keyboard open, focus moves into the revealed content; on close, focus returns
 * to the guard. The `@media (prefers-reduced-motion: reduce)` rule drops the
 * animation and the pulse.
 *
 * The rendered structure:
 * ```html
 * <div id="{ClientID}" class="safety-cover">
 *     <div id="{ClientID}_slider" class="safety-cover-slider">
 *         <div id="{ClientID}_overlay" class="safety-cover-overlay">      <!-- guard -->
 *             <div id="{ClientID}_face" class="safety-cover-face"> overlay template </div>
 *         </div>
 *     </div>
 *     <div id="{ClientID}_content" class="safety-cover-content"> body content </div>
 * </div>
 * ```
 *
 * The face animates both opening and closing over {@see getAnimationDuration
 * AnimationDuration}. The guard blocks clicks the instant the cover starts to
 * close, so the animated return never exposes the content.
 *
 * ## CSS contract
 *
 * The cover tracks the content because the slider is `inset:0` inside the
 * `position:relative` panel, which sizes to its in-flow content, and it guards
 * because the slider stacks at `z-index:1` above the content with the guard's
 * `pointer-events` catching the mouse. Overriding these invariants (through
 * `CssClass`, a theme, or a replacement {@see getCssUrl CssUrl}) breaks the
 * control:
 * - the root must stay positioned (`position: relative|absolute|fixed`, not
 *   `static`, or the slider positions against a distant ancestor);
 * - the content must stay in normal flow (not `position:absolute/fixed`,
 *   `float`, or `display:none`, or the panel collapses and covers nothing) and
 *   keeps `isolation:isolate` so a positioned high-`z-index` descendant cannot
 *   paint above the cover;
 * - the slider keeps `overflow:hidden` (clips the Slide face) and `z-index:1`;
 * - the overlay and face keep `inset:0`, and the guard keeps its `pointer-events`
 *   toggle (auto closed, none open).
 *
 * The content also needs real size (its own content height or an explicit
 * `Height`). `OverlayColor`, `OverlayCssClass`, `OverlayTemplate`, and panel
 * padding/border are safe to change. The stylesheet header carries the same
 * contract as a table.
 *
 * The client-side wrapper registers in `Prado.Registry` under the ClientID and
 * offers `open()` and `close()` methods for script control.
 *
 * Template usage:
 * ```html
 * <com:TSafetyCover OverlayColor="#c00">
 *     <prop:OverlayTemplate>
 *         Click to unlock
 *     </prop:OverlayTemplate>
 *     <com:TButton Text="Delete" OnClick="deleteItem" />
 * </com:TSafetyCover>
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TSafetyCover extends TPanel
{
	/**
	 * @var string[] the CSS classes the control manages on its root element; used
	 *   to keep them out of the author {@see getCssClass CssClass} when combining
	 */
	private const FRAMEWORK_CLASSES = [
		'safety-cover',
		'safety-cover-slide', 'safety-cover-collapse', 'safety-cover-none',
		'safety-cover-up', 'safety-cover-down', 'safety-cover-left', 'safety-cover-right',
		'safety-cover-fade',
	];

	/** @var ?ITemplate template for the overlay content */
	private ?ITemplate $_overlayTemplate = null;

	/** @var ?TControl container holding the instantiated overlay template */
	private ?TControl $_overlay = null;

	/**
	 * @return ?ITemplate template for the overlay content. Defaults to null.
	 */
	public function getOverlayTemplate()
	{
		return $this->_overlayTemplate;
	}

	/**
	 * Set the template for the overlay content. A template set after OnInit
	 * replaces the instantiated overlay content. The content renders on the face,
	 * inside the `role="button"` guard, so it must be non-interactive — a label or
	 * decoration, not links, buttons, or active controls, which ARIA disallows as
	 * descendants of a button. It also provides the guard's accessible name when
	 * {@see setAccessibleLabel AccessibleLabel} is empty.
	 * @param ?ITemplate $value template for the overlay content
	 */
	public function setOverlayTemplate($value)
	{
		$this->_overlayTemplate = $value;
		if ($this->_overlay !== null) {
			$overlay = $this->_overlay;
			$this->_overlay = null;
			$this->getControls()->remove($overlay);
		}
		if ($value !== null && $this->getHasInitialized()) {
			$this->ensureOverlayControls();
		}
	}

	/**
	 * @return string CSS color of the overlay. Defaults to empty, which keeps the
	 *   stylesheet color.
	 */
	public function getOverlayColor()
	{
		return $this->getViewState('OverlayColor', '');
	}

	/**
	 * Set the CSS color of the overlay, such as `#c00` or `rgba(255,0,0,0.65)`.
	 * The value renders as an inline `background-color` on the visible face element,
	 * overriding the stylesheet and any background a
	 * {@see setOverlayCssClass OverlayCssClass} declares. A translucent color
	 * leaves the guarded content legible behind the overlay. Leave it empty when
	 * an `OverlayCssClass` supplies the background.
	 * @param string $value CSS color of the overlay, or empty to keep the
	 *   stylesheet color
	 */
	public function setOverlayColor($value)
	{
		$this->setViewState('OverlayColor', TPropertyValue::ensureString($value), '');
	}

	/**
	 * @return string CSS class(es) added to the visible face element, for styling
	 *   one cover's face beyond {@see getOverlayColor OverlayColor}. Defaults to empty.
	 */
	public function getOverlayCssClass()
	{
		return $this->getViewState('OverlayCssClass', '');
	}

	/**
	 * Set CSS class(es) added to the visible face element, alongside the built-in
	 * `safety-cover-face` class. Use it to style the face per instance — gradients,
	 * borders, typography, a background image — where {@see setOverlayColor
	 * OverlayColor} only sets a background color. An inline `OverlayColor` still
	 * wins over a background the class declares.
	 * @param string $value CSS class(es) for the face
	 */
	public function setOverlayCssClass($value)
	{
		$this->setViewState('OverlayCssClass', TPropertyValue::ensureString($value), '');
	}

	/**
	 * @return string the geometric transition the overlay makes, one of the
	 *   {@see TSafetyCoverEffect} values. Defaults to TSafetyCoverEffect::Slide.
	 */
	public function getOverlayEffect()
	{
		return $this->getViewState('OverlayEffect', TSafetyCoverEffect::Slide);
	}

	/**
	 * Set the geometric transition the overlay makes as the control opens and
	 * closes. `Slide` translates the overlay off the panel, `Collapse` clips it
	 * away in place, and `None` makes no geometric change. Combine with
	 * {@see setOverlayFade OverlayFade} for an opacity transition.
	 * @param string $value a {@see TSafetyCoverEffect} value
	 */
	public function setOverlayEffect($value)
	{
		$this->setViewState('OverlayEffect', TPropertyValue::ensureEnum($value, TSafetyCoverEffect::class), TSafetyCoverEffect::Slide);
	}

	/**
	 * @return bool whether the overlay fades between opaque and transparent as the
	 *   control opens and closes, combined with the {@see getOverlayEffect
	 *   OverlayEffect} geometry. Defaults to false.
	 */
	public function getOverlayFade()
	{
		return $this->getViewState('OverlayFade', false);
	}

	/**
	 * Set whether the overlay fades between opaque and transparent as the control
	 * opens and closes. The fade layers on any {@see setOverlayEffect
	 * OverlayEffect} geometry; with `OverlayEffect` set to `None` it is the whole
	 * transition. With `None` and no fade, the overlay snaps between states
	 * without animation.
	 * @param bool $value whether the overlay fades
	 */
	public function setOverlayFade($value)
	{
		$this->setViewState('OverlayFade', TPropertyValue::ensureBoolean($value), false);
	}

	/**
	 * @return string the edge the overlay moves or collapses toward, one of the
	 *   {@see TSafetyCoverDirection} values. Defaults to TSafetyCoverDirection::Up.
	 */
	public function getOverlayDirection()
	{
		return $this->getViewState('OverlayDirection', TSafetyCoverDirection::Up);
	}

	/**
	 * Set the edge the overlay moves or collapses toward for the `Slide` and
	 * `Collapse` effects. `Forward` and `Backward` resolve to `Right`/`Left` from
	 * the control's {@see TPanel::getDirection Direction} during rendering. The
	 * `None` effect ignores this property.
	 * @param string $value a {@see TSafetyCoverDirection} value
	 */
	public function setOverlayDirection($value)
	{
		$this->setViewState('OverlayDirection', TPropertyValue::ensureEnum($value, TSafetyCoverDirection::class), TSafetyCoverDirection::Up);
	}

	/**
	 * Resolve {@see getOverlayDirection OverlayDirection} to a physical edge. The
	 * logical `Forward` and `Backward` values map to `right`/`left` through the
	 * control's {@see TPanel::getDirection Direction}: `Forward` is `right` in
	 * left-to-right content and `left` in right-to-left content.
	 * @return string one of `up`, `down`, `left`, `right`
	 */
	protected function getResolvedDirection(): string
	{
		$direction = $this->getOverlayDirection();
		if ($direction !== TSafetyCoverDirection::Forward && $direction !== TSafetyCoverDirection::Backward) {
			return strtolower($direction);
		}
		$rightToLeft = $this->getDirection() === TContentDirection::RightToLeft;
		$forwardIsRight = !$rightToLeft;
		if ($direction === TSafetyCoverDirection::Backward) {
			$forwardIsRight = !$forwardIsRight;
		}
		return $forwardIsRight ? 'right' : 'left';
	}

	/**
	 * @return int milliseconds between the click and the overlay moving aside.
	 *   Defaults to 800.
	 */
	public function getOpenDelay()
	{
		return $this->getViewState('OpenDelay', 800);
	}

	/**
	 * Set the milliseconds between the click and the overlay moving aside. The
	 * panel pulses during the delay.
	 * @param int $value milliseconds before the overlay moves aside
	 */
	public function setOpenDelay($value)
	{
		$this->setViewState('OpenDelay', TPropertyValue::ensureInteger($value), 800);
	}

	/**
	 * @return int milliseconds the content stays open before the overlay returns
	 *   on its own. Defaults to 6000.
	 */
	public function getAutoCloseDelay()
	{
		return $this->getViewState('AutoCloseDelay', 6000);
	}

	/**
	 * Set the milliseconds the content stays open before the overlay returns on
	 * its own. {@see setKeepOpenWhileActive KeepOpenWhileActive} makes this an idle
	 * timeout that interaction resets. The pointer leaving the panel returns it
	 * sooner; see {@see setMouseOutTimeout MouseOutTimeout}.
	 * @param int $value milliseconds the content stays open
	 */
	public function setAutoCloseDelay($value)
	{
		$this->setViewState('AutoCloseDelay', TPropertyValue::ensureInteger($value), 6000);
	}

	/**
	 * @return bool whether interaction with the open content resets the
	 *   {@see getAutoCloseDelay AutoCloseDelay} auto-close timer. Defaults to false.
	 */
	public function getKeepOpenWhileActive()
	{
		return $this->getViewState('KeepOpenWhileActive', false);
	}

	/**
	 * Set whether interaction keeps the cover open. When true, a mouse move,
	 * key press, pointer press, or input within the open panel resets the
	 * {@see getAutoCloseDelay AutoCloseDelay} timer and cancels a pending mouse-out
	 * close, so the cover stays open through a complex interaction and closes
	 * `AutoCloseDelay` after the last activity. It only extends the open time, never
	 * shortens it. This replaces the naive area-scaled timeout with one that
	 * follows the actual interaction.
	 * @param bool $value whether interaction resets the auto-close timer
	 */
	public function setKeepOpenWhileActive($value)
	{
		$this->setViewState('KeepOpenWhileActive', TPropertyValue::ensureBoolean($value), false);
	}

	/**
	 * @return int milliseconds the open and close animation takes. Defaults to 250.
	 */
	public function getAnimationDuration()
	{
		return $this->getViewState('AnimationDuration', 250);
	}

	/**
	 * Set the milliseconds the open and close animation takes. The value drives
	 * the face's CSS transition through the `--safety-cover-animation-duration`
	 * custom property. The guard blocks clicks independently of this animation, so
	 * a longer close still re-guards instantly.
	 * @param int $value milliseconds the animation takes
	 */
	public function setAnimationDuration($value)
	{
		$this->setViewState('AnimationDuration', TPropertyValue::ensureInteger($value), 250);
	}

	/**
	 * @return int milliseconds after the pointer leaves the panel before the
	 *   overlay returns. Defaults to 1000.
	 */
	public function getMouseOutTimeout()
	{
		return $this->getViewState('MouseOutTimeout', 1000);
	}

	/**
	 * Set the milliseconds after the pointer leaves the panel before the overlay
	 * returns. Re-entering the panel cancels the pending close.
	 * @param int $value milliseconds after the pointer leaves the panel
	 */
	public function setMouseOutTimeout($value)
	{
		$this->setViewState('MouseOutTimeout', TPropertyValue::ensureInteger($value), 1000);
	}

	/**
	 * @return string URL of the stylesheet for the control. Defaults to
	 *   'default', which publishes the bundled stylesheet. An empty string
	 *   registers no stylesheet.
	 */
	public function getCssUrl()
	{
		return $this->getViewState('CssUrl', 'default');
	}

	/**
	 * Set the URL of the stylesheet for the control. The bundled stylesheet
	 * positions the overlay over the content and animates the opening; a
	 * replacement stylesheet provides those rules itself.
	 * @param string $value stylesheet URL, 'default' for the bundled stylesheet,
	 *   or empty to register no stylesheet
	 */
	public function setCssUrl($value)
	{
		$this->setViewState('CssUrl', TPropertyValue::ensureString($value), 'default');
	}

	/**
	 * @return string the accessible name override for the guard, rendered as its
	 *   `aria-label`. Defaults to empty, in which case the guard is instead
	 *   labelled by its visible face content ({@see setOverlayTemplate
	 *   OverlayTemplate}).
	 */
	public function getAccessibleLabel()
	{
		return $this->getViewState('AccessibleLabel', '');
	}

	/**
	 * Set the accessible name override for the guard. The guard renders as a
	 * `role="button"` that a keyboard or assistive-technology user activates to
	 * reveal the content. When empty (the default), the guard is labelled by its
	 * visible face content, so the accessible name matches the visible label (WCAG
	 * 2.5.3). Set this only when the face has no readable text, such as an
	 * icon-only cover, and make it contain any visible text on the face.
	 * @param string $value the accessible name override, or empty to label from the face
	 */
	public function setAccessibleLabel($value)
	{
		$this->setViewState('AccessibleLabel', TPropertyValue::ensureString($value), '');
	}

	/**
	 * Instantiate the {@see setOverlayTemplate OverlayTemplate} into the control
	 * tree so its controls participate in the page lifecycle.
	 * @param mixed $param event parameter
	 */
	public function onInit($param)
	{
		parent::onInit($param);
		$this->ensureOverlayControls();
	}

	/**
	 * Create the overlay container and instantiate the template into it. The
	 * container is a child of this panel; {@see renderContents()} renders it
	 * inside the overlay element and excludes it from the body content.
	 */
	protected function ensureOverlayControls()
	{
		if ($this->_overlay !== null || $this->_overlayTemplate === null) {
			return;
		}
		$this->_overlay = new TControl();
		$this->getControls()->add($this->_overlay);
		$this->_overlayTemplate->instantiateIn($this->_overlay);
	}

	/**
	 * Register the stylesheet and the client-side wrapper script.
	 * @param mixed $param event parameter
	 */
	public function onPreRender($param)
	{
		parent::onPreRender($param);
		$this->registerStyleSheet();
		$cs = $this->getPage()->getClientScript();
		$cs->registerPradoScript('safetycover');
		$cs->registerPostBackControl($this->getClientClassName(), $this->getClientOptions());
	}

	/**
	 * Register the stylesheet specified by {@see getCssUrl CssUrl}. The value
	 * 'default' publishes the bundled stylesheet; an empty string registers
	 * nothing.
	 */
	protected function registerStyleSheet()
	{
		$url = $this->getCssUrl();
		if ($url === '') {
			return;
		}
		if ($url === 'default') {
			$url = $this->getApplication()->getAssetManager()->publishFilePath(__DIR__ . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'safetycover.css');
		}
		$this->getPage()->getClientScript()->registerStyleSheetFile($url, $url);
	}

	/**
	 * @return string the client-side JavaScript class name
	 */
	protected function getClientClassName()
	{
		return 'Prado.WebUI.TSafetyCover';
	}

	/**
	 * @return array the options passed to the client-side JavaScript class
	 */
	protected function getClientOptions(): array
	{
		return [
			'ID' => $this->getClientID(),
			'OpenDelay' => $this->getOpenDelay(),
			'AutoCloseDelay' => $this->getAutoCloseDelay(),
			'MouseOutTimeout' => $this->getMouseOutTimeout(),
			'KeepOpenWhileActive' => $this->getKeepOpenWhileActive(),
		];
	}

	/**
	 * Add attribute name-value pairs to the renderer. The `id` attribute always
	 * renders because the stylesheet and the client-side wrapper address the
	 * control and its inner elements by ClientID. The framework CSS classes lead
	 * any author-set {@see setCssClass CssClass}: `safety-cover`, the effect class
	 * `safety-cover-<effect>`, and, for the `Slide` and `Collapse` effects, the
	 * resolved direction class `safety-cover-<up|down|left|right>`. The class
	 * attribute is composed only for rendering; the stored CssClass is left
	 * untouched. The `--safety-cover-open-delay` custom property carries
	 * {@see getOpenDelay OpenDelay} so the pulse animation spans it.
	 * @param \Prado\Web\UI\THtmlWriter $writer the renderer
	 */
	protected function addAttributesToRender($writer)
	{
		$writer->addAttribute('id', $this->getClientID());
		$writer->addStyleAttribute('--safety-cover-open-delay', $this->getOpenDelay() . 'ms');
		$writer->addStyleAttribute('--safety-cover-animation-duration', $this->getAnimationDuration() . 'ms');
		parent::addAttributesToRender($writer);
		// Override the class the style renderer wrote, prepending the framework
		// classes without persisting them into the CssClass viewstate.
		$writer->addAttribute('class', $this->buildCssClass($this->getCssClass()));
	}

	/**
	 * Compose the class attribute value, framework classes first, then the author
	 * classes. Author classes are preserved as-is; only a literal duplicate of a
	 * framework class this control adds is dropped. Nothing is stored, so the
	 * stored {@see getCssClass CssClass} stays exactly what the author set.
	 * @param string $cssClass the author CssClass value
	 * @return string the class attribute value with framework classes first
	 */
	protected function buildCssClass(string $cssClass): string
	{
		$framework = ['safety-cover', 'safety-cover-' . strtolower($this->getOverlayEffect())];
		if ($this->getOverlayEffect() !== TSafetyCoverEffect::None) {
			$framework[] = 'safety-cover-' . $this->getResolvedDirection();
		}
		if ($this->getOverlayFade()) {
			$framework[] = 'safety-cover-fade';
		}
		$authored = array_filter(
			$cssClass === '' ? [] : explode(' ', $cssClass),
			fn ($token) => $token !== '' && !in_array($token, $framework, true),
		);
		return implode(' ', array_merge($framework, $authored));
	}

	/**
	 * Strip characters that could break out of the inline `background-color`
	 * declaration, guarding against CSS injection when {@see getOverlayColor
	 * OverlayColor} carries untrusted data. The retained set covers hex,
	 * `rgb()`/`rgba()`/`hsl()`, the modern slash syntax, percentages, `var()`
	 * references, and named colors.
	 * @param string $color the raw OverlayColor value
	 * @return string the color with unsafe characters removed
	 */
	protected function sanitizeOverlayColor(string $color): string
	{
		return preg_replace('~[^#a-zA-Z0-9(),.%/\s-]~', '', $color);
	}

	/**
	 * Render the overlay and the body content in their wrapper elements. The
	 * slider clips the animation. Inside it are two layers: the `overlay` guard,
	 * a transparent element whose `pointer-events` block clicks whenever the cover
	 * is closed, and the `face` inside it, the visible skin that carries the color
	 * and template and animates open and closed. Decoupling the two lets the face
	 * animate the close smoothly while the guard re-blocks clicks instantly. The
	 * content element wraps the body content the overlay guards.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 */
	public function renderContents($writer)
	{
		$this->ensureOverlayControls();
		$clientID = $this->getClientID();
		$writer->addAttribute('id', $clientID . '_slider');
		$writer->addAttribute('class', 'safety-cover-slider');
		$writer->renderBeginTag('div');
		$writer->addAttribute('id', $clientID . '_overlay');
		$writer->addAttribute('class', 'safety-cover-overlay');
		// The guard is an accessible button: a keyboard or AT user focuses it and
		// activates it to reveal the content, which the wrapper keeps unreachable
		// (inert) while closed. aria-expanded reflects the state; the wrapper flips
		// it to "true" and drops the tabindex on open. The accessible name comes
		// from AccessibleLabel when set, otherwise from the visible face content so
		// it matches the visible label.
		$writer->addAttribute('role', 'button');
		$writer->addAttribute('tabindex', '0');
		if (($label = $this->getAccessibleLabel()) !== '') {
			$writer->addAttribute('aria-label', $label);
		} else {
			$writer->addAttribute('aria-labelledby', $clientID . '_face');
		}
		$writer->addAttribute('aria-expanded', 'false');
		$writer->addAttribute('aria-controls', $clientID . '_content');
		$writer->renderBeginTag('div');
		$writer->addAttribute('id', $clientID . '_face');
		$faceClass = 'safety-cover-face';
		if (($faceCss = $this->getOverlayCssClass()) !== '') {
			$faceClass .= ' ' . $faceCss;
		}
		$writer->addAttribute('class', $faceClass);
		if (($color = $this->sanitizeOverlayColor($this->getOverlayColor())) !== '') {
			$writer->addStyleAttribute('background-color', $color);
		}
		$writer->renderBeginTag('div');
		if ($this->_overlay !== null) {
			$this->_overlay->renderControl($writer);
		}
		$writer->renderEndTag();
		$writer->renderEndTag();
		$writer->renderEndTag();
		$writer->addAttribute('id', $clientID . '_content');
		$writer->addAttribute('class', 'safety-cover-content');
		$writer->renderBeginTag('div');
		$this->renderBodyContents($writer);
		$writer->renderEndTag();
	}

	/**
	 * Render the child controls except the overlay container. The rendering of
	 * each child matches {@see \Prado\Web\UI\TControl::renderChildren()}.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 */
	protected function renderBodyContents($writer)
	{
		if (!$this->getHasControls()) {
			return;
		}
		foreach ($this->getControls() as $control) {
			if ($control === $this->_overlay) {
				continue;
			}
			if (is_string($control)) {
				$writer->write($control);
			} elseif ($control instanceof TControl) {
				$control->renderControl($writer);
			} elseif ($control instanceof IFilterRenderable) {
				$oldWriter = $this->preRenderFilter($writer, $control);
				$control->render($writer);
				$this->processRenderFilter($writer, $oldWriter, $control);
			} elseif ($control instanceof IRenderable) {
				$control->render($writer);
			}
		}
	}
}

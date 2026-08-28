# Web/UI/WebControls/TSafetyCover

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TSafetyCover`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TSafetyCover.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Since:** 4.4.0

## Overview
TSafetyCover extends `TPanel` and keeps its body content behind an overlay. A click on the overlay pulses the panel and moves the cover aside, opening the content. The cover returns after `AutoCloseDelay`, or after the pointer leaves the panel. It models the hinged cover over a physical switch: it prevents accidental activation, and it re-closes itself.

**Two layers (safety):** inside the slider are a transparent **guard** (`safety-cover-overlay`) and a visible **face** (`safety-cover-face`) nested within it. The guard never moves; only its `pointer-events` toggle (auto when closed → blocks, none when open), so it re-blocks the content the instant close begins. The face is the colored skin that animates open and closed over `AnimationDuration`. Decoupling them means the close animates smoothly (the face slides/fades back) while the guard already blocks every click — no window where a click reaches the content mid-close. Verified: during the close animation the topmost hit-testable element over the guarded content is always the guard.

It is a UX guard, not an access control. The guarded content is present in the page and any script can call `open()`. Use `TAuthManager` and authorization rules to restrict who may act.

Search terms: confirm before delete, click to unlock, guarded button, accidental click.

## Key Properties/Methods

- `OverlayTemplate` - `ITemplate` rendered on the overlay (e.g. "click to unlock"); instantiated into the control tree during `OnInit`
- `OverlayColor` - CSS color of the overlay; renders as an inline `background-color` that overrides the stylesheet. Empty (default) keeps the stylesheet's translucent red. A translucent value leaves the guarded content legible behind the overlay.
- `OverlayCssClass` - CSS class(es) added to the visible face element (alongside `safety-cover-face`), for per-instance styling beyond color — gradients, borders, background image, typography. Default empty. `OverlayColor`'s inline background still wins over the class.
- `OverlayEffect` - `TSafetyCoverEffect`: `Slide` (default), `Collapse`, `None`. The geometric transition the overlay makes as the control opens and closes.
- `OverlayFade` - bool (default false). Whether the overlay also fades between opaque and transparent, combined with the `OverlayEffect` geometry. An independent axis.
- `OverlayDirection` - `TSafetyCoverDirection`: `Up` (default), `Down`, `Left`, `Right`, `Forward`, `Backward`. The edge the overlay moves/collapses toward for Slide and Collapse; ignored by None.
- `OpenDelay` - ms between the click and the cover moving aside; the panel pulses for this whole span (default 800)
- `AutoCloseDelay` - ms before the cover auto-closes, from opening or (with `KeepOpenWhileActive`) from the last interaction (default 6000)
- `KeepOpenWhileActive` - bool (default false). When true, interaction inside the open panel (mousemove, keydown, pointerdown, input) resets the `AutoCloseDelay` timer and cancels a pending mouse-out close, so a complex interaction (typing, clicking a series of controls) keeps the cover open; it closes `AutoCloseDelay` after the last activity. This replaces the old naive `width*height` ms area heuristic with an idle timeout that follows the actual interaction — it only ever extends, never shortens.
- `MouseOutTimeout` - ms after the pointer leaves before the cover returns; re-entering cancels it (default 1000)
- `AnimationDuration` - ms the open/close animation of the face takes, via `--safety-cover-animation-duration` (default 250). Precedent: TAccordion's `AnimationDuration` is the same concept but in seconds; this one is ms to match the control's other timings.
- `AccessibleLabel` - accessible-name override for the guard. Default empty → the guard is `aria-labelledby` its visible face, so the name matches the visible text (WCAG 2.5.3). Set it only for an icon-only face (and make it contain any visible text).
- `CssUrl` - `'default'` publishes `assets/safetycover.css`; empty string registers no stylesheet

## Open effects

Three axes, all resolved server-side into CSS classes on the panel; the JS wrapper only toggles `safety-cover-open` and is unaware of them.

**Geometry** (`OverlayEffect`):

| Value | CSS mechanism | Overlay content |
|---|---|---|
| `Slide` | `transform: translate`, clipped by the slider's `overflow:hidden` | moves with the face |
| `Collapse` | `clip-path: inset` | stays put, wiped edge-to-edge like a shade |
| `None` | *(none)* | no geometric change |

The geometry (and fade) apply to the **face**, not the guard; the guard never moves.

**Fade** (`OverlayFade`, bool) is an independent axis: `safety-cover-fade` adds an `opacity` transition that layers on any geometry. So `Slide`+fade, `Collapse`+fade, and `None`+fade (a pure fade) all compose. `None` without fade snaps the face hidden with `visibility: hidden` and no animation.

**Direction** (`OverlayDirection`): sets CSS custom properties (`--safety-cover-translate`, `--safety-cover-clip`) on the panel that the geometry rule consumes. `Slide` and `Collapse` reveal the content in the same order (the face leaves toward the named edge); the difference is whether the face's content translates or is clipped. `None` emits no direction class.

`Forward`/`Backward` are logical (inline-axis) directions, resolved to `Right`/`Left` from the panel's `Direction` (`RightToLeft` flips them) in `getResolvedDirection()`. Resolving server-side avoids the `:dir()` CSS pseudo-class, which is below the project's browser baseline.

The framework classes lead the rendered class attribute; `addAttributesToRender()` composes `buildCssClass()` for output only and never mutates the stored `CssClass`, so `getCssClass()` returns exactly what the author set. Author classes are preserved as-is (including any that share the `safety-cover` prefix, e.g. a theme's `safety-cover-dark`); only a literal duplicate of a framework class is dropped.

**Guard vs face (safety):** the CSS transition lives on the face; the guard's `pointer-events` toggle instantly. So the close animates smoothly (face) while the guard blocks from the first frame — no exposure window. An earlier single-layer version transitioned the one overlay on both open and close, which left a ~90 ms window (measured) where the returning overlay had not yet re-covered the content and a click reached it; the two-layer split removes that window while keeping the close animation. `OverlayColor` is sanitized (`sanitizeOverlayColor()`) to a color-safe charset before rendering, so a data-bound value cannot inject extra CSS declarations.

**Reduced motion:** `@media (prefers-reduced-motion: reduce)` drops the open transition and the pulse animation; the `OpenDelay` stall (a safety feature, not decoration) still applies.

## Accessibility

The guard is a real button for keyboard/AT: `role="button"`, `aria-expanded`, `aria-controls`→content, and a name from the visible face by default (`aria-labelledby`→face; `AccessibleLabel` overrides to `aria-label`). Enter/Space on it opens the cover (the JS binds `keydown` alongside `click`). The guard is `tabindex="0"` when closed and drops to `-1` when open, so it is not a tabbable no-op once the content is revealed. `OverlayTemplate` content must be non-interactive — it renders inside the `role="button"` guard, where ARIA forbids interactive descendants.

The `setContentGuarded()` toggle is idempotent (it tracks the current state), so the no-`inert` fallback never re-reads an already-lowered tabindex as the value to restore — repeated guarding cannot strand a control at `tabindex="-1"`. `onDone()` (teardown) restores the content, so a wrapper that later re-registers on the same DOM starts unguarded and the fallback saves the real originals. `aria-expanded` flips to `true` on activation (before the OpenDelay pulse), acknowledging the action immediately to AT; `close()` resets it if the open is cancelled.

The `pointer-events` guard only blocks the mouse — keyboard focus ignores it — so the JS wrapper also marks the content `inert` while closed (with an `aria-hidden` + tabindex-sweep fallback for browsers lacking `inert`). This is the fix for the audit's headline finding: without it, a keyboard/AT user could Tab straight to the guarded button and fire it behind the cover (verified before the fix; the `TSafetyCoverAccessibilityTestCase` playwright spec now asserts the button is unfocusable while closed and reachable when open). On a keyboard open, focus moves into the content; on close, focus returns to the guard. `aria-expanded` tracks the state. Progressive enhancement: the `inert` guarding is applied by the wrapper on init, so with JS disabled the content stays reachable (unguarded) rather than permanently locked.

**Pulse duration:** the `--safety-cover-open-delay` custom property carries `OpenDelay` to the keyframe animation (`calc(var / 3)` for three pulses), so the pulse spans the delay at any `OpenDelay`. The pre-effect port hardcoded the pulse at 0.75 s, which desynchronized from a non-default `OpenDelay`.

## CSS contract

The cover **tracks** the content because the slider is `inset:0` inside the `position:relative` panel, and the panel sizes to its one in-flow child, the content div. It **guards** because the slider stacks at `z-index:1` above the content and the guard's `pointer-events` intercept the mouse. Both properties depend on a small set of invariants; overriding any of them (through `CssClass`, a theme, or a replacement `CssUrl`) breaks the control.

| Element | Must keep | Breaks if | Symptom |
|---|---|---|---|
| `.safety-cover` (root) | a positioned containing block: `position: relative` (default), or `absolute`/`fixed` | set to `position: static` | the slider positions against a distant ancestor — the cover lands elsewhere, or over the whole page |
| `.safety-cover-content` | normal flow; it is the panel's size source. `isolation: isolate` (bundled) | content made `position:absolute/fixed`, `float`, or `display:none` | the panel collapses to zero → slider/overlay/face shrink to nothing → nothing is covered |
| `.safety-cover-content` | `isolation: isolate` | removed while a positioned descendant has a high `z-index` | that descendant paints above the cover and is clickable through it (verified: without isolation a `z-index:9999` child is topmost over itself) |
| `.safety-cover-slider` | `overflow: hidden` | set to `overflow: visible` | the Slide face shows outside the panel while sliding (Collapse/Fade unaffected) |
| `.safety-cover-slider` | `z-index: 1` above content | content given a higher competing z-index at the same level | content pokes above the cover (mitigated by the content's `isolation`) |
| `.safety-cover-overlay` / `-face` | `inset: 0` (fill the slider) | insets changed | the cover no longer aligns with the content box |
| `.safety-cover-overlay` (guard) | `pointer-events` toggle: `auto` closed, `none` open | forced to a fixed value | the mouse guard stops blocking, or the content stays unreachable when open |

**Also required:** the content must have real size — its own content height or an explicit `Height` — or the panel (and cover) is zero-height and invisible. Empty covers in tests set `Width`/`Height` for this reason.

**Safe to customize freely:** `OverlayColor`, `OverlayCssClass`, and `OverlayTemplate` (the face's looks); the panel's padding and border (the cover extends over padding, which is fine); and any non-positioning `CssClass` on the control.

Keyboard/AT guarding does **not** depend on this CSS contract — it uses `inert`, which the wrapper toggles independently of stacking and pointer-events. So even a z-index escape that defeats the mouse guard leaves the keyboard/AT guard intact.

## Vocabulary

The control is the safety cover; the element that hides the content is the **overlay**. The distinction is deliberate: naming the part `Cover` would match the class name, which makes `CoverColor` ambiguous about whether it colors the whole control or the moving part. `Overlay` can only mean the part, and it keeps the CSS class free of the doubled `.safety-cover-cover`.

The inner elements are the slider (clips the animation), the guard `overlay` (transparent click-blocker), the `face` inside it (visible skin that animates), and the guarded content. `OverlayColor`/`OverlayTemplate` configure the face; the guard is purely functional.

## Rendered Structure

```html
<div id="{ClientID}" class="safety-cover safety-cover-slide safety-cover-up"
     style="--safety-cover-open-delay:800ms;--safety-cover-animation-duration:250ms">
    <div id="{ClientID}_slider" class="safety-cover-slider">
        <div id="{ClientID}_overlay" class="safety-cover-overlay">        <!-- guard: blocks clicks, never moves -->
            <div id="{ClientID}_face" class="safety-cover-face"> OverlayTemplate </div>  <!-- visible skin, animates -->
        </div>
    </div>
    <div id="{ClientID}_content" class="safety-cover-content"> body content </div>
</div>
```

The effect/direction classes and the `--safety-cover-*` variables are added by `addAttributesToRender()`. The effect class is on the panel but `safety-cover-open` is toggled on the slider, so the stylesheet's open rules are descendant (`.safety-cover-collapse .safety-cover-open .safety-cover-face`), not compound. `OverlayColor` and the `OverlayTemplate` render on the face; the guard stays transparent.

## Client Side

- JS class `Prado.WebUI.TSafetyCover` in `Web/Javascripts/source/prado/controls/safetycover.js`; package `safetycover` in `Web/Javascripts/packages.php`
- Wrapper registers in `Prado.Registry[ClientID]` with `open()`, `close()`, `isOpen()`
- Open state = `safety-cover-open` class on the slider; pulse = `safety-cover-pulsate` class on the root; `assets/safetycover.css` supplies the transitions and keyframes
- Vitest tests: `tests/js/controls/safetycover.test.js` (adapter `tests/js/adapters/safetycover.js`)

## History

Port of the pre-4.x `RConfirmPanel` (Rexode application, Prototype/Scriptaculous era). The port replaces `Effect.Pulsate`/`Effect.SlideUp` with CSS animations, moves the template instantiation into the control lifecycle, and replaces the hard-coded timings and inline CSS with properties and a published stylesheet. The class was named `TConfirmPanel` during the port; `TSafetyCover` replaced it to avoid implying a `confirm()` dialog.

## See Also

- [TPanel](./TPanel.md)

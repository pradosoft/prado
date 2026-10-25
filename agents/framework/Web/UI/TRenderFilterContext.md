# Web/UI/TRenderFilterContext

### Directories
[framework](../../INDEX.md) / [Web](../INDEX.md) / [UI](./INDEX.md) / **`TRenderFilterContext`**

## Class Info
**Location:** `framework/Web/UI/TRenderFilterContext.php`
**Namespace:** `Prado\Web\UI`
**Since:** 4.4.0

## Overview

Tracks the render filters capturing a [THtmlWriter](THtmlWriter.md)'s output and the controls exempt from them (`TControl.RenderFilterExempt`). Held by `THtmlWriter::getRenderFilterContext()`; `TControl::preRenderFilter()` creates it on the first capture.

A filter sees output unless the nearest `RenderFilterExempt` between the filter and the output is `true`. The setting on a control applies to the filters above it, so a control's own handlers and the filters inside it see its output.

A filter of a control with `TControl.RenderFilterDepth` N sees the output of the controls at most N levels below it (@since 4.4.0). Deeper output is hidden from that filter only; the filters of the deeper controls and the filters around the control see it.

## State

| Property | Meaning |
|---|---|
| `_filters` | Active captures, innermost last (`beginCapture()` / `endCapture()`); all are ancestors, since rendering is depth-first. Each is `[serial, depth limit, depth of the control rendering below the filtering control]` |
| `_serial` | Counter naming each capture's depth markers |
| `_exemptWriters` | Writer stack of exempt controls rendering into a buffer |
| `_exemptOutput` | Placeholder → exempt output |
| `_nonce` | Random part of placeholders and markers |

## Mechanism

| Event | Action |
|---|---|
| `beginExempt()` while capturing | push the inner writer, render into a fresh `TTextWriter` |
| `beginExempt()` not capturing | nothing; output renders unchanged (no filter to hide from) |
| `beginFilter()` inside an exempt buffer | write `<!--prado-filter-{nonce}-->` |
| `endFilter()` | write `<!--/prado-filter-{nonce}-->` |
| `endExempt()` | split the buffer at markers (by nesting depth); text outside markers → placeholders `<!--prado-exempt-{nonce}-{n}-->`, text inside → written in place; markers are kept until the outermost exempt control ends |
| `endCapture()` | remove the capture's depth markers |
| `endCapture()` of the outermost capture | restore placeholders, repeating `strtr` while nested placeholders appear (an exempt control inside an exempt control), clear, then remove the depth markers left in the restored exempt output |
| `beginControl()` / `endControl()` (`TControl::preRenderFilterDepth()` / `processRenderFilterDepth()`) | for each capture with a depth limit, move the depth one level down/up; return `<!--prado-depth-{nonce}-{serial}-{depth}-->` / `<!--/…-->` around a control at depth 2 through limit + 1 |
| `createDepthParameter()` | with a depth limit on the innermost capture, a `TRenderFilterParameter` whose `setDepthMarker()` receives the capture's marker prefix and limit, hiding the deeper output; `null` without one |
| `filterOutput()` | with a depth parameter, raise `onRenderFilter` on it (as `filterFragment()` does for ancestors); otherwise call `onRenderFilter($output)` |
| `wrapDepth()` | enclose a callback re-render in the innermost capture's markers of the levels above it (`beginCapture($limit, $depth)` starts the capture `$depth` levels below its control) |

Re-enabled output inside an exempt control passes through the filters inside the exempt control first, then reaches the outer filters between the placeholders; the markers locate it in the inner filters' output.

## Limits

- Output rendered into another `THtmlWriter` has no context; exemption covers the filters of the control's own writer.
- A handler that deletes a placeholder deletes the exempt output; one that rewrites `prado-exempt`/`prado-filter` comment text breaks the restore. Markers a handler removes leave the re-enabled output exempt.
- Control and markup ([`data-prado-filter-exempt`](TRenderFilterParameter.md)) exemptions each re-enable only within their own kind.
- Markers exist only for depths 2 through limit + 1, so a handler's `DepthLimit` can narrow the control's `RenderFilterDepth` or turn it off (0), but cannot widen it to another number.
- `RenderFilterExempt` `false` does not override a depth limit.

## See Also

- [TControl](TControl.md) — `RenderFilterExempt`, `RenderFilterDepth`, `preRenderFilterExempt()`, `processRenderFilterExempt()`, `preRenderFilterDepth()`, `processRenderFilterDepth()`
- [TRenderFilterParameter](TRenderFilterParameter.md) — markup exemption

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

## State

| Property | Meaning |
|---|---|
| `_depth` | Active captures (`beginCapture()` / `endCapture()`); all are ancestors, since rendering is depth-first |
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
| `endCapture()` at depth 0 | restore placeholders, repeating `strtr` while nested placeholders appear (an exempt control inside an exempt control), then clear |

Re-enabled output inside an exempt control passes through the filters inside the exempt control first, then reaches the outer filters between the placeholders; the markers locate it in the inner filters' output.

## Limits

- Output rendered into another `THtmlWriter` has no context; exemption covers the filters of the control's own writer.
- A handler that deletes a placeholder deletes the exempt output; one that rewrites `prado-exempt`/`prado-filter` comment text breaks the restore. Markers a handler removes leave the re-enabled output exempt.
- Control and markup ([`data-prado-filter-exempt`](TRenderFilterParameter.md)) exemptions each re-enable only within their own kind.

## See Also

- [TControl](TControl.md) — `RenderFilterExempt`, `preRenderFilterExempt()`, `processRenderFilterExempt()`
- [TRenderFilterParameter](TRenderFilterParameter.md) — markup exemption

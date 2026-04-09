# TXmlTransform

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [UI](../INDEX.md) > [WebControls](./INDEX.md) > [TXmlTransform](./TXmlTransform.md)

**Location:** `framework/Web/UI/WebControls/TXmlTransform.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview

TXmlTransform uses PHP's XSL extension to perform XSL transformations. It transforms XML documents using XSL stylesheets.

## Key Properties/Methods

- `getTransformPath()` / `setTransformPath($value)` - Path to XSL stylesheet
- `getTransformContent()` / `setTransformContent($value)` - XSL stylesheet as string
- `getDocumentPath()` / `setDocumentPath($value)` - Path to XML document
- `getDocumentContent()` / `setDocumentContent($value)` - XML data as string
- `getParameters()` - Returns TAttributeCollection of transformation parameters
- `render($writer)` - Performs XSL transformation and writes output

## See Also

- [TControl](./TControl.md)

# Web/UI/WebControls/TImage

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TImage`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TImage.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview
TImage displays an image on a page. It provides functionality for specifying image URL, alternative text, and various display options including alignment and description.

## Key Features
- **Image Display**: Shows images from local or external URLs
- **Alternative Text**: Supports alt text for accessibility and SEO
- **Image Description**: Includes long description URL for accessibility
- **Image Alignment**: Supports traditional image alignment properties
- **HTML Generation**: Renders as `<img>` tag with all relevant attributes
- **Responsive Design**: Works with CSS styling for responsive layouts

## Core Properties
- `ImageUrl` (string): URL to the image file (relative or absolute)
- `AlternateText` (string): Alternative text displayed when image is unavailable
- `DescriptionUrl` (string): Long description URL for accessibility
- `ImageAlign` (string): Image alignment with respect to other elements
- `ImageStyle` (string): CSS style for image display
- `Width` (string): Image width
- `Height` (string): Image height
- `Border` (string): Border width
- `Margin` (string): Margin around image
- `Padding` (string): Padding inside image

## Core Methods

### Image Properties
- `getImageUrl()`: Gets image URL
- `setImageUrl($value)`: Sets image URL
- `getAlternateText()`: Gets alternative text
- `setAlternateText($value)`: Sets alternative text
- `getDescriptionUrl()`: Gets description URL
- `setDescriptionUrl($value)`: Sets description URL
- `getImageAlign()`: Gets image alignment
- `setImageAlign($value)`: Sets image alignment

### Rendering
- `getTagName()`: Gets HTML tag name ('img')
- `addAttributesToRender($writer)`: Adds image attributes to HTML writer
- `renderContents($writer)`: No content rendering (img tags are self-closing)
- `render($writer)`: Renders complete HTML img tag
- `renderBeginTag($writer)`: Renders opening image tag
- `renderEndTag($writer)`: Renders closing image tag (not applicable for img tags)

### Styling
- `getStyle()`: Gets CSS style for image
- `setStyle($value)`: Sets CSS style for image
- `getCssClass()`: Gets CSS class for image
- `setCssClass($value)`: Sets CSS class for image

### Image Display Options
- Width and height properties for controlling image dimensions
- Border styling for image borders
- Margin and padding for image spacing
- CSS styling for advanced layout control

## Image Alignment Options (deprecated)
- **absbottom**: Aligns bottom of image with bottom of text
- **absmiddle**: Aligns middle of image with middle of text
- **baseline**: Aligns baseline of image with baseline of text
- **bottom**: Aligns bottom of image with bottom of text
- **left**: Aligns left side of image with left side of text
- **middle**: Aligns middle of image with middle of text
- **right**: Aligns right side of image with right side of text
- **texttop**: Aligns top of image with top of text
- **top**: Aligns top of image with top of text

## Usage Example
```php
// Create image
$image = new TImage();
$image->ID = "logo";
$image->setImageUrl("images/logo.png");
$image->setAlternateText("Company Logo");
$image->setDescriptionUrl("accessibility/logo-description.html");
$image->setStyle("width: 200px; height: 100px; border: 1px solid #ccc;");

// Add to page
$panel->getControls()->add($image);

// Render
$writer = new [THtmlWriter](./THtmlWriter.md)();
$image->render($writer);
```
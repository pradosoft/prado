<?php

/**
 * TPicture class file
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * TPicture class
 * TPicture represents the HTML `<picture>` element, an image with alternative
 * resources for different formats, screen densities, or layouts. It renders
 * `<picture>`, then its child {@see TMediaSource} controls, then the `<img>` of
 * its {@see TImage} properties. The browser shows the first source whose `Type`
 * and `Media` it supports, and otherwise {@see setImageUrl() ImageUrl}.
 * ```html
 * <com:TPicture ImageUrl="chart.png" AlternateText="Sales rose 20% in 2026">
 *     <com:TMediaSource Srcset="chart.avif" Type="image/avif" />
 *     <com:TMediaSource Srcset="chart.webp" Type="image/webp" />
 * </com:TPicture>
 * ```
 * The attributes of the control, including `ID`, `CssClass`, and `Style`,
 * render on the `<img>`; the `<picture>` has none.
 * Accessibility: `<picture>` has no role; the `<img>` is the image, named by
 * {@see setAlternateText() AlternateText}. Every source shows the same image
 * content, so one text alternative describes them all. A decorative image keeps
 * AlternateText empty, which renders `alt=""`.
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TPicture extends TImage
{
	/**
	 * Renders `<picture>` holding the child controls and then the `<img>`.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 */
	public function render($writer)
	{
		$writer->renderBeginTag('picture');
		$this->renderChildren($writer);
		parent::render($writer);
		$writer->renderEndTag();
	}
}

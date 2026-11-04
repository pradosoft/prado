<?php

/**
 * TOpenGraph class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\TPropertyValue;

/**
 * TOpenGraph class
 *
 * TOpenGraph renders the Open Graph `og:*` and X (Twitter) Card `twitter:*` meta
 * tags that a social platform reads to preview a shared page. Each property
 * renders one {@see TMetaTag}; an empty property renders nothing.
 * ```php
 * <com:THead>
 *   <com:TOpenGraph Title="Blue Widget" Description="A widget, in blue."
 *     Url="https://example.com/widgets/blue" Image="https://example.com/img/blue.png"
 *     ImageAlt="A blue widget" SiteName="Acme" TwitterCard="summary_large_image">
 *     <com:TMetaTag Property="product:price:amount" Content="9.95" />
 *   </com:TOpenGraph>
 * </com:THead>
 * ```
 *
 * | Property | Meta tag |
 * |---|---|
 * | {@see setTitle Title} | `og:title` |
 * | {@see setType Type} | `og:type`, `website` by default |
 * | {@see setUrl Url} | `og:url`: the canonical URL |
 * | {@see setDescription Description} | `og:description` |
 * | {@see setSiteName SiteName} | `og:site_name` |
 * | {@see setLocale Locale} | `og:locale`, such as `en_US` |
 * | {@see setImage Image} | `og:image` |
 * | {@see setImageAlt ImageAlt} | `og:image:alt` |
 * | {@see setImageWidth ImageWidth}, {@see setImageHeight ImageHeight} | `og:image:width`, `og:image:height`; 0 renders none |
 * | {@see setTwitterCard TwitterCard} | `twitter:card`: `summary`, `summary_large_image`, `app`, or `player` |
 * | {@see setTwitterSite TwitterSite} | `twitter:site`: the site's `@handle` |
 * | {@see setTwitterCreator TwitterCreator} | `twitter:creator`: the author's `@handle` |
 *
 * `og:*` tags render with the `property` attribute and `twitter:*` tags with
 * `name`. An X Card without its own title, description, or image uses the
 * `og:*` ones. Crawlers require absolute `Url` and `Image` URLs.
 *
 * A {@see TMetaTag} in the body joins {@see getMetaTags MetaTags} and renders
 * after the tags above, for metadata such as `article:published_time` or a
 * second `og:image`. A TOpenGraph in a content placeholder of the head lets
 * each page supply its own tags.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TOpenGraph extends \Prado\Web\UI\TControl
{
	/**
	 * The default Open Graph object type.
	 */
	public const DEFAULT_TYPE = 'website';

	/**
	 * @return string the title of the object. Defaults to empty.
	 */
	public function getTitle()
	{
		return $this->getViewState('Title', '');
	}

	/**
	 * @param string $value the title of the object (`og:title`)
	 */
	public function setTitle($value)
	{
		$this->setViewState('Title', TPropertyValue::ensureString($value), '');
	}

	/**
	 * @return string the type of the object. Defaults to `website`.
	 */
	public function getType()
	{
		return $this->getViewState('Type', self::DEFAULT_TYPE);
	}

	/**
	 * Sets the type of the object (`og:type`), such as `website`, `article`,
	 * `profile`, or `video.movie`; empty renders none.
	 * @param string $value the object type
	 */
	public function setType($value)
	{
		$this->setViewState('Type', TPropertyValue::ensureString($value), self::DEFAULT_TYPE);
	}

	/**
	 * @return string the canonical URL of the object. Defaults to empty.
	 */
	public function getUrl()
	{
		return $this->getViewState('Url', '');
	}

	/**
	 * @param string $value the absolute canonical URL of the object (`og:url`)
	 */
	public function setUrl($value)
	{
		$this->setViewState('Url', TPropertyValue::ensureString($value), '');
	}

	/**
	 * @return string the description of the object. Defaults to empty.
	 */
	public function getDescription()
	{
		return $this->getViewState('Description', '');
	}

	/**
	 * @param string $value a one- or two-sentence description (`og:description`)
	 */
	public function setDescription($value)
	{
		$this->setViewState('Description', TPropertyValue::ensureString($value), '');
	}

	/**
	 * @return string the name of the site. Defaults to empty.
	 */
	public function getSiteName()
	{
		return $this->getViewState('SiteName', '');
	}

	/**
	 * @param string $value the name of the site (`og:site_name`)
	 */
	public function setSiteName($value)
	{
		$this->setViewState('SiteName', TPropertyValue::ensureString($value), '');
	}

	/**
	 * @return string the locale of the object. Defaults to empty.
	 */
	public function getLocale()
	{
		return $this->getViewState('Locale', '');
	}

	/**
	 * @param string $value the locale of the object (`og:locale`), such as `en_US`
	 */
	public function setLocale($value)
	{
		$this->setViewState('Locale', TPropertyValue::ensureString($value), '');
	}

	/**
	 * @return string the URL of the preview image. Defaults to empty.
	 */
	public function getImage()
	{
		return $this->getViewState('Image', '');
	}

	/**
	 * @param string $value the absolute URL of the preview image (`og:image`)
	 */
	public function setImage($value)
	{
		$this->setViewState('Image', TPropertyValue::ensureString($value), '');
	}

	/**
	 * @return string the text alternative of the preview image. Defaults to empty.
	 */
	public function getImageAlt()
	{
		return $this->getViewState('ImageAlt', '');
	}

	/**
	 * @param string $value the text alternative of the preview image (`og:image:alt`)
	 */
	public function setImageAlt($value)
	{
		$this->setViewState('ImageAlt', TPropertyValue::ensureString($value), '');
	}

	/**
	 * @return int the width of the preview image, in pixels. Defaults to 0.
	 */
	public function getImageWidth()
	{
		return $this->getViewState('ImageWidth', 0);
	}

	/**
	 * @param int $value the width of the preview image, in pixels (`og:image:width`)
	 */
	public function setImageWidth($value)
	{
		$this->setViewState('ImageWidth', TPropertyValue::ensureInteger($value), 0);
	}

	/**
	 * @return int the height of the preview image, in pixels. Defaults to 0.
	 */
	public function getImageHeight()
	{
		return $this->getViewState('ImageHeight', 0);
	}

	/**
	 * @param int $value the height of the preview image, in pixels (`og:image:height`)
	 */
	public function setImageHeight($value)
	{
		$this->setViewState('ImageHeight', TPropertyValue::ensureInteger($value), 0);
	}

	/**
	 * @return string the X Card type. Defaults to empty.
	 */
	public function getTwitterCard()
	{
		return $this->getViewState('TwitterCard', '');
	}

	/**
	 * @param string $value the X Card type (`twitter:card`): `summary`,
	 *   `summary_large_image`, `app`, or `player`
	 */
	public function setTwitterCard($value)
	{
		$this->setViewState('TwitterCard', TPropertyValue::ensureString($value), '');
	}

	/**
	 * @return string the X handle of the site. Defaults to empty.
	 */
	public function getTwitterSite()
	{
		return $this->getViewState('TwitterSite', '');
	}

	/**
	 * @param string $value the X handle of the site (`twitter:site`), such as `@acme`
	 */
	public function setTwitterSite($value)
	{
		$this->setViewState('TwitterSite', TPropertyValue::ensureString($value), '');
	}

	/**
	 * @return string the X handle of the author. Defaults to empty.
	 */
	public function getTwitterCreator()
	{
		return $this->getViewState('TwitterCreator', '');
	}

	/**
	 * @param string $value the X handle of the author (`twitter:creator`)
	 */
	public function setTwitterCreator($value)
	{
		$this->setViewState('TwitterCreator', TPropertyValue::ensureString($value), '');
	}

	/**
	 * @return TMetaTagCollection the additional meta tags, rendered after the
	 *   property tags
	 */
	public function getMetaTags()
	{
		if (($metaTags = $this->getViewState('MetaTags', null)) === null) {
			$metaTags = new TMetaTagCollection();
			$this->setViewState('MetaTags', $metaTags, null);
		}
		return $metaTags;
	}

	/**
	 * Adds a TMetaTag in the template to {@see getMetaTags MetaTags}.
	 * @param \Prado\TComponent|string $object text string or component parsed and instantiated in template
	 */
	public function addParsedObject($object)
	{
		if ($object instanceof TMetaTag) {
			$this->getMetaTags()->add($object);
		} else {
			parent::addParsedObject($object);
		}
	}

	/**
	 * Builds the meta tags of the properties, in rendering order; an empty
	 * property has none.
	 * @return TMetaTag[] the property meta tags
	 */
	public function createPropertyMetaTags(): array
	{
		$width = $this->getImageWidth();
		$height = $this->getImageHeight();
		$properties = [
			'og:title' => $this->getTitle(),
			'og:type' => $this->getType(),
			'og:url' => $this->getUrl(),
			'og:description' => $this->getDescription(),
			'og:site_name' => $this->getSiteName(),
			'og:locale' => $this->getLocale(),
			'og:image' => $this->getImage(),
			'og:image:alt' => $this->getImageAlt(),
			'og:image:width' => $width > 0 ? (string) $width : '',
			'og:image:height' => $height > 0 ? (string) $height : '',
		];
		$names = [
			'twitter:card' => $this->getTwitterCard(),
			'twitter:site' => $this->getTwitterSite(),
			'twitter:creator' => $this->getTwitterCreator(),
		];
		$metaTags = [];
		foreach ($properties as $property => $content) {
			if ($content !== '') {
				$metaTag = new TMetaTag();
				$metaTag->setProperty($property);
				$metaTag->setContent($content);
				$metaTags[] = $metaTag;
			}
		}
		foreach ($names as $name => $content) {
			if ($content !== '') {
				$metaTag = new TMetaTag();
				$metaTag->setName($name);
				$metaTag->setContent($content);
				$metaTags[] = $metaTag;
			}
		}
		return $metaTags;
	}

	/**
	 * Renders the property meta tags, then {@see getMetaTags MetaTags}, then the
	 * child controls.
	 * @param \Prado\Web\UI\THtmlWriter $writer writer for the rendering purpose
	 */
	public function render($writer)
	{
		foreach ($this->createPropertyMetaTags() as $metaTag) {
			$metaTag->render($writer);
			$writer->writeLine();
		}
		foreach ($this->getMetaTags() as $metaTag) {
			$metaTag->render($writer);
			$writer->writeLine();
		}
		parent::render($writer);
	}
}

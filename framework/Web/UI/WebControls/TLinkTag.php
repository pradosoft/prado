<?php

/**
 * TLinkTag class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\Exceptions\TInvalidDataValueException;
use Prado\TPropertyValue;
use Prado\Web\Javascripts\TJavaScript;

/**
 * TLinkTag class
 *
 * TLinkTag represents a `<link>` element in the page head: a relation between the
 * page and another resource. {@see THead} renders its
 * {@see THead::getLinkTags LinkTags} after the meta tags and before the page's
 * stylesheets and head scripts, so a preload or preconnect starts early.
 * ```html
 * <com:THead>
 *   <com:TLinkTag Rel="canonical" Href="https://example.com/products" />
 *   <com:TLinkTag Rel="alternate" HrefLang="fr" Href="https://example.com/fr/products" />
 *   <com:TLinkTag Rel="preconnect" Href="https://cdn.example.com" CrossOrigin="anonymous" />
 *   <com:TLinkTag Rel="preload" Href="fonts/body.woff2" As="font" Type="font/woff2" CrossOrigin="anonymous" />
 *   <com:TLinkTag Rel="icon" Href="icon-192.png" Sizes="192x192" Type="image/png" />
 *   <com:TLinkTag Rel="manifest" Href="app.webmanifest" />
 * </com:THead>
 * ```
 *
 * | Property | Attribute |
 * |---|---|
 * | {@see setID ID} | `id` |
 * | {@see setRel Rel} | `rel`: one or more space-separated link types |
 * | {@see setHref Href} | `href` |
 * | {@see setHrefLang HrefLang} | `hreflang` |
 * | {@see setType Type} | `type`: the MIME type of the resource |
 * | {@see setMedia Media} | `media`: the media query the resource applies to |
 * | {@see setSizes Sizes} | `sizes`: icon sizes |
 * | {@see setAs As} | `as`: the destination of a preload |
 * | {@see setImageSrcset ImageSrcset}, {@see setImageSizes ImageSizes} | `imagesrcset`, `imagesizes`: a responsive image preload |
 * | {@see setCrossOrigin CrossOrigin} | `crossorigin`: `anonymous` or `use-credentials` |
 * | {@see setIntegrity Integrity} | `integrity`: Subresource Integrity hash |
 * | {@see setFetchPriority FetchPriority} | `fetchpriority` ({@see TFetchPriority}) |
 * | {@see setReferrerPolicy ReferrerPolicy} | `referrerpolicy` ({@see TReferrerPolicy}) |
 * | {@see setTitle Title} | `title`: the name of an alternate stylesheet or feed |
 *
 * A `stylesheet`, `preload`, or `modulepreload` link carries the request's CSP
 * nonce, when one is active. A font preload needs `CrossOrigin`, even from the
 * same origin. Page and theme stylesheets register through
 * {@see \Prado\Web\UI\TClientScriptManager::registerStyleSheetFile()} instead, so
 * they render in order with the theme and reach a callback.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TLinkTag extends \Prado\TComponent
{
	/**
	 * @var string[] link types whose request a CSP nonce governs
	 */
	private const NONCE_RELS = ['stylesheet', 'preload', 'modulepreload'];

	/** @var string id of the link tag */
	private $_id = '';
	/** @var string rel attribute of the link tag */
	private $_rel = '';
	/** @var string href attribute of the link tag */
	private $_href = '';
	/** @var string hreflang attribute of the link tag */
	private $_hrefLang = '';
	/** @var string type attribute of the link tag */
	private $_type = '';
	/** @var string media attribute of the link tag */
	private $_media = '';
	/** @var string sizes attribute of the link tag */
	private $_sizes = '';
	/** @var string as attribute of the link tag */
	private $_as = '';
	/** @var string imagesrcset attribute of the link tag */
	private $_imageSrcset = '';
	/** @var string imagesizes attribute of the link tag */
	private $_imageSizes = '';
	/** @var string crossorigin attribute of the link tag */
	private $_crossOrigin = '';
	/** @var string integrity attribute of the link tag */
	private $_integrity = '';
	/** @var string fetchpriority of the link tag, a TFetchPriority */
	private $_fetchPriority = TFetchPriority::NotSet;
	/** @var string referrerpolicy of the link tag, a TReferrerPolicy */
	private $_referrerPolicy = TReferrerPolicy::NotSet;
	/** @var string title attribute of the link tag */
	private $_title = '';

	/**
	 * @return string id of the link tag. Defaults to empty.
	 */
	public function getID()
	{
		return $this->_id;
	}

	/**
	 * @param string $value id of the link tag
	 */
	public function setID($value)
	{
		$this->_id = TPropertyValue::ensureString($value);
	}

	/**
	 * @return string the link types, space separated. Defaults to empty.
	 */
	public function getRel()
	{
		return $this->_rel;
	}

	/**
	 * Sets the relation of the resource to the page, such as `canonical`,
	 * `alternate`, `icon`, `manifest`, `preload`, or `preconnect`.
	 * @param string $value one or more link types; whitespace is collapsed.
	 */
	public function setRel($value)
	{
		$this->_rel = self::normalizeTokens($value);
	}

	/**
	 * @return string the URL of the linked resource. Defaults to empty.
	 */
	public function getHref()
	{
		return $this->_href;
	}

	/**
	 * @param string $value the URL of the linked resource
	 */
	public function setHref($value)
	{
		$this->_href = TPropertyValue::ensureString($value);
	}

	/**
	 * @return string the language of the linked resource. Defaults to empty.
	 */
	public function getHrefLang()
	{
		return $this->_hrefLang;
	}

	/**
	 * Sets the language of the linked resource, a BCP 47 tag; `x-default` marks the
	 * fallback of a set of `alternate` links.
	 * @param string $value the language tag
	 */
	public function setHrefLang($value)
	{
		$this->_hrefLang = TPropertyValue::ensureString($value);
	}

	/**
	 * @return string the MIME type of the linked resource. Defaults to empty.
	 */
	public function getType()
	{
		return $this->_type;
	}

	/**
	 * @param string $value the MIME type of the linked resource
	 */
	public function setType($value)
	{
		$this->_type = TPropertyValue::ensureString($value);
	}

	/**
	 * @return string the media query the resource applies to. Defaults to empty.
	 */
	public function getMedia()
	{
		return $this->_media;
	}

	/**
	 * @param string $value the media query, such as `(prefers-color-scheme: dark)`
	 */
	public function setMedia($value)
	{
		$this->_media = TPropertyValue::ensureString($value);
	}

	/**
	 * @return string the sizes of an icon. Defaults to empty.
	 */
	public function getSizes()
	{
		return $this->_sizes;
	}

	/**
	 * @param string $value the icon sizes, such as `32x32 64x64` or `any`
	 */
	public function setSizes($value)
	{
		$this->_sizes = self::normalizeTokens($value);
	}

	/**
	 * @return string the destination of a preload. Defaults to empty.
	 */
	public function getAs()
	{
		return $this->_as;
	}

	/**
	 * Sets the destination of a `preload`, such as `font`, `image`, `script`, or
	 * `style`; the browser applies that destination's CSP directive and priority.
	 * @param string $value the destination; it is lowercased.
	 */
	public function setAs($value)
	{
		$this->_as = strtolower(trim(TPropertyValue::ensureString($value)));
	}

	/**
	 * @return string the image candidates of a responsive image preload. Defaults to empty.
	 */
	public function getImageSrcset()
	{
		return $this->_imageSrcset;
	}

	/**
	 * @param string $value the image candidates, as in `<img srcset>`
	 */
	public function setImageSrcset($value)
	{
		$this->_imageSrcset = TPropertyValue::ensureString($value);
	}

	/**
	 * @return string the display sizes of a responsive image preload. Defaults to empty.
	 */
	public function getImageSizes()
	{
		return $this->_imageSizes;
	}

	/**
	 * @param string $value the display sizes, as in `<img sizes>`
	 */
	public function setImageSizes($value)
	{
		$this->_imageSizes = TPropertyValue::ensureString($value);
	}

	/**
	 * @return string the CORS mode of the request: '', 'anonymous', or
	 *   'use-credentials'. Defaults to ''.
	 */
	public function getCrossOrigin()
	{
		return $this->_crossOrigin;
	}

	/**
	 * Sets the CORS mode of the request (`crossorigin`).
	 * @param string $value '', 'anonymous', or 'use-credentials'
	 * @throws TInvalidDataValueException if the value is not one of those.
	 */
	public function setCrossOrigin($value)
	{
		$value = strtolower(trim(TPropertyValue::ensureString($value)));
		if ($value !== '' && $value !== 'anonymous' && $value !== 'use-credentials') {
			throw new TInvalidDataValueException('media_crossorigin_invalid', $this::class, $value);
		}
		$this->_crossOrigin = $value;
	}

	/**
	 * @return string the Subresource Integrity hash. Defaults to empty.
	 */
	public function getIntegrity()
	{
		return $this->_integrity;
	}

	/**
	 * @param string $value the Subresource Integrity hash, such as `sha384-…`
	 */
	public function setIntegrity($value)
	{
		$this->_integrity = TPropertyValue::ensureString($value);
	}

	/**
	 * @return string the request priority, a {@see TFetchPriority}. Defaults to NotSet.
	 */
	public function getFetchPriority()
	{
		return $this->_fetchPriority;
	}

	/**
	 * @param string $value the request priority, a {@see TFetchPriority}
	 */
	public function setFetchPriority($value)
	{
		$this->_fetchPriority = TPropertyValue::ensureEnum($value, TFetchPriority::class);
	}

	/**
	 * @return string the referrer policy of the request, a {@see TReferrerPolicy}.
	 *   Defaults to NotSet.
	 */
	public function getReferrerPolicy()
	{
		return $this->_referrerPolicy;
	}

	/**
	 * @param string $value the referrer policy of the request, a {@see TReferrerPolicy}
	 */
	public function setReferrerPolicy($value)
	{
		$this->_referrerPolicy = TPropertyValue::ensureEnum($value, TReferrerPolicy::class);
	}

	/**
	 * @return string the title of the link. Defaults to empty.
	 */
	public function getTitle()
	{
		return $this->_title;
	}

	/**
	 * Sets the title, which names an alternate stylesheet or an `alternate` feed.
	 * @param string $value the title
	 */
	public function setTitle($value)
	{
		$this->_title = TPropertyValue::ensureString($value);
	}

	/**
	 * Returns whether the link carries the CSP nonce: its Rel has a link type whose
	 * request the nonce governs.
	 * @return bool whether the nonce renders
	 */
	protected function getNeedsNonce(): bool
	{
		$rels = explode(' ', strtolower($this->getRel()));
		return array_intersect($rels, self::NONCE_RELS) !== [];
	}

	/**
	 * Renders the link tag.
	 * @param \Prado\Web\UI\THtmlWriter $writer writer for the rendering purpose
	 */
	public function render($writer)
	{
		$attributes = [
			'id' => $this->getID(),
			'rel' => $this->getRel(),
			'href' => $this->getHref(),
			'hreflang' => $this->getHrefLang(),
			'type' => $this->getType(),
			'media' => $this->getMedia(),
			'sizes' => $this->getSizes(),
			'as' => $this->getAs(),
			'imagesrcset' => $this->getImageSrcset(),
			'imagesizes' => $this->getImageSizes(),
			'crossorigin' => $this->getCrossOrigin(),
			'integrity' => $this->getIntegrity(),
			'title' => $this->getTitle(),
		];
		if (($priority = $this->getFetchPriority()) !== TFetchPriority::NotSet) {
			$attributes['fetchpriority'] = strtolower($priority);
		}
		if (($policy = $this->getReferrerPolicy()) !== TReferrerPolicy::NotSet) {
			$attributes['referrerpolicy'] = TReferrerPolicy::toAttributeValue($policy);
		}
		if ($this->getNeedsNonce() && ($nonce = TJavaScript::getScriptNonce()) !== null) {
			$attributes['nonce'] = $nonce;
		}
		foreach ($attributes as $name => $value) {
			if ($value !== '') {
				$writer->addAttribute($name, $value);
			}
		}
		$writer->renderBeginTag('link');
		$writer->renderEndTag();
	}

	/**
	 * Trims a space-separated token list and collapses its whitespace.
	 * @param mixed $value the token list
	 * @return string the normalized token list
	 */
	private static function normalizeTokens($value): string
	{
		return trim(preg_replace('/\s+/', ' ', TPropertyValue::ensureString($value)));
	}
}

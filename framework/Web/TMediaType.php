<?php

/**
 * TMediaType class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web;

use Prado\Web\HttpHeaders\THeaderParametersTrait;

/**
 * TMediaType class
 *
 * TMediaType is a mutable value object that parses, represents, and renders
 * an HTTP media type — the structured value carried by headers such as
 * `Content-Type` and `Accept` — as defined by RFC 9110 §8.3.1:
 *
 * ```
 * media-type = type "/" subtype *( OWS ";" OWS parameter )
 * parameter  = token "=" ( token / quoted-string )
 * ```
 *
 * Properties:
 * - {@see getType() Type} — the top-level type, e.g. `text`.
 * - {@see getSubtype() Subtype} — the subtype, e.g. `html`.
 * - {@see getMimeType() MimeType} — combined `type/subtype`, e.g. `text/html`.
 *   Assigning this property parses both type and subtype from the string.
 * - {@see getParameters() Parameters} — associative array of parameter
 *   names (lowercase) to values, e.g. `['charset' => 'UTF-8']`.
 * - {@see getCharset() Charset} — convenience accessor for the `charset`
 *   parameter; returns `null` when absent.
 *
 * ```php
 * $mt = new TMediaType('text/html; charset=UTF-8');
 * $mt->getType();          // 'text'
 * $mt->getSubtype();       // 'html'
 * $mt->getMimeType();      // 'text/html'
 * $mt->getCharset();       // 'UTF-8'
 * (string) $mt;            // 'text/html; charset=UTF-8'
 *
 * $mt->setCharset('ISO-8859-1');
 * (string) $mt;            // 'text/html; charset=ISO-8859-1'
 *
 * // Use named constants instead of bare strings
 * $mt = new TMediaType(TMediaType::JSON);
 * ```
 *
 * **Named constants.** Commonly used media type strings are available as
 * class constants for IDE completion and typo prevention:
 *
 * *Text:* {@see HTML}, {@see PLAIN}, {@see CSS}, {@see JAVASCRIPT},
 * {@see EVENT_STREAM}, {@see XML_TEXT}, {@see CSV}, {@see MARKDOWN},
 * {@see CALENDAR}
 *
 * *Application:* {@see JSON}, {@see JSON_LD}, {@see XML}, {@see XHTML},
 * {@see FORM}, {@see OCTET_STREAM}, {@see PDF}, {@see ZIP},
 * {@see GZIP}, {@see TAR}, {@see BZIP2}, {@see XZ}, {@see RTF}, {@see WASM}
 *
 * *Multipart:* {@see MULTIPART}
 *
 * *Syndication / feeds:* {@see RSS}, {@see ATOM}, {@see RDF}
 *
 * *Office documents:* {@see DOCX}, {@see XLSX}, {@see PPTX},
 * {@see DOC}, {@see XLS}, {@see PPT}
 *
 * *CSP / Reporting API:* {@see CSP_REPORT}, {@see REPORTS_JSON}
 *
 * *Image:* {@see PNG}, {@see JPEG}, {@see GIF}, {@see WEBP}, {@see AVIF},
 * {@see SVG}, {@see ICON}, {@see BMP}, {@see TIFF}
 *
 * *Audio:* {@see AUDIO_MPEG}, {@see AUDIO_OGG}, {@see AUDIO_WAV},
 * {@see AUDIO_WEBM}, {@see AUDIO_AAC}
 *
 * *Video:* {@see VIDEO_MP4}, {@see VIDEO_WEBM}, {@see VIDEO_OGG}
 *
 * *Font:* {@see WOFF}, {@see WOFF2}, {@see TTF}, {@see OTF}
 *
 * *Defaults:* {@see DEFAULT_TYPE} (`'text'`), {@see DEFAULT_SUBTYPE} (`'html'`) —
 * override in a subclass to change the no-argument default.
 *
 * **ArrayAccess.** Parameters are also accessible via array syntax via
 * {@see THeaderParametersTrait}, making `TMediaType` a transparent pipe to
 * its parameter map:
 *
 * ```php
 * $mt = new TMediaType('text/html; charset=UTF-8');
 * $mt['charset'];            // 'UTF-8'    (offsetGet  → getParameter)
 * isset($mt['charset']);     // true       (offsetExists → hasParameter)
 * $mt['charset'] = 'ASCII'; //            (offsetSet  → setParameter)
 * unset($mt['charset']);     //            (offsetUnset → removeParameter)
 * ```
 *
 * Parameter names are normalized to lowercase by the underlying methods,
 * so `$mt['Charset']` and `$mt['charset']` address the same entry.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 * @see https://www.rfc-editor.org/rfc/rfc9110#section-8.3.1 RFC 9110 §8.3.1 (Content-Type / media-type)
 * @see https://www.rfc-editor.org/rfc/rfc7231#section-3.1.1 RFC 7231 §3.1.1 (superseded by RFC 9110)
 */
class TMediaType implements \ArrayAccess
{
	use THeaderParametersTrait;

	// =========================================================================
	// Named constants
	// =========================================================================

	// ---- Text ----

	/** `text/html` — HTML document. */
	public const HTML = 'text/html';

	/** `text/plain` — Plain text. */
	public const PLAIN = 'text/plain';

	/** `text/css` — CSS stylesheet. */
	public const CSS = 'text/css';

	/** `text/javascript` — JavaScript source file (current IANA registration). */
	public const JAVASCRIPT = 'text/javascript';

	/** `text/xml` — XML document sent as text. Prefer {@see XML} for API responses. */
	public const XML_TEXT = 'text/xml';

	/** `text/csv` — Comma-separated values. */
	public const CSV = 'text/csv';

	/** `text/event-stream` — Server-sent events (SSE). */
	public const EVENT_STREAM = 'text/event-stream';

	/** `text/markdown` — Markdown document (RFC 7763). */
	public const MARKDOWN = 'text/markdown';

	/** `text/calendar` — iCalendar data (RFC 5545), e.g. `.ics` files. */
	public const CALENDAR = 'text/calendar';

	// ---- Application ----

	/** `application/json` — JSON document. */
	public const JSON = 'application/json';

	/** `application/xml` — XML document (preferred over {@see XML_TEXT} for APIs). */
	public const XML = 'application/xml';

	/** `application/xhtml+xml` — XHTML document. */
	public const XHTML = 'application/xhtml+xml';

	/** `application/x-www-form-urlencoded` — HTML form submission body. */
	public const FORM = 'application/x-www-form-urlencoded';

	/** `application/octet-stream` — Arbitrary binary data / file download. */
	public const OCTET_STREAM = 'application/octet-stream';

	/** `application/pdf` — PDF document. */
	public const PDF = 'application/pdf';

	/** `application/zip` — ZIP archive. */
	public const ZIP = 'application/zip';

	/** `application/gzip` — Gzip-compressed data (`.gz`, `.tar.gz`, `.tgz`). */
	public const GZIP = 'application/gzip';

	/** `application/x-tar` — Uncompressed tar archive (`.tar`). */
	public const TAR = 'application/x-tar';

	/** `application/x-bzip2` — Bzip2-compressed data (`.bz2`, `.tar.bz2`, `.tbz2`). */
	public const BZIP2 = 'application/x-bzip2';

	/** `application/x-xz` — XZ/LZMA-compressed data (`.xz`, `.tar.xz`, `.txz`). */
	public const XZ = 'application/x-xz';

	/** `application/ld+json` — JSON-LD structured data. */
	public const JSON_LD = 'application/ld+json';

	/** `application/wasm` — WebAssembly module. */
	public const WASM = 'application/wasm';

	/** `application/rtf` — Rich Text Format document. */
	public const RTF = 'application/rtf';

	// ---- Multipart ----

	/** `multipart/form-data` — Multipart form upload (HTML forms with file input). */
	public const MULTIPART = 'multipart/form-data';

	// ---- Syndication / feeds ----

	/**
	 * `application/rss+xml` — RSS 2.0 syndication feed.
	 * @see \Prado\Web\Services\IFeedContentProvider
	 */
	public const RSS = 'application/rss+xml';

	/**
	 * `application/atom+xml` — Atom syndication feed (RFC 4287).
	 * @see \Prado\Web\Services\IFeedContentProvider
	 */
	public const ATOM = 'application/atom+xml';

	/**
	 * `application/rdf+xml` — RDF document; used for RSS 1.0 feeds.
	 * @see \Prado\Web\Services\IFeedContentProvider
	 */
	public const RDF = 'application/rdf+xml';

	// ---- Office documents ----

	/** `application/vnd.openxmlformats-officedocument.wordprocessingml.document` — Word (.docx). */
	public const DOCX = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';

	/** `application/vnd.openxmlformats-officedocument.spreadsheetml.sheet` — Excel (.xlsx). */
	public const XLSX = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

	/** `application/vnd.openxmlformats-officedocument.presentationml.presentation` — PowerPoint (.pptx). */
	public const PPTX = 'application/vnd.openxmlformats-officedocument.presentationml.presentation';

	/** `application/msword` — Legacy Word (.doc). */
	public const DOC = 'application/msword';

	/** `application/vnd.ms-excel` — Legacy Excel (.xls). */
	public const XLS = 'application/vnd.ms-excel';

	/** `application/vnd.ms-powerpoint` — Legacy PowerPoint (.ppt). */
	public const PPT = 'application/vnd.ms-powerpoint';

	// ---- CSP / Reporting API ----

	/**
	 * `application/csp-report` — Legacy CSP violation report format.
	 * Payload: `{"csp-report": { "blocked-uri": "…", … }}`
	 */
	public const CSP_REPORT = 'application/csp-report';

	/**
	 * `application/reports+json` — Modern Reporting API format (W3C).
	 * Payload: `[{"type": "csp-violation", "body": { … }}]`
	 */
	public const REPORTS_JSON = 'application/reports+json';

	// ---- Image ----

	/** `image/png` — PNG image. */
	public const PNG = 'image/png';

	/** `image/jpeg` — JPEG image. */
	public const JPEG = 'image/jpeg';

	/** `image/gif` — GIF image. */
	public const GIF = 'image/gif';

	/** `image/webp` — WebP image. */
	public const WEBP = 'image/webp';

	/** `image/svg+xml` — SVG vector image. */
	public const SVG = 'image/svg+xml';

	/** `image/x-icon` — Favicon / icon file. */
	public const ICON = 'image/x-icon';

	/** `image/avif` — AVIF image (AV1 Image File Format). */
	public const AVIF = 'image/avif';

	/** `image/bmp` — BMP bitmap image. */
	public const BMP = 'image/bmp';

	/** `image/tiff` — TIFF image. */
	public const TIFF = 'image/tiff';

	// ---- Audio ----

	/** `audio/mpeg` — MP3 and other MPEG audio. */
	public const AUDIO_MPEG = 'audio/mpeg';

	/** `audio/ogg` — Ogg Vorbis audio. */
	public const AUDIO_OGG = 'audio/ogg';

	/** `audio/wav` — WAV audio. */
	public const AUDIO_WAV = 'audio/wav';

	/** `audio/webm` — WebM audio. */
	public const AUDIO_WEBM = 'audio/webm';

	/** `audio/aac` — AAC audio. */
	public const AUDIO_AAC = 'audio/aac';

	// ---- Video ----

	/** `video/mp4` — MP4 video. */
	public const VIDEO_MP4 = 'video/mp4';

	/** `video/webm` — WebM video. */
	public const VIDEO_WEBM = 'video/webm';

	/** `video/ogg` — Ogg video. */
	public const VIDEO_OGG = 'video/ogg';

	// ---- Font ----

	/** `font/woff` — Web Open Font Format. */
	public const WOFF = 'font/woff';

	/** `font/woff2` — Web Open Font Format 2. */
	public const WOFF2 = 'font/woff2';

	/** `font/ttf` — TrueType font. */
	public const TTF = 'font/ttf';

	/** `font/otf` — OpenType font. */
	public const OTF = 'font/otf';

	// ---- Defaults ----

	/**
	 * Default top-level type used when the constructor receives no argument.
	 * Subclasses may override this constant to change the default media type,
	 * but must also supply a matching {@see DEFAULT_SUBTYPE}.
	 */
	public const DEFAULT_TYPE = 'text';

	/**
	 * Default subtype used when the constructor receives no argument.
	 * Subclasses may override this constant to change the default media type,
	 * but must also supply a matching {@see DEFAULT_TYPE}.
	 */
	public const DEFAULT_SUBTYPE = 'html';

	// =========================================================================
	// Backing fields
	// =========================================================================

	/**
	 * @var string The top-level type, always lowercase (e.g. `text`).
	 */
	private string $_type = '';

	/**
	 * @var string The subtype, always lowercase (e.g. `html`).
	 */
	private string $_subtype = '';

	// =========================================================================
	// Constructor
	// =========================================================================

	/**
	 * Constructs a TMediaType by parsing a media type string.
	 *
	 * When called with no argument, {@see DEFAULT_TYPE} and {@see DEFAULT_SUBTYPE}
	 * are applied via late static binding, allowing subclasses to change the default
	 * by overriding those constants without overriding the constructor.
	 *
	 * ```php
	 * new TMediaType('text/html; charset=UTF-8')
	 * new TMediaType(TMediaType::JSON)
	 * new TMediaType()  // → type/subtype from DEFAULT_TYPE / DEFAULT_SUBTYPE
	 * ```
	 *
	 * @param ?string $mediaType full media type string to parse, with or without
	 *   parameters; pass `null` (or omit) to use {@see DEFAULT_TYPE}/{@see DEFAULT_SUBTYPE}.
	 */
	public function __construct(?string $mediaType = null)
	{
		$this->_type = static::DEFAULT_TYPE;
		$this->_subtype = static::DEFAULT_SUBTYPE;
		if ($mediaType === null) {
			return;
		}
		$mediaType = trim($mediaType);
		$semicolon = strpos($mediaType, ';');
		if ($semicolon !== false) {
			$this->setMimeType(substr($mediaType, 0, $semicolon));
			$this->setParameters(substr($mediaType, $semicolon + 1));
		} else {
			$this->setMimeType($mediaType);
		}
	}

	// =========================================================================
	// Type / Subtype / MimeType
	// =========================================================================

	/**
	 * Returns the top-level type, always lowercase (e.g. `text`).
	 * @return string the top-level type.
	 */
	public function getType(): string
	{
		return $this->_type;
	}

	/**
	 * Sets the top-level type; normalized to lowercase and trimmed.
	 * @param string $type e.g. `text`, `application`, `image`
	 */
	public function setType(string $type): void
	{
		$this->_type = strtolower(trim($type));
	}

	/**
	 * Returns the subtype, always lowercase (e.g. `html`, `json`).
	 * @return string the subtype.
	 */
	public function getSubtype(): string
	{
		return $this->_subtype;
	}

	/**
	 * Sets the subtype; normalized to lowercase and trimmed.
	 * @param string $subtype e.g. `html`, `json`, `plain`
	 */
	public function setSubtype(string $subtype): void
	{
		$this->_subtype = strtolower(trim($subtype));
	}

	/**
	 * Returns the `type/subtype` portion without parameters,
	 * e.g. `text/html` or `application/json`. When the subtype is empty,
	 * only the type is returned.
	 * @return string the MIME type without parameters.
	 */
	public function getMimeType(): string
	{
		$sub = $this->getSubtype();
		return $sub !== '' ? $this->getType() . '/' . $sub : $this->getType();
	}

	/**
	 * Sets {@see getType() Type} and {@see getSubtype() Subtype} by parsing a
	 * `type/subtype` string. Existing parameters are left unchanged.
	 *
	 * When no `/` is present the entire string is treated as the type and the
	 * subtype is cleared to `''`. Each component is normalized to lowercase.
	 *
	 * ```php
	 * $mt->setMimeType('application/json');
	 * $mt->getType();    // 'application'
	 * $mt->getSubtype(); // 'json'
	 * ```
	 *
	 * @param string $mimeType e.g. `text/html` or `application/json`
	 */
	public function setMimeType(string $mimeType): void
	{
		if (str_contains($mimeType, '/')) {
			[$type, $subtype] = explode('/', $mimeType, 2);
			$this->setType($type);
			$this->setSubtype($subtype);
		} else {
			$this->setType($mimeType);
			$this->setSubtype('');
		}
	}

	// =========================================================================
	// Charset convenience
	// =========================================================================

	/**
	 * Returns the `charset` parameter value, or `null` when absent.
	 * @return ?string e.g. `'UTF-8'`, or `null`.
	 */
	public function getCharset(): ?string
	{
		return $this->getParameter('charset');
	}

	/**
	 * Sets the `charset` parameter. Pass `null` to remove it.
	 * @param ?string $charset e.g. `'UTF-8'`, or `null` to remove.
	 */
	public function setCharset(?string $charset): void
	{
		if ($charset === null) {
			$this->removeParameter('charset');
		} else {
			$this->setParameter('charset', $charset);
		}
	}

	// =========================================================================
	// Boundary convenience
	// =========================================================================

	/**
	 * Returns the `boundary` parameter value, or `null` when absent.
	 * The `boundary` parameter is required by `multipart/*` types
	 * (e.g. `multipart/form-data; boundary=----WebKitFormBoundaryXYZ`).
	 * @return ?string e.g. `'----WebKitFormBoundaryXYZ'`, or `null`.
	 */
	public function getBoundary(): ?string
	{
		return $this->getParameter('boundary');
	}

	/**
	 * Sets the `boundary` parameter. Pass `null` to remove it.
	 * @param ?string $boundary e.g. `'----WebKitFormBoundaryXYZ'`, or `null` to remove.
	 */
	public function setBoundary(?string $boundary): void
	{
		if ($boundary === null) {
			$this->removeParameter('boundary');
		} else {
			$this->setParameter('boundary', $boundary);
		}
	}

	// =========================================================================
	// Rendering
	// =========================================================================

	/**
	 * Renders the full media type string, e.g. `text/html; charset=UTF-8`.
	 * Parameters are appended in insertion order, each separated by `'; '`.
	 * Parameter values are emitted as stored (not re-quoted); values that are
	 * valid RFC 9110 tokens render without quotes, which is correct for all
	 * standard parameters (`charset`, `boundary`, etc.).
	 * @return string the rendered media type.
	 */
	public function __toString(): string
	{
		$params = $this->getParameters();
		if (empty($params)) {
			return $this->getMimeType();
		}
		$parts = [$this->getMimeType()];
		foreach ($params as $name => $value) {
			$parts[] = $name . '=' . $value;
		}
		return implode('; ', $parts);
	}
}

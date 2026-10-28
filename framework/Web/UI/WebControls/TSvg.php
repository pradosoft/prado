<?php

/**
 * TSvg class file
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\Prado;
use Prado\TApplicationMode;
use Prado\TPropertyValue;

/**
 * TSvg class
 * TSvg renders an inline `<svg>` graphic. Its content comes from one source:
 * - the child controls, SVG markup written in the template;
 * - {@see setSvgFile() SvgFile}, a `.svg` file read on the server;
 * - {@see setSvg() Svg}, SVG markup as a string, such as from data binding.
 * ```html
 * <com:TSvg ViewBox="0 0 24 24" Width="24" Height="24" AlternateText="Settings">
 *     <path d="M12 8a4 4 0 1 0 0 8a4 4 0 0 0 0-8z" />
 * </com:TSvg>
 * <com:TSvg SvgFile="Application.icons.logo" AlternateText="Prado" />
 * ```
 * For SvgFile and Svg, the attributes of the file's root `<svg>`, such as
 * `viewBox` and `fill`, render on the element, and the control's properties
 * replace them. The child controls render after that content.
 * {@see setWidth() Width} and {@see setHeight() Height} are the size in CSS pixels,
 * rendered as attributes; {@see setViewBox() ViewBox} and
 * {@see setPreserveAspectRatio() PreserveAspectRatio} render as written.
 *
 * ## SvgFile and Svg content
 * An inline SVG runs in the page, so the content of SvgFile and Svg is processed
 * by {@see TSvgDocument}:
 * - {@see setSanitize() Sanitize}, on by default, removes script, event
 *   attributes, and external resources, for SVG from users or other sites.
 * - {@see setPrefixIds() PrefixIds}, on by default, prefixes each ID with the
 *   client ID and rewrites the references to it, so two copies on a page keep
 *   their own gradients, clip paths, and filters.
 * - {@see setHoistStyles() HoistStyles}, on by default, moves the `<style>`
 *   elements into the page head through
 *   {@see \Prado\Web\UI\TClientScriptManager::registerStyleSheet()}, where equal
 *   CSS renders once and carries the CSP nonce.
 * Template markup is the developer's code, and none of these apply to it.
 *
 * ## Accessibility
 * | Setting | Rendering |
 * |---|---|
 * | {@see setAlternateText() AlternateText} | `role="img"`, a `<title>` as the first child, and `aria-labelledby` to it; {@see setDescription() Description} adds a `<desc>` and `aria-describedby` |
 * | No AlternateText, and the SvgFile or Svg has a root `<title>` | That title names the graphic: `role="img"` and `aria-labelledby` to it |
 * | {@see setDecorative() Decorative} | `aria-hidden="true"`; the graphic is hidden from assistive technology |
 * | None of these | Rendered as written; in Debug mode a warning names the control |
 * A {@see TWebControl::setRole() Role} replaces `role="img"`; an SVG with links or
 * controls sets `Role="group"` and an `Aria.label` instead of `img`, whose content
 * is hidden from assistive technology. Shapes that should stay visible in forced
 * colors mode use `currentColor`.
 * {@see setReducedMotion() ReducedMotion} stops CSS animations and transitions in
 * the graphic for a user who prefers reduced motion. It does not stop SMIL
 * animations (`<animate>`). Motion that lasts more than five seconds needs a way
 * to pause it (WCAG 2.2.2).
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TSvg extends TWebControl
{
	/**
	 * @var ?TSvgDocument the processed SvgFile or Svg content, or null for template markup.
	 */
	private ?TSvgDocument $_document = null;

	/**
	 * @var bool whether the content has been processed for this request.
	 */
	private bool $_processed = false;

	/**
	 * @var ?string the ID of the root `<title>` of the SvgFile or Svg, after processing.
	 */
	private ?string $_documentTitleId = null;

	/**
	 * @return string the tag name, `svg`.
	 */
	protected function getTagName()
	{
		return 'svg';
	}

	/**
	 * @return string the namespace or file path of the `.svg` file. Defaults to ''.
	 */
	public function getSvgFile()
	{
		return $this->getViewState('SvgFile', '');
	}

	/**
	 * @param string $value the namespace, such as `Application.icons.logo`, or file path of
	 *   the `.svg` file.
	 */
	public function setSvgFile($value)
	{
		$this->setViewState('SvgFile', TPropertyValue::ensureString($value), '');
		$this->_processed = false;
	}

	/**
	 * @return string the SVG markup. Defaults to ''.
	 */
	public function getSvg()
	{
		return $this->getViewState('Svg', '');
	}

	/**
	 * @param string $value the SVG markup, with a root `<svg>` element.
	 */
	public function setSvg($value)
	{
		$this->setViewState('Svg', TPropertyValue::ensureString($value), '');
		$this->_processed = false;
	}

	/**
	 * @return string the `viewBox`, such as `0 0 24 24`. Defaults to ''.
	 */
	public function getViewBox()
	{
		return $this->getViewState('ViewBox', '');
	}

	/**
	 * @param string $value the `viewBox`, such as `0 0 24 24`.
	 */
	public function setViewBox($value)
	{
		$this->setViewState('ViewBox', trim(TPropertyValue::ensureString($value)), '');
	}

	/**
	 * @return string the `preserveAspectRatio`, such as `xMidYMid meet`. Defaults to ''.
	 */
	public function getPreserveAspectRatio()
	{
		return $this->getViewState('PreserveAspectRatio', '');
	}

	/**
	 * @param string $value the `preserveAspectRatio`, such as `xMidYMid meet`.
	 */
	public function setPreserveAspectRatio($value)
	{
		$this->setViewState('PreserveAspectRatio', trim(TPropertyValue::ensureString($value)), '');
	}

	/**
	 * @return int|string the width in CSS pixels, or -1 when not set. Defaults to -1.
	 */
	public function getWidth()
	{
		return $this->getViewState('Width', -1);
	}

	/**
	 * @param int|string $value the width in CSS pixels; a negative value unsets it.
	 */
	public function setWidth($value)
	{
		if (($value = TPropertyValue::ensureInteger($value)) < 0) {
			$value = -1;
		}
		$this->setViewState('Width', $value, -1);
	}

	/**
	 * @return int|string the height in CSS pixels, or -1 when not set. Defaults to -1.
	 */
	public function getHeight()
	{
		return $this->getViewState('Height', -1);
	}

	/**
	 * @param int|string $value the height in CSS pixels; a negative value unsets it.
	 */
	public function setHeight($value)
	{
		if (($value = TPropertyValue::ensureInteger($value)) < 0) {
			$value = -1;
		}
		$this->setViewState('Height', $value, -1);
	}

	/**
	 * @return string the text alternative of the graphic. Defaults to ''.
	 */
	public function getAlternateText()
	{
		return $this->getViewState('AlternateText', '');
	}

	/**
	 * @param string $value the text alternative of the graphic, rendered in a `<title>`.
	 */
	public function setAlternateText($value)
	{
		$this->setViewState('AlternateText', TPropertyValue::ensureString($value), '');
	}

	/**
	 * @return string the longer description of the graphic. Defaults to ''.
	 */
	public function getDescription()
	{
		return $this->getViewState('Description', '');
	}

	/**
	 * @param string $value the longer description of the graphic, rendered in a `<desc>`.
	 */
	public function setDescription($value)
	{
		$this->setViewState('Description', TPropertyValue::ensureString($value), '');
	}

	/**
	 * @return bool whether the graphic is decorative and hidden from assistive technology.
	 *   Defaults to false.
	 */
	public function getDecorative()
	{
		return $this->getViewState('Decorative', false);
	}

	/**
	 * @param bool $value whether the graphic is decorative and hidden from assistive technology.
	 */
	public function setDecorative($value)
	{
		$this->setViewState('Decorative', TPropertyValue::ensureBoolean($value), false);
	}

	/**
	 * @return bool whether SvgFile and Svg content is sanitized. Defaults to true.
	 */
	public function getSanitize()
	{
		return $this->getViewState('Sanitize', true);
	}

	/**
	 * @param bool $value whether SvgFile and Svg content is sanitized.
	 */
	public function setSanitize($value)
	{
		$this->setViewState('Sanitize', TPropertyValue::ensureBoolean($value), true);
		$this->_processed = false;
	}

	/**
	 * @return bool whether the IDs of SvgFile and Svg content are prefixed with the client
	 *   ID. Defaults to true.
	 */
	public function getPrefixIds()
	{
		return $this->getViewState('PrefixIds', true);
	}

	/**
	 * @param bool $value whether the IDs of SvgFile and Svg content are prefixed with the
	 *   client ID.
	 */
	public function setPrefixIds($value)
	{
		$this->setViewState('PrefixIds', TPropertyValue::ensureBoolean($value), true);
		$this->_processed = false;
	}

	/**
	 * @return bool whether the `<style>` elements of SvgFile and Svg content move into the
	 *   page head. Defaults to true.
	 */
	public function getHoistStyles()
	{
		return $this->getViewState('HoistStyles', true);
	}

	/**
	 * @param bool $value whether the `<style>` elements of SvgFile and Svg content move
	 *   into the page head.
	 */
	public function setHoistStyles($value)
	{
		$this->setViewState('HoistStyles', TPropertyValue::ensureBoolean($value), true);
		$this->_processed = false;
	}

	/**
	 * @return bool whether CSS animations and transitions stop for a user who prefers
	 *   reduced motion. Defaults to false.
	 */
	public function getReducedMotion()
	{
		return $this->getViewState('ReducedMotion', false);
	}

	/**
	 * @param bool $value whether CSS animations and transitions stop for a user who
	 *   prefers reduced motion.
	 */
	public function setReducedMotion($value)
	{
		$this->setViewState('ReducedMotion', TPropertyValue::ensureBoolean($value), false);
	}

	/**
	 * @return string the client ID of the `<title>` of AlternateText.
	 */
	public function getTitleClientID()
	{
		return $this->getClientID() . '_title';
	}

	/**
	 * @return string the client ID of the `<desc>` of Description.
	 */
	public function getDescriptionClientID()
	{
		return $this->getClientID() . '_desc';
	}

	/**
	 * Processes the SvgFile or Svg content, registering its styles, and registers the
	 * reduced motion CSS. In Debug mode, logs a warning for a graphic with no text
	 * alternative that is not decorative.
	 * @param mixed $param event parameter
	 */
	public function onPreRender($param)
	{
		parent::onPreRender($param);
		$this->_processed = false;
		$this->processDocument();
		if ($this->getReducedMotion() && ($page = $this->getPage()) !== null) {
			$this->setEnsureId(true);
			$id = '#' . $this->getClientID();
			$page->getClientScript()->registerStyleSheet(
				'TSvg:motion:' . $this->getClientID(),
				"@media (prefers-reduced-motion: reduce) { {$id}, {$id} * { animation: none !important; transition: none !important; } }"
			);
		}
		if ($this->getNeedsTextAlternative() && ($app = $this->getApplication()) !== null && $app->getMode() === TApplicationMode::Debug) {
			Prado::warning("TSvg '" . $this->getUniqueID() . "' has no text alternative. Set AlternateText, or Decorative for a decorative graphic.", static::class);
		}
	}

	/**
	 * @return bool whether the graphic has neither a text alternative nor a Role and is not
	 *   decorative.
	 */
	protected function getNeedsTextAlternative(): bool
	{
		return !$this->getDecorative() && $this->getAlternateText() === '' && $this->getRole() === null
			&& $this->_documentTitleId === null;
	}

	/**
	 * Parses the SvgFile or Svg content once per request: sanitizes it, prefixes its IDs,
	 * finds its root `<title>`, and registers its styles with the page.
	 * @return ?TSvgDocument the processed content, or null for template markup.
	 */
	protected function processDocument(): ?TSvgDocument
	{
		if ($this->_processed) {
			return $this->_document;
		}
		$this->_processed = true;
		$this->_documentTitleId = null;
		if (($file = $this->getSvgFile()) !== '') {
			$this->_document = TSvgDocument::fromFile($this->resolveSvgFile($file));
		} elseif (($svg = $this->getSvg()) !== '') {
			$this->_document = new TSvgDocument($svg);
		} else {
			return $this->_document = null;
		}
		$document = $this->_document;
		if ($this->getSanitize()) {
			$document->sanitize();
		}
		if ($this->getPrefixIds()) {
			$document->prefixIds($this->getClientID());
		}
		if ($this->getAlternateText() !== '') {
			$document->removeTitles();
		} else {
			$this->_documentTitleId = $document->ensureTitleId($this->getTitleClientID());
		}
		if ($this->getHoistStyles() && ($page = $this->getPage()) !== null) {
			$cs = $page->getClientScript();
			foreach ($document->extractStyles() as $css) {
				$cs->registerStyleSheet('TSvg:' . md5($css), $css);
			}
		}
		return $document;
	}

	/**
	 * @param string $file a file path or namespace.
	 * @return string the path of the `.svg` file.
	 */
	protected function resolveSvgFile(string $file): string
	{
		if (is_file($file)) {
			return $file;
		}
		return Prado::getPathOfNamespace($file, '.svg') ?? $file;
	}

	/**
	 * Adds the root attributes of the SvgFile or Svg, the size and view box, and the
	 * accessibility attributes, then the control's attributes, which replace them.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 */
	protected function addAttributesToRender($writer)
	{
		if (($document = $this->processDocument()) !== null) {
			foreach ($document->getRootAttributes() as $name => $value) {
				$writer->addAttribute($name, $value);
			}
		}
		if (($viewBox = $this->getViewBox()) !== '') {
			$writer->addAttribute('viewBox', $viewBox);
		}
		if (($ratio = $this->getPreserveAspectRatio()) !== '') {
			$writer->addAttribute('preserveAspectRatio', $ratio);
		}
		if (($width = $this->getWidth()) !== -1) {
			$writer->addAttribute('width', (string) $width);
		}
		if (($height = $this->getHeight()) !== -1) {
			$writer->addAttribute('height', (string) $height);
		}
		if ($this->getDecorative()) {
			$writer->addAttribute('aria-hidden', 'true');
		} elseif ($this->getAlternateText() !== '' || $this->_documentTitleId !== null) {
			if ($this->getRole() === null) {
				$writer->addAttribute('role', 'img');
			}
			$writer->addAttribute('aria-labelledby', $this->_documentTitleId ?? $this->getTitleClientID());
			if ($this->getAlternateText() !== '' && $this->getDescription() !== '') {
				$writer->addAttribute('aria-describedby', $this->getDescriptionClientID());
			}
		}
		parent::addAttributesToRender($writer);
	}

	/**
	 * Renders the `<title>` and `<desc>` of AlternateText and Description, the SvgFile or
	 * Svg content, and then the child controls.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 */
	public function renderContents($writer)
	{
		if (!$this->getDecorative() && ($text = $this->getAlternateText()) !== '') {
			$writer->write('<title id="' . $this->encode($this->getTitleClientID()) . '">' . $this->encode($text) . '</title>');
			if (($description = $this->getDescription()) !== '') {
				$writer->write('<desc id="' . $this->encode($this->getDescriptionClientID()) . '">' . $this->encode($description) . '</desc>');
			}
		}
		if (($document = $this->processDocument()) !== null) {
			$writer->write($document->getInnerMarkup());
		}
		parent::renderContents($writer);
	}

	/**
	 * @param string $text text for an SVG element or attribute.
	 * @return string the text with `&`, `<`, `>`, and quotes encoded.
	 */
	protected function encode(string $text): string
	{
		return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
	}
}

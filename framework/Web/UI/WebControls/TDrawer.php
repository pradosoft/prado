<?php

/**
 * TDrawer class file
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
 * TDrawer class
 *
 * TDrawer is a {@see TDialog} at a page edge, the off-canvas pattern. A {@see TButton}
 * with {@see TButton::setCommandFor CommandFor} and `show-modal` opens it modally
 * without script. With {@see setDockAt DockAt}, it docks in the page layout on wide
 * screens and opens modally on narrow ones. The client control
 * `Prado.WebUI.TDrawer` adds the dock switch, swipe-to-close on touch, and, where
 * the browser lacks `closedby`, the behavior of ClosedBy Any and None.
 *
 * | Property | Default | Renders |
 * |---|---|---|
 * | {@see setPlacement Placement} | Start | `data-placement`: the page edge ({@see TDrawerPlacement}) |
 * | {@see setDockAt DockAt} | '' | the width at or above which a Start or End drawer docks; '' never docks |
 * | {@see setSwipe Swipe} | true | swipe-to-close on touch, except with ClosedBy None |
 * | {@see setClosedBy ClosedBy} | Any | `closedby`, in modal mode for a docking drawer |
 * | {@see setShowCloseButton ShowCloseButton} | true | the close button, the single-pointer alternative to the swipe (WCAG 2.5.1) |
 *
 * A drawer with DockAt renders by the width and the state of script:
 *
 * | Width | Script | Rendering |
 * |---|---|---|
 * | at or above DockAt | any | an open non-modal dialog in the page flow; its invokers hide |
 * | below DockAt | on | closed; an invoker opens it modally |
 * | below DockAt | off | an open dialog inline in the page flow; its invokers hide |
 *
 * The server renders `open`, so a docked drawer shows on first paint and takes no
 * focus. A script directly after the dialog closes it on a narrow screen before the
 * page scripts load, because an open non-modal dialog ignores `show-modal`.
 * {@see setOpen Open} opens it modally on a narrow screen. The docked state has the
 * `dialog` role; a `<nav>` in the body gives navigation its landmark.
 * ```xml
 * <com:TButton Text="Menu" CommandFor="Nav" Command="show-modal" />
 * <com:TDrawer ID="Nav" HeaderText="Menu" DockAt="64rem">
 *     <nav aria-label="Main">...</nav>
 * </com:TDrawer>
 * ```
 *
 * The `prado-drawer` class is always rendered before {@see setCssClass CssClass}.
 * {@see setCssUrl CssUrl} `default` registers the {@see TDialog} and drawer stylesheets.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TDrawer extends TDialog
{
	/**
	 * The class rendered on every drawer and styled by the bundled stylesheet.
	 */
	public const CSS_CLASS = 'prado-drawer';

	/**
	 * The client-side class of the control.
	 */
	public const CLIENT_CLASS = 'Prado.WebUI.TDrawer';

	/**
	 * @return TDrawerPlacement the page edge of the drawer. Defaults to TDrawerPlacement::Start.
	 */
	public function getPlacement()
	{
		return $this->getViewState('Placement', TDrawerPlacement::Start);
	}

	/**
	 * Sets the page edge of the drawer.
	 * @param TDrawerPlacement $value the page edge
	 */
	public function setPlacement($value)
	{
		$this->setViewState('Placement', TPropertyValue::ensureEnum($value, TDrawerPlacement::class), TDrawerPlacement::Start);
	}

	/**
	 * @return string the width at or above which the drawer docks. Defaults to '',
	 *   which never docks.
	 */
	public function getDockAt()
	{
		return $this->getViewState('DockAt', '');
	}

	/**
	 * Sets the width at or above which a Start or End drawer docks in the page layout.
	 * @param string $value a positive width in `px`, `rem`, or `em`, such as `64rem`;
	 *   '' never docks
	 * @throws TInvalidDataValueException if the value is not such a width
	 */
	public function setDockAt($value)
	{
		$value = trim(TPropertyValue::ensureString($value));
		if ($value !== '' && !preg_match('/^(?=.*[1-9])\d+(\.\d+)?(px|rem|em)$/D', $value)) {
			throw new TInvalidDataValueException('drawer_dockat_invalid', $value);
		}
		$this->setViewState('DockAt', $value, '');
	}

	/**
	 * @return string the width at or above which the drawer docks: DockAt for a Start
	 *   or End drawer, otherwise ''
	 */
	public function getDockWidth()
	{
		$placement = $this->getPlacement();
		return $placement === TDrawerPlacement::Start || $placement === TDrawerPlacement::End ? $this->getDockAt() : '';
	}

	/**
	 * @return bool whether a touch swipe toward the page edge closes the drawer.
	 *   Defaults to true.
	 */
	public function getSwipe()
	{
		return $this->getViewState('Swipe', true);
	}

	/**
	 * Sets whether a touch swipe toward the page edge closes the drawer. ClosedBy
	 * None turns the swipe off, and a docked drawer does not swipe.
	 * @param bool $value whether a swipe closes the drawer
	 */
	public function setSwipe($value)
	{
		$this->setViewState('Swipe', TPropertyValue::ensureBoolean($value), true);
	}

	/**
	 * @return TDialogClosedBy the user actions that close the drawer. Defaults to
	 *   TDialogClosedBy::Any.
	 */
	public function getClosedBy()
	{
		return $this->getViewState('ClosedBy', TDialogClosedBy::Any);
	}

	/**
	 * Sets the user actions that close the drawer. A docked drawer renders no
	 * `closedby`; a click on the page and Esc leave it open.
	 * @param TDialogClosedBy $value the user actions that close the drawer
	 */
	public function setClosedBy($value)
	{
		$this->setViewState('ClosedBy', TPropertyValue::ensureEnum($value, TDialogClosedBy::class), TDialogClosedBy::Any);
	}

	/**
	 * @return bool whether the drawer renders a close button. Defaults to true.
	 */
	public function getShowCloseButton()
	{
		return $this->getViewState('ShowCloseButton', true);
	}

	/**
	 * Sets whether the drawer renders a close button.
	 * @param bool $value whether the drawer renders a close button
	 */
	public function setShowCloseButton($value)
	{
		$this->setViewState('ShowCloseButton', TPropertyValue::ensureBoolean($value), true);
	}

	/**
	 * Registers the stylesheets, the dock rules of DockAt, and the client control.
	 * @param mixed $param event parameter
	 */
	public function onPreRender($param)
	{
		parent::onPreRender($param);
		if (!$this->getHasHeader()) {
			$this->registerStyleSheet();
		}
		$cs = $this->getPage()->getClientScript();
		if ($this->getDockWidth() !== '') {
			$cs->registerStyleSheet('prado:drawer:' . $this->getClientID(), $this->getDockCss());
		}
		$cs->registerPradoScript('drawer');
		$cs->registerPostBackControl(static::CLIENT_CLASS, $this->getClientOptions());
	}

	/**
	 * Registers the stylesheet of {@see getCssUrl CssUrl}. 'default' publishes the
	 * bundled dialog and drawer stylesheets.
	 */
	protected function registerStyleSheet()
	{
		parent::registerStyleSheet();
		if ($this->getCssUrl() === 'default') {
			$url = $this->publishAsset('assets' . DIRECTORY_SEPARATOR . 'drawer.css', self::class);
			$this->getPage()->getClientScript()->registerStyleSheetFile($url, $url);
		}
	}

	/**
	 * Returns the rules of a docking drawer, scoped to it by DockAt. The invokers,
	 * which include the close button, hide when the drawer does not open modally.
	 * @return string the CSS
	 */
	protected function getDockCss()
	{
		$id = '#' . $this->getClientID();
		$invokers = '[commandfor="' . $this->getClientID() . '"]';
		$wide = '(min-width: ' . $this->getDockWidth() . ')';
		$flow = 'position: static; height: auto; transform: none; transition: none; box-shadow: none;';
		return "@media $wide {\n\t$id:not(:modal) { $flow }\n\t$invokers { display: none; }\n}\n"
			. "@media (scripting: enabled) and (not $wide) {\n\t$id:not(:modal) { display: none; }\n}\n"
			. "@media (scripting: none) and (not $wide) {\n\t$id { $flow width: auto; }\n\t$invokers { display: none; }\n}";
	}

	/**
	 * @return string the `closedby` value in modal mode, or '' for Auto
	 */
	protected function getClosedByAttribute()
	{
		$closedBy = $this->getClosedBy();
		return $closedBy === TDialogClosedBy::Auto ? '' : strtolower($closedBy);
	}

	/**
	 * @return array the options of the client control: `ID`, `Placement`, `DockAt`,
	 *   `ClosedBy`, and `Swipe`
	 */
	protected function getClientOptions()
	{
		return [
			'ID' => $this->getClientID(),
			'Placement' => strtolower($this->getPlacement()),
			'DockAt' => $this->getDockWidth(),
			'ClosedBy' => $this->getClosedByAttribute(),
			'Swipe' => $this->getSwipe() && $this->getClosedBy() !== TDialogClosedBy::None,
		];
	}

	/**
	 * Registers nothing for a docking drawer, whose script after the dialog opens
	 * an Open drawer modally on a narrow screen.
	 */
	protected function registerShowModalScript()
	{
		if ($this->getDockWidth() === '') {
			parent::registerShowModalScript();
		}
	}

	/**
	 * Adds the client ID, the `prado-drawer` class before {@see getCssClass CssClass},
	 * and `data-placement`. A docking drawer renders `open` and no `closedby`.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 */
	protected function addAttributesToRender($writer)
	{
		$this->setEnsureId(true);
		parent::addAttributesToRender($writer);
		$classes = array_filter(
			explode(' ', $this->getCssClass()),
			fn ($class) => $class !== '' && $class !== self::CSS_CLASS
		);
		$writer->addAttribute('class', implode(' ', [self::CSS_CLASS, ...$classes]));
		$writer->addAttribute('data-placement', strtolower($this->getPlacement()));
		if ($this->getDockWidth() !== '') {
			$writer->removeAttribute('closedby');
			$writer->addAttribute('open', 'open');
		}
	}

	/**
	 * Renders the drawer and, for a docking drawer, the script that closes it on a
	 * narrow screen.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 */
	public function render($writer)
	{
		parent::render($writer);
		if ($this->getDockWidth() !== '') {
			$writer->write(TJavaScript::renderScriptBlocks([$this->getNarrowScript()]));
		}
	}

	/**
	 * Returns the script that closes a docking drawer on a narrow screen, applies
	 * `closedby`, and opens an Open drawer modally.
	 * @return string the JavaScript
	 */
	protected function getNarrowScript()
	{
		$closedBy = $this->getClosedByAttribute();
		return '(function(d){if(d&&!matchMedia(' . TJavaScript::quoteString('(min-width: ' . $this->getDockWidth() . ')') . ').matches){d.close();'
			. ($closedBy !== '' ? 'd.setAttribute("closedby",' . TJavaScript::quoteString($closedBy) . ');' : '')
			. ($this->getOpen() ? 'd.showModal();' : '')
			. '}})(document.getElementById(' . TJavaScript::quoteString($this->getClientID()) . '));';
	}
}

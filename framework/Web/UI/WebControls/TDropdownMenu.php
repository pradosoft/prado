<?php

/**
 * TDropdownMenu class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * TDropdownMenu class
 *
 * TDropdownMenu renders a menu button and an action menu: a {@see TPopover} whose
 * links and buttons are the menu items (the WAI-ARIA menu button pattern). The
 * popover opens without script. The client control `Prado.WebUI.TDropdownMenu`
 * gives it the ARIA `menu` role and the menu keyboard:
 *
 * | Key | In the menu button | In the menu |
 * |---|---|---|
 * | Enter, Space | opens the menu; focus moves to the first item | activates the item |
 * | Down Arrow | opens the menu on the first item | moves to the next item, wrapping |
 * | Up Arrow | opens the menu on the last item | moves to the previous item, wrapping |
 * | Home, End | | moves to the first or last item |
 * | a character | | moves to the next item that starts with it |
 * | Esc | | closes the menu; focus returns to the menu button |
 * | Tab | | closes the menu and moves on |
 *
 * The items are the links and buttons in the body, in order; an enabled
 * {@see TButton}, {@see TLinkButton}, or {@see THyperLink} is one. The client
 * control gives each `role="menuitem"` and a roving `tabindex`, and lists
 * between the menu and its items take `role="none"`. Activating an item closes
 * the menu. Without script, the menu is a popover of links and buttons.
 * ```xml
 * <com:TDropdownMenu ButtonText="Actions">
 *     <com:TButton Text="Duplicate" ButtonTag="Button" OnClick="duplicate" />
 *     <com:TButton Text="Archive" ButtonTag="Button" OnClick="archive" />
 *     <hr />
 *     <com:THyperLink Text="Export" NavigateUrl="export.csv" />
 * </com:TDropdownMenu>
 * ```
 *
 * {@see setButtonText ButtonText} renders the menu button, with
 * `aria-haspopup="menu"`. A {@see TButton} with {@see TButton::setCommandFor CommandFor}
 * and `toggle-popover` is also a menu button; the client control adds its
 * `aria-haspopup`. {@see setPlacement Placement} defaults to Bottom, aligned with
 * the start of the button. The `prado-popover` and `prado-menu` classes are
 * always rendered before {@see setCssClass CssClass}.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TDropdownMenu extends TPopover
{
	/**
	 * The class rendered on every menu, after {@see TPopover::CSS_CLASS}.
	 */
	public const MENU_CSS_CLASS = 'prado-menu';

	/**
	 * The client-side class of the control.
	 */
	public const CLIENT_CLASS = 'Prado.WebUI.TDropdownMenu';

	/**
	 * Registers the stylesheets and the client control.
	 * @param mixed $param event parameter
	 */
	public function onPreRender($param)
	{
		parent::onPreRender($param);
		$this->registerClientScript();
	}

	/**
	 * Registers the stylesheet of {@see getCssUrl CssUrl}. 'default' publishes the
	 * bundled popover and menu stylesheets.
	 */
	protected function registerStyleSheet()
	{
		parent::registerStyleSheet();
		if ($this->getCssUrl() === 'default') {
			$url = $this->publishAsset('assets' . DIRECTORY_SEPARATOR . 'menu.css', self::class);
			$this->getPage()->getClientScript()->registerStyleSheetFile($url, $url);
		}
	}

	/**
	 * Registers the `menu` script package and the client control.
	 */
	protected function registerClientScript()
	{
		$cs = $this->getPage()->getClientScript();
		$cs->registerPradoScript('menu');
		$cs->registerPostBackControl(static::CLIENT_CLASS, $this->getClientOptions());
	}

	/**
	 * @return array the options of the client control: `ID`
	 */
	protected function getClientOptions()
	{
		return ['ID' => $this->getClientID()];
	}

	/**
	 * Renders the menu button, with `aria-haspopup="menu"`.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 */
	protected function renderButton($writer)
	{
		$writer->addAttribute('aria-haspopup', 'menu');
		parent::renderButton($writer);
	}

	/**
	 * Adds the popover attributes and the `prado-popover prado-menu` classes
	 * before {@see getCssClass CssClass} to the renderer.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 */
	protected function addAttributesToRender($writer)
	{
		parent::addAttributesToRender($writer);
		$classes = array_filter(
			explode(' ', $this->getCssClass()),
			fn ($class) => $class !== '' && $class !== self::CSS_CLASS && $class !== self::MENU_CSS_CLASS
		);
		$writer->addAttribute('class', implode(' ', [self::CSS_CLASS, self::MENU_CSS_CLASS, ...$classes]));
	}
}

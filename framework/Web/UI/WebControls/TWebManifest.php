<?php

/**
 * TWebManifest class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\Exceptions\TInvalidDataValueException;
use Prado\TPropertyValue;
use Prado\Web\Services\ServiceWorker\TServiceWorkerDefinition;
use Prado\Web\Services\ServiceWorker\TServiceWorkerModule;

/**
 * TWebManifest class
 *
 * TWebManifest renders the web app manifest link of a page and the head tags
 * that installed web apps read. Each property renders one {@see TLinkTag} or
 * {@see TMetaTag}; an empty property renders nothing.
 * ```php
 * <com:THead>
 *   <com:TWebManifest Href="/app.webmanifest" ThemeColor="#2b5797" ThemeColorDark="#10213a"
 *     AppTitle="Orders" StatusBarStyle="black-translucent"
 *     AppleTouchIcon="/img/apple-touch-icon.png" />
 * </com:THead>
 * ```
 *
 * | Property | Tag |
 * |---|---|
 * | {@see setHref Href} | `<link rel="manifest">` |
 * | {@see setCrossOrigin CrossOrigin} | `crossorigin` of the manifest link; `use-credentials` sends cookies with the manifest request |
 * | {@see setThemeColor ThemeColor} | `<meta name="theme-color">` |
 * | {@see setThemeColorDark ThemeColorDark} | `<meta name="theme-color" media="(prefers-color-scheme: dark)">`; ThemeColor then carries the light media query |
 * | {@see setApplicationName ApplicationName} | `<meta name="application-name">` |
 * | {@see setMobileWebAppCapable MobileWebAppCapable} | `<meta name="mobile-web-app-capable" content="yes">` |
 * | {@see setAppTitle AppTitle} | `<meta name="apple-mobile-web-app-title">` |
 * | {@see setStatusBarStyle StatusBarStyle} | `<meta name="apple-mobile-web-app-status-bar-style">`: `default`, `black`, or `black-translucent` |
 * | {@see setAppleTouchIcon AppleTouchIcon} | `<link rel="apple-touch-icon">`, with {@see setAppleTouchIconSizes AppleTouchIconSizes} |
 *
 * **The service worker's manifest.** With an empty Href, the control links the
 * manifest of the page's service worker of {@see TServiceWorkerModule}
 * (`?sw=manifest`), when that worker has one. {@see setWorker Worker} names the
 * worker; empty selects the page's worker
 * ({@see TServiceWorkerModule::getPageWorkerName()}). An empty ThemeColor is
 * then the manifest's `theme_color`, and an empty AppTitle its `short_name`,
 * else its `name`. TServiceWorkerModule adds a TWebManifest to the head of a
 * page without a manifest link.
 *
 * A manifest link applies once per page, so a page holds one TWebManifest. It
 * renders where it is placed, in {@see THead} or a content placeholder of the
 * head. It renders no visible content.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TWebManifest extends \Prado\Web\UI\TControl
{
	/**
	 * The values of {@see setStatusBarStyle StatusBarStyle}.
	 */
	public const STATUS_BAR_STYLES = ['default', 'black', 'black-translucent'];

	/**
	 * The media query of {@see setThemeColorDark ThemeColorDark}.
	 */
	public const DARK_MEDIA = '(prefers-color-scheme: dark)';

	/**
	 * The media query of {@see setThemeColor ThemeColor} beside ThemeColorDark.
	 */
	public const LIGHT_MEDIA = '(prefers-color-scheme: light)';

	/**
	 * @return string the URL of the manifest. Defaults to empty, which links the
	 *   manifest of the page's service worker.
	 */
	public function getHref()
	{
		return $this->getViewState('Href', '');
	}

	/**
	 * @param string $value the URL of the manifest; empty links the manifest of the
	 *   page's service worker
	 */
	public function setHref($value)
	{
		$this->setViewState('Href', TPropertyValue::ensureString($value), '');
	}

	/**
	 * @return string the CORS mode of the manifest request: '', 'anonymous', or
	 *   'use-credentials'. Defaults to ''.
	 */
	public function getCrossOrigin()
	{
		return $this->getViewState('CrossOrigin', '');
	}

	/**
	 * Sets the CORS mode of the manifest request (`crossorigin`). Browsers fetch a
	 * manifest without cookies unless it is `use-credentials`.
	 * @param string $value '', 'anonymous', or 'use-credentials'
	 * @throws TInvalidDataValueException if the value is not one of those.
	 */
	public function setCrossOrigin($value)
	{
		$value = strtolower(trim(TPropertyValue::ensureString($value)));
		if ($value !== '' && $value !== 'anonymous' && $value !== 'use-credentials') {
			throw new TInvalidDataValueException('media_crossorigin_invalid', $this::class, $value);
		}
		$this->setViewState('CrossOrigin', $value, '');
	}

	/**
	 * @return string the ID of the TServiceWorkerModule, or `Auto` for the first
	 *   one. Defaults to `Auto`.
	 */
	public function getModuleID()
	{
		return $this->getViewState('ModuleID', TServiceWorkerModule::AUTO);
	}

	/**
	 * @param string $value the ID of the TServiceWorkerModule, or `Auto` for the first one
	 */
	public function setModuleID($value)
	{
		$value = trim(TPropertyValue::ensureString($value));
		$this->setViewState('ModuleID', $value === '' ? TServiceWorkerModule::AUTO : $value, TServiceWorkerModule::AUTO);
	}

	/**
	 * @return string the service worker whose manifest an empty Href links.
	 *   Defaults to empty, which selects the page's worker.
	 */
	public function getWorker()
	{
		return $this->getViewState('Worker', '');
	}

	/**
	 * @param string $value the service worker whose manifest an empty Href links;
	 *   empty selects the page's worker
	 */
	public function setWorker($value)
	{
		$this->setViewState('Worker', trim(TPropertyValue::ensureString($value)), '');
	}

	/**
	 * @return string the theme color. Defaults to empty.
	 */
	public function getThemeColor()
	{
		return $this->getViewState('ThemeColor', '');
	}

	/**
	 * @param string $value the theme color of the browser interface, a CSS color
	 */
	public function setThemeColor($value)
	{
		$this->setViewState('ThemeColor', TPropertyValue::ensureString($value), '');
	}

	/**
	 * @return string the theme color in a dark color scheme. Defaults to empty.
	 */
	public function getThemeColorDark()
	{
		return $this->getViewState('ThemeColorDark', '');
	}

	/**
	 * @param string $value the theme color in a dark color scheme, a CSS color
	 */
	public function setThemeColorDark($value)
	{
		$this->setViewState('ThemeColorDark', TPropertyValue::ensureString($value), '');
	}

	/**
	 * @return string the application name. Defaults to empty.
	 */
	public function getApplicationName()
	{
		return $this->getViewState('ApplicationName', '');
	}

	/**
	 * @param string $value the application name (`application-name`)
	 */
	public function setApplicationName($value)
	{
		$this->setViewState('ApplicationName', TPropertyValue::ensureString($value), '');
	}

	/**
	 * @return bool whether the page opens as a standalone app from a home screen
	 *   shortcut. Defaults to false.
	 */
	public function getMobileWebAppCapable()
	{
		return $this->getViewState('MobileWebAppCapable', false);
	}

	/**
	 * @param bool|string $value whether the page opens as a standalone app from a
	 *   home screen shortcut (`mobile-web-app-capable`)
	 */
	public function setMobileWebAppCapable($value)
	{
		$this->setViewState('MobileWebAppCapable', TPropertyValue::ensureBoolean($value), false);
	}

	/**
	 * @return string the app title under the home screen icon on iOS and iPadOS.
	 *   Defaults to empty.
	 */
	public function getAppTitle()
	{
		return $this->getViewState('AppTitle', '');
	}

	/**
	 * @param string $value the app title under the home screen icon on iOS and
	 *   iPadOS (`apple-mobile-web-app-title`)
	 */
	public function setAppTitle($value)
	{
		$this->setViewState('AppTitle', TPropertyValue::ensureString($value), '');
	}

	/**
	 * @return string the status bar style of the app on iOS and iPadOS. Defaults to empty.
	 */
	public function getStatusBarStyle()
	{
		return $this->getViewState('StatusBarStyle', '');
	}

	/**
	 * @param string $value the status bar style of the app on iOS and iPadOS
	 *   (`apple-mobile-web-app-status-bar-style`): `default`, `black`,
	 *   `black-translucent`, or empty
	 * @throws TInvalidDataValueException if the value is not a style.
	 */
	public function setStatusBarStyle($value)
	{
		$value = strtolower(trim(TPropertyValue::ensureString($value)));
		if ($value !== '' && !in_array($value, self::STATUS_BAR_STYLES, true)) {
			throw new TInvalidDataValueException('webmanifest_statusbarstyle_invalid', $value);
		}
		$this->setViewState('StatusBarStyle', $value, '');
	}

	/**
	 * @return string the URL of the home screen icon on iOS and iPadOS. Defaults to empty.
	 */
	public function getAppleTouchIcon()
	{
		return $this->getViewState('AppleTouchIcon', '');
	}

	/**
	 * @param string $value the URL of the home screen icon on iOS and iPadOS
	 *   (`apple-touch-icon`), a 180×180 PNG
	 */
	public function setAppleTouchIcon($value)
	{
		$this->setViewState('AppleTouchIcon', TPropertyValue::ensureString($value), '');
	}

	/**
	 * @return string the sizes of the home screen icon. Defaults to empty.
	 */
	public function getAppleTouchIconSizes()
	{
		return $this->getViewState('AppleTouchIconSizes', '');
	}

	/**
	 * @param string $value the sizes of the home screen icon, such as `180x180`
	 */
	public function setAppleTouchIconSizes($value)
	{
		$this->setViewState('AppleTouchIconSizes', TPropertyValue::ensureString($value), '');
	}

	/**
	 * Returns the service worker whose manifest an empty Href links.
	 * @return ?TServiceWorkerDefinition the worker with a manifest, or null
	 */
	public function getServiceWorkerDefinition(): ?TServiceWorkerDefinition
	{
		$module = TServiceWorker::findModule($this->getApplication(), $this->getModuleID());
		if ($module === null) {
			return null;
		}
		$worker = $module->getWorker($module->getPageWorkerName($this->getPage(), $this->getWorker()));
		return ($worker !== null && $worker->getHasManifest()) ? $worker : null;
	}

	/**
	 * Builds the link and meta tags of the properties, in rendering order.
	 * @return array<TLinkTag|TMetaTag> the tags
	 */
	public function createTags(): array
	{
		$href = $this->getHref();
		$themeColor = $this->getThemeColor();
		$appTitle = $this->getAppTitle();
		if ($href === '' && ($worker = $this->getServiceWorkerDefinition()) !== null) {
			$module = TServiceWorker::findModule($this->getApplication(), $this->getModuleID());
			$href = $module->getManifestUrl($worker, true);
			$manifest = $worker->getManifestData();
			$themeColor = $themeColor !== '' ? $themeColor : (string) ($manifest['theme_color'] ?? '');
			$appTitle = $appTitle !== '' ? $appTitle : (string) ($manifest['short_name'] ?? $manifest['name'] ?? '');
		}
		$tags = [];
		if ($href !== '') {
			$link = new TLinkTag();
			$link->setRel('manifest');
			$link->setHref($href);
			$link->setCrossOrigin($this->getCrossOrigin());
			$tags[] = $link;
		}
		$themeColorDark = $this->getThemeColorDark();
		if ($themeColor !== '') {
			$tags[] = $this->createMetaTag('theme-color', $themeColor, $themeColorDark !== '' ? self::LIGHT_MEDIA : '');
		}
		if ($themeColorDark !== '') {
			$tags[] = $this->createMetaTag('theme-color', $themeColorDark, self::DARK_MEDIA);
		}
		if (($applicationName = $this->getApplicationName()) !== '') {
			$tags[] = $this->createMetaTag('application-name', $applicationName);
		}
		if ($this->getMobileWebAppCapable()) {
			$tags[] = $this->createMetaTag('mobile-web-app-capable', 'yes');
		}
		if ($appTitle !== '') {
			$tags[] = $this->createMetaTag('apple-mobile-web-app-title', $appTitle);
		}
		if (($statusBarStyle = $this->getStatusBarStyle()) !== '') {
			$tags[] = $this->createMetaTag('apple-mobile-web-app-status-bar-style', $statusBarStyle);
		}
		if (($icon = $this->getAppleTouchIcon()) !== '') {
			$link = new TLinkTag();
			$link->setRel('apple-touch-icon');
			$link->setHref($icon);
			$link->setSizes($this->getAppleTouchIconSizes());
			$tags[] = $link;
		}
		return $tags;
	}

	/**
	 * Renders the tags, then the child controls.
	 * @param \Prado\Web\UI\THtmlWriter $writer writer for the rendering purpose
	 */
	public function render($writer)
	{
		foreach ($this->createTags() as $tag) {
			$tag->render($writer);
			$writer->writeLine();
		}
		parent::render($writer);
	}

	/**
	 * Creates a named meta tag.
	 * @param string $name the meta name
	 * @param string $content the content
	 * @param string $media the media query, or empty
	 * @return TMetaTag the tag
	 */
	protected function createMetaTag(string $name, string $content, string $media = ''): TMetaTag
	{
		$metaTag = new TMetaTag();
		$metaTag->setName($name);
		$metaTag->setContent($content);
		$metaTag->setMedia($media);
		return $metaTag;
	}
}

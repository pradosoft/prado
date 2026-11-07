<?php

/**
 * TWebAppManifest class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\Services\ServiceWorker;

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Prado;
use Prado\TPropertyValue;

/**
 * TWebAppManifest class
 *
 * TWebAppManifest holds the members of a web app manifest, the JSON document
 * that names an installable web app, its icons, and how it opens.
 * {@see TServiceWorkerDefinition::getManifest()} holds one per worker, and
 * {@see TServiceWorkerService} serves it at `?sw=manifest`.
 * ```xml
 * <module id="serviceworker" class="Prado\Web\Services\ServiceWorker\TServiceWorkerModule"
 *     Manifest.Name="Orders" Manifest.ShortName="Orders" Manifest.Display="standalone"
 *     Manifest.ThemeColor="#2b5797" Manifest.Icons='[{"src": "/img/icon-192.png", "sizes": "192x192"}]' />
 * ```
 *
 * | Property | Member | Value |
 * |---|---|---|
 * | {@see setId Id} | `id` | The app identity URL |
 * | {@see setName Name} | `name` | |
 * | {@see setShortName ShortName} | `short_name` | The name under an icon |
 * | {@see setDescription Description} | `description` | |
 * | {@see setStartUrl StartUrl} | `start_url` | |
 * | {@see setScope Scope} | `scope` | |
 * | {@see setDisplay Display} | `display` | `fullscreen`, `standalone`, `minimal-ui`, or `browser` |
 * | {@see setDisplayOverride DisplayOverride} | `display_override` | Display modes and `window-controls-overlay` or `tabbed`, in order |
 * | {@see setOrientation Orientation} | `orientation` | `any`, `natural`, `landscape`, `portrait`, or a `-primary`/`-secondary` form |
 * | {@see setThemeColor ThemeColor} | `theme_color` | |
 * | {@see setBackgroundColor BackgroundColor} | `background_color` | |
 * | {@see setLang Lang} | `lang` | A BCP 47 language tag |
 * | {@see setDir Dir} | `dir` | `ltr`, `rtl`, or `auto` |
 * | {@see setCategories Categories} | `categories` | A comma-separated list |
 * | {@see setIcons Icons} | `icons` | A list of `{src, sizes, type, purpose}` |
 * | {@see setScreenshots Screenshots} | `screenshots` | A list of `{src, sizes, type, form_factor, label}` |
 * | {@see setShortcuts Shortcuts} | `shortcuts` | A list of `{name, url, icons}` |
 *
 * A list property takes a PHP array or JSON text. {@see setFile File} names a
 * JSON manifest file as the base; {@see setMembers Members} adds any other
 * members; the properties above take precedence over both. An empty property
 * adds no member.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TWebAppManifest extends \Prado\TComponent
{
	/**
	 * The display modes of the `display` member.
	 */
	public const DISPLAY_MODES = ['fullscreen', 'standalone', 'minimal-ui', 'browser'];

	/**
	 * The modes of the `display_override` member beyond the display modes.
	 */
	public const DISPLAY_OVERRIDE_MODES = ['window-controls-overlay', 'tabbed'];

	/**
	 * The values of the `orientation` member.
	 */
	public const ORIENTATIONS = ['any', 'natural', 'landscape', 'landscape-primary', 'landscape-secondary', 'portrait', 'portrait-primary', 'portrait-secondary'];

	/**
	 * The values of the `dir` member.
	 */
	public const DIRECTIONS = ['ltr', 'rtl', 'auto'];

	/** @var array<string, mixed> the members set by properties */
	private array $_properties = [];

	/** @var array<string, mixed> other members */
	private array $_members = [];

	/** @var string the manifest file */
	private string $_file = '';

	/**
	 * @return string the app identity URL (`id`). Defaults to empty.
	 */
	public function getId(): string
	{
		return $this->getMember('id');
	}

	/**
	 * @param string $value the app identity URL (`id`)
	 */
	public function setId($value): void
	{
		$this->setMember('id', TPropertyValue::ensureString($value));
	}

	/**
	 * @return string the app name (`name`). Defaults to empty.
	 */
	public function getName(): string
	{
		return $this->getMember('name');
	}

	/**
	 * @param string $value the app name (`name`)
	 */
	public function setName($value): void
	{
		$this->setMember('name', TPropertyValue::ensureString($value));
	}

	/**
	 * @return string the short app name (`short_name`). Defaults to empty.
	 */
	public function getShortName(): string
	{
		return $this->getMember('short_name');
	}

	/**
	 * @param string $value the short app name (`short_name`), shown under the app icon
	 */
	public function setShortName($value): void
	{
		$this->setMember('short_name', TPropertyValue::ensureString($value));
	}

	/**
	 * @return string the app description (`description`). Defaults to empty.
	 */
	public function getDescription(): string
	{
		return $this->getMember('description');
	}

	/**
	 * @param string $value the app description (`description`)
	 */
	public function setDescription($value): void
	{
		$this->setMember('description', TPropertyValue::ensureString($value));
	}

	/**
	 * @return string the URL the app opens (`start_url`). Defaults to empty.
	 */
	public function getStartUrl(): string
	{
		return $this->getMember('start_url');
	}

	/**
	 * @param string $value the URL the app opens (`start_url`)
	 */
	public function setStartUrl($value): void
	{
		$this->setMember('start_url', TPropertyValue::ensureString($value));
	}

	/**
	 * @return string the URL scope of the app (`scope`). Defaults to empty.
	 */
	public function getScope(): string
	{
		return $this->getMember('scope');
	}

	/**
	 * @param string $value the URL scope of the app (`scope`)
	 */
	public function setScope($value): void
	{
		$this->setMember('scope', TPropertyValue::ensureString($value));
	}

	/**
	 * @return string the display mode (`display`). Defaults to empty.
	 */
	public function getDisplay(): string
	{
		return $this->getMember('display');
	}

	/**
	 * @param string $value the display mode (`display`): `fullscreen`,
	 *   `standalone`, `minimal-ui`, `browser`, or empty
	 * @throws TInvalidDataValueException if the value is not a display mode.
	 */
	public function setDisplay($value): void
	{
		$this->setMember('display', $this->ensureToken($value, self::DISPLAY_MODES, 'Display'));
	}

	/**
	 * @return string[] the display modes in order of preference
	 *   (`display_override`). Defaults to empty.
	 */
	public function getDisplayOverride(): array
	{
		return $this->getMember('display_override', []);
	}

	/**
	 * @param string|string[] $value the display modes in order of preference
	 *   (`display_override`), as a list or comma-separated text
	 * @throws TInvalidDataValueException if a mode is not valid.
	 */
	public function setDisplayOverride($value): void
	{
		$modes = [];
		foreach ($this->ensureList($value) as $mode) {
			$modes[] = $this->ensureToken($mode, [...self::DISPLAY_MODES, ...self::DISPLAY_OVERRIDE_MODES], 'DisplayOverride');
		}
		$this->setMember('display_override', $modes);
	}

	/**
	 * @return string the default orientation (`orientation`). Defaults to empty.
	 */
	public function getOrientation(): string
	{
		return $this->getMember('orientation');
	}

	/**
	 * @param string $value the default orientation (`orientation`)
	 * @throws TInvalidDataValueException if the value is not an orientation.
	 */
	public function setOrientation($value): void
	{
		$this->setMember('orientation', $this->ensureToken($value, self::ORIENTATIONS, 'Orientation'));
	}

	/**
	 * @return string the theme color (`theme_color`). Defaults to empty.
	 */
	public function getThemeColor(): string
	{
		return $this->getMember('theme_color');
	}

	/**
	 * @param string $value the theme color (`theme_color`), a CSS color
	 */
	public function setThemeColor($value): void
	{
		$this->setMember('theme_color', TPropertyValue::ensureString($value));
	}

	/**
	 * @return string the background color of the splash screen
	 *   (`background_color`). Defaults to empty.
	 */
	public function getBackgroundColor(): string
	{
		return $this->getMember('background_color');
	}

	/**
	 * @param string $value the background color of the splash screen
	 *   (`background_color`), a CSS color
	 */
	public function setBackgroundColor($value): void
	{
		$this->setMember('background_color', TPropertyValue::ensureString($value));
	}

	/**
	 * @return string the language of the text members (`lang`). Defaults to empty.
	 */
	public function getLang(): string
	{
		return $this->getMember('lang');
	}

	/**
	 * @param string $value the language of the text members (`lang`), a BCP 47 tag
	 */
	public function setLang($value): void
	{
		$this->setMember('lang', TPropertyValue::ensureString($value));
	}

	/**
	 * @return string the direction of the text members (`dir`). Defaults to empty.
	 */
	public function getDir(): string
	{
		return $this->getMember('dir');
	}

	/**
	 * @param string $value the direction of the text members (`dir`): `ltr`,
	 *   `rtl`, `auto`, or empty
	 * @throws TInvalidDataValueException if the value is not a direction.
	 */
	public function setDir($value): void
	{
		$this->setMember('dir', $this->ensureToken($value, self::DIRECTIONS, 'Dir'));
	}

	/**
	 * @return string[] the app categories (`categories`). Defaults to empty.
	 */
	public function getCategories(): array
	{
		return $this->getMember('categories', []);
	}

	/**
	 * @param string|string[] $value the app categories (`categories`), as a list
	 *   or comma-separated text
	 */
	public function setCategories($value): void
	{
		$this->setMember('categories', $this->ensureList($value));
	}

	/**
	 * @return array the app icons (`icons`). Defaults to empty.
	 */
	public function getIcons(): array
	{
		return $this->getMember('icons', []);
	}

	/**
	 * @param array|string $value the app icons (`icons`): a list of
	 *   `{src, sizes, type, purpose}`, as an array or JSON text
	 * @throws TInvalidDataValueException if the JSON is not a list.
	 */
	public function setIcons($value): void
	{
		$this->setMember('icons', $this->ensureObjectList($value, 'Icons'));
	}

	/**
	 * @return array the app screenshots (`screenshots`). Defaults to empty.
	 */
	public function getScreenshots(): array
	{
		return $this->getMember('screenshots', []);
	}

	/**
	 * @param array|string $value the app screenshots (`screenshots`): a list of
	 *   `{src, sizes, type, form_factor, label}`, as an array or JSON text
	 * @throws TInvalidDataValueException if the JSON is not a list.
	 */
	public function setScreenshots($value): void
	{
		$this->setMember('screenshots', $this->ensureObjectList($value, 'Screenshots'));
	}

	/**
	 * @return array the app shortcuts (`shortcuts`). Defaults to empty.
	 */
	public function getShortcuts(): array
	{
		return $this->getMember('shortcuts', []);
	}

	/**
	 * @param array|string $value the app shortcuts (`shortcuts`): a list of
	 *   `{name, url, icons}`, as an array or JSON text
	 * @throws TInvalidDataValueException if the JSON is not a list.
	 */
	public function setShortcuts($value): void
	{
		$this->setMember('shortcuts', $this->ensureObjectList($value, 'Shortcuts'));
	}

	/**
	 * @return array other members of the manifest. Defaults to empty.
	 */
	public function getMembers(): array
	{
		return $this->_members;
	}

	/**
	 * @param array|string $value other members of the manifest, as an array or
	 *   JSON object text
	 * @throws TInvalidDataValueException if the JSON is not an object.
	 */
	public function setMembers($value): void
	{
		$members = is_string($value) ? $this->decodeJson($value, 'Members') : (array) $value;
		if ($members !== [] && array_is_list($members)) {
			throw new TInvalidDataValueException('webappmanifest_json_invalid', 'Members', 'an object is required');
		}
		$this->_members = $members;
	}

	/**
	 * @return string the JSON manifest file that is the base of the manifest.
	 *   Defaults to empty.
	 */
	public function getFile(): string
	{
		return $this->_file;
	}

	/**
	 * @param string $value the JSON manifest file: a path, or a namespace whose
	 *   `.json` file is the manifest
	 */
	public function setFile($value): void
	{
		$this->_file = TPropertyValue::ensureString($value);
	}

	/**
	 * @return bool whether the manifest has no file, no members, and no property set
	 */
	public function getIsEmpty(): bool
	{
		return $this->getFile() === '' && $this->getMembers() === [] && $this->_properties === [];
	}

	/**
	 * Returns the manifest members: the file, then {@see getMembers Members},
	 * then the properties.
	 * @throws TConfigurationException if the file cannot be read or is not a JSON object.
	 * @return array the manifest members
	 */
	public function toArray(): array
	{
		return array_merge($this->loadFile(), $this->getMembers(), $this->_properties);
	}

	/**
	 * Reads the manifest file.
	 * @throws TConfigurationException if the file cannot be read or is not a JSON object.
	 * @return array the members of the file, or empty without a file
	 */
	protected function loadFile(): array
	{
		if (($file = $this->getFile()) === '') {
			return [];
		}
		$path = is_file($file) ? $file : (Prado::getPathOfNamespace($file, '.json') ?? '');
		if (!is_file($path) || ($json = @file_get_contents($path)) === false) {
			throw new TConfigurationException('webappmanifest_file_invalid', $file);
		}
		$members = json_decode($json, true);
		if (!is_array($members) || ($members !== [] && array_is_list($members))) {
			throw new TConfigurationException('webappmanifest_file_invalid', $file);
		}
		return $members;
	}

	/**
	 * @param string $member the member name
	 * @param mixed $default the value of an unset member
	 * @return mixed the member set by a property
	 */
	protected function getMember(string $member, mixed $default = ''): mixed
	{
		return $this->_properties[$member] ?? $default;
	}

	/**
	 * Sets a member; an empty value removes it.
	 * @param string $member the member name
	 * @param mixed $value the value
	 */
	protected function setMember(string $member, mixed $value): void
	{
		if ($value === '' || $value === []) {
			unset($this->_properties[$member]);
		} else {
			$this->_properties[$member] = $value;
		}
	}

	/**
	 * @param mixed $value the value
	 * @param string[] $tokens the allowed tokens
	 * @param string $property the property name, for the error message
	 * @throws TInvalidDataValueException if the value is not a token.
	 * @return string the lowercase token, or empty
	 */
	protected function ensureToken($value, array $tokens, string $property): string
	{
		$value = strtolower(trim(TPropertyValue::ensureString($value)));
		if ($value !== '' && !in_array($value, $tokens, true)) {
			throw new TInvalidDataValueException('webappmanifest_value_invalid', $property, $value, implode(', ', $tokens));
		}
		return $value;
	}

	/**
	 * @param mixed $value a list or comma-separated text
	 * @return string[] the non-empty trimmed items
	 */
	protected function ensureList($value): array
	{
		$items = is_array($value) ? $value : explode(',', TPropertyValue::ensureString($value));
		return array_values(array_filter(array_map(fn ($item) => trim(TPropertyValue::ensureString($item)), $items), fn ($item) => $item !== ''));
	}

	/**
	 * @param mixed $value a list as an array or JSON text
	 * @param string $property the property name, for the error message
	 * @throws TInvalidDataValueException if the JSON is not a list.
	 * @return array the list
	 */
	protected function ensureObjectList($value, string $property): array
	{
		$list = is_string($value) ? $this->decodeJson($value, $property) : (array) $value;
		if (!array_is_list($list)) {
			throw new TInvalidDataValueException('webappmanifest_json_invalid', $property, 'a list is required');
		}
		return $list;
	}

	/**
	 * @param string $json the JSON text
	 * @param string $property the property name, for the error message
	 * @throws TInvalidDataValueException if the JSON is not valid or not an array.
	 * @return array the decoded array, or empty for empty text
	 */
	protected function decodeJson(string $json, string $property): array
	{
		if (($json = trim($json)) === '') {
			return [];
		}
		try {
			$value = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
		} catch (\JsonException $e) {
			throw new TInvalidDataValueException('webappmanifest_json_invalid', $property, $e->getMessage());
		}
		if (!is_array($value)) {
			throw new TInvalidDataValueException('webappmanifest_json_invalid', $property, 'an array is required');
		}
		return $value;
	}
}

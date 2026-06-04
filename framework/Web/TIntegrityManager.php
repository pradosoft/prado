<?php

/**
 * TIntegrityManager class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web;

use Prado\Exceptions\TConfigurationException;
use Prado\Prado;
use Prado\TApplication;
use Prado\TModule;
use Prado\TPropertyValue;
use Prado\Util\Traits\TInitializedTrait;
use Prado\Web\Javascripts\TJavaScript;
use Prado\Xml\TXmlDocument;
use Prado\Xml\TXmlElement;

/**
 * TIntegrityManager class
 *
 * TIntegrityManager registers Subresource Integrity (SRI) values for external
 * script URLs so that a strict `Content-Security-Policy` can require an
 * `integrity` attribute on every third-party `<script>` tag. Each configured
 * entry is pushed into the per-request registry on {@see TJavaScript} via
 * {@see TJavaScript::setScriptIntegrity()}; the registry is consumed by
 * {@see TJavaScript::renderScriptFile()} and {@see \Prado\Web\Javascripts\TJavaScriptAsset},
 * which emit the `integrity` and `crossorigin="anonymous"` attributes for matching
 * remote URLs.
 *
 * XML configuration style:
 * ```xml
 * <modules>
 *     <module id="integrity" class="Prado\Web\TIntegrityManager" RequireIntegrity="true">
 *         <integrity url="https://cdn.example.com/jquery-3.7.1.min.js" hash="sha384-AAAA..." />
 *         <integrity url="https://cdn.example.com/lib.js" hash="BBBB..." method="sha512" />
 *     </module>
 * </modules>
 * ```
 *
 * PHP configuration style:
 * ```php
 * [
 *   'integrity' => [
 *      'class' => 'Prado\Web\TIntegrityManager',
 *      'properties' => [
 *         'RequireIntegrity' => 'true',
 *      ],
 *      'integrities' => [
 *         ['url' => 'https://cdn.example.com/jquery-3.7.1.min.js', 'hash' => 'sha384-AAAA...'],
 *         ['url' => 'https://cdn.example.com/lib.js', 'hash' => 'BBBB...', 'method' => 'sha512'],
 *      ],
 *   ],
 * ]
 * ```
 *
 * An entry's `hash` may be a fully-formed `algo-digest` SRI string (e.g.
 * `sha384-AAAA…`) or a bare base64 digest, in which case `method` supplies the
 * algorithm prefix (defaults to `sha384`).
 *
 * Integrity entries may also be loaded from an external file specified by the
 * {@see setIntegrityFile IntegrityFile} property. The property only accepts a file
 * path in namespace format. The file extension matches the application
 * configuration type (`.xml` or `.php`).
 *
 * XML integrity file (e.g. `integrity.xml`):
 * ```xml
 * <integrities>
 *     <integrity url="https://cdn.example.com/jquery-3.7.1.min.js" hash="sha384-AAAA..." />
 *     <integrity url="https://cdn.example.com/lib.js" hash="BBBB..." method="sha512" />
 * </integrities>
 * ```
 *
 * PHP integrity file (e.g. `integrity.php`):
 * ```php
 * <?php
 * return [
 *     'integrities' => [
 *         ['url' => 'https://cdn.example.com/jquery-3.7.1.min.js', 'hash' => 'sha384-AAAA...'],
 *         ['url' => 'https://cdn.example.com/lib.js', 'hash' => 'BBBB...', 'method' => 'sha512'],
 *     ],
 * ];
 * ```
 *
 * When {@see setRequireIntegrity RequireIntegrity} is enabled, a remote
 * `<script src>` with no registered integrity raises a {@see TConfigurationException}
 * at render time. A strict CSP would otherwise block such a tag silently.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 * @todo Stylesheet Integrity
 */
class TIntegrityManager extends TModule
{
	use TInitializedTrait;

	/**
	 * @var array<string, string> registry of normalized URL → full SRI string managed by this module
	 */
	private array $_integrities = [];
	/**
	 * @var ?string integrity information file
	 */
	private ?string $_integrityFile = null;

	/**
	 * Initializes the module.
	 * This method is required by IModule and is invoked by the application.
	 * It loads integrity information from the module configuration and any
	 * external {@see getIntegrityFile IntegrityFile}, registers each entry with
	 * {@see TJavaScript}, and applies the {@see getRequireIntegrity RequireIntegrity}
	 * setting.
	 * @param null|array|\Prado\Xml\TXmlElement $config module configuration
	 */
	public function init($config)
	{
		$this->loadIntegrityData($this->normalizeConfig($config));
		if (($integrityFile = $this->getIntegrityFile()) !== null) {
			if ($this->getApplication()->getConfigurationType() == TApplication::CONFIG_TYPE_PHP) {
				$fileConfig = include $integrityFile;
			} else {
				$fileConfig = new TXmlDocument();
				$fileConfig->loadFromFile($integrityFile);
			}
			$this->loadIntegrityData($this->normalizeConfig($fileConfig));
		}
		parent::init($config);
		$this->markInitialized();
	}

	/**
	 * Normalizes a module configuration into the canonical PHP array form so a
	 * single code path loads it. A {@see \Prado\Xml\TXmlElement} is converted via
	 * {@see \Prado\Xml\TXmlElement::getElementsAttrArrayByTagName()}, mapping each
	 * `<integrity>` element's attributes to an entry under the `integrities` key.
	 * An array is returned unchanged.
	 * @param mixed $config module configuration, either a PHP array or an XML node
	 * @return array the configuration in PHP array form
	 */
	private function normalizeConfig($config): array
	{
		if ($config instanceof TXmlElement) {
			return ['integrities' => $config->getElementsAttrArrayByTagName('integrity')];
		}
		return is_array($config) ? $config : [];
	}

	/**
	 * Loads integrity information from a normalized PHP array and registers each
	 * entry. Use {@see normalizeConfig()} to convert XML configuration first.
	 * @param array $config the array containing the integrity information
	 */
	private function loadIntegrityData(array $config): void
	{
		if (isset($config['integrities']) && is_array($config['integrities'])) {
			foreach ($config['integrities'] as $entry) {
				$method = trim((string) ($entry['method'] ?? '')) ?: 'sha384';
				$this->addIntegrity($entry['url'] ?? '', $entry['hash'] ?? '', $method);
			}
		}
	}

	/**
	 * The hash algorithms permitted by the Subresource Integrity specification.
	 * @var string[]
	 */
	public const SRI_ALGORITHMS = ['sha256', 'sha384', 'sha512'];

	/**
	 * Calculates the Subresource Integrity value for a string of content.
	 *
	 * The result is the fully-formed `algo-digest` SRI string where `digest` is the
	 * base64 encoding of the raw binary hash of `$content`, as required by the
	 * Subresource Integrity specification.
	 *
	 * @param string $content the content to hash, such as a script or stylesheet body
	 * @param string $method the SRI hash algorithm, one of {@see SRI_ALGORITHMS} (default `sha384`)
	 * @throws TConfigurationException if `$method` is not an SRI-supported algorithm
	 * @return string the fully-formed `algo-digest` SRI string (e.g. `sha384-…`)
	 * @since 4.4.0
	 */
	public static function calculateIntegrity(string $content, string $method = 'sha384'): string
	{
		$method = strtolower(trim($method));
		if (!in_array($method, self::SRI_ALGORITHMS, true)) {
			throw new TConfigurationException('integritymanager_algorithm_invalid', $method, implode(', ', self::SRI_ALGORITHMS));
		}
		return $method . '-' . base64_encode(hash($method, $content, true));
	}

	/**
	 * Calculates the Subresource Integrity value for the contents of a file.
	 *
	 * Reads the file at `$path` and delegates to {@see calculateIntegrity()}. The
	 * hash covers the exact bytes on disk, so the file must hold the same content
	 * the browser fetches for the resulting SRI value to validate.
	 *
	 * @param string $path filesystem path to the file to hash
	 * @param string $method the SRI hash algorithm, one of {@see SRI_ALGORITHMS} (default `sha384`)
	 * @throws TConfigurationException if the file does not exist or cannot be read,
	 *   or if `$method` is not an SRI-supported algorithm
	 * @return string the fully-formed `algo-digest` SRI string (e.g. `sha384-…`)
	 * @since 4.4.0
	 */
	public static function calculateIntegrityForFile(string $path, string $method = 'sha384'): string
	{
		if (!is_file($path) || ($content = @file_get_contents($path)) === false) {
			throw new TConfigurationException('integritymanager_file_unreadable', $path);
		}
		return self::calculateIntegrity($content, $method);
	}

	/**
	 * Registers a single integrity entry with this module and with {@see TJavaScript}.
	 * @param string $url the script URL; normalized before storage
	 * @param string $hash fully-formed `algo-digest` SRI string or a bare base64 digest
	 * @param string $method algorithm prefix prepended to a bare digest (default `sha384`)
	 * @throws TConfigurationException if the URL or hash is empty
	 */
	public function addIntegrity(string $url, string $hash, string $method = 'sha384'): void
	{
		if (($url = trim($url)) === '' || ($hash = trim($hash)) === '') {
			throw new TConfigurationException('integritymanager_entry_invalid', $url);
		}
		$key = THttpUtility::normalizeIntegrityUrl($url);
		TJavaScript::setScriptIntegrity($key, $hash, $method);
		$this->_integrities[$key] = TJavaScript::getScriptIntegrity($key);
	}

	/**
	 * Returns the SRI strings managed by this module.
	 * The array key is the normalized URL and the value is the fully-formed
	 * `algo-digest` SRI string.
	 * @return array<string, string> list of integrity entries
	 */
	public function getIntegrities(): array
	{
		return $this->_integrities;
	}

	/**
	 * Returns the registered SRI string for the given URL, or `null` when none
	 * is managed by this module. The URL is normalized before the lookup.
	 * @param string $url script URL to look up
	 * @return ?string the fully-formed `algo-digest` SRI string, or `null`
	 */
	public function getIntegrity(string $url): ?string
	{
		return $this->_integrities[THttpUtility::normalizeIntegrityUrl($url)] ?? null;
	}

	/**
	 * @return ?string the full path to the file storing integrity information
	 */
	public function getIntegrityFile(): ?string
	{
		return $this->_integrityFile;
	}

	/**
	 * @param string $value integrity data file path (in namespace form). The file
	 * is XML or PHP depending on the application configuration type; see the class
	 * documentation for the file format.
	 * @throws \Prado\Exceptions\TInvalidOperationException if the module is already initialized
	 * @throws TConfigurationException if the file is not in proper namespace format
	 */
	public function setIntegrityFile($value)
	{
		$this->assertUninitialized('IntegrityFile');
		if (($this->_integrityFile = Prado::getPathOfNamespace($value, $this->getApplication()->getConfigurationFileExt())) === null || !is_file($this->_integrityFile)) {
			throw new TConfigurationException('integritymanager_integrityfile_invalid', $value);
		}
	}

	/**
	 * This is an alias for {@see TJavaScript::getRequireScriptIntegrity()}.
	 * @return bool whether remote scripts must carry a registered integrity, defaults to false
	 */
	public function getRequireIntegrity(): bool
	{
		return TJavaScript::getRequireScriptIntegrity();
	}

	/**
	 * Sets whether remote scripts must carry a registered integrity. The value is
	 * stored on {@see TJavaScript::setRequireScriptIntegrity()}, which the render
	 * helpers consult. When enabled, rendering a remote script with no registered
	 * integrity throws a {@see TConfigurationException}.
	 * @param mixed $value whether remote scripts must carry a registered integrity
	 */
	public function setRequireIntegrity($value)
	{
		TJavaScript::setRequireScriptIntegrity(TPropertyValue::ensureBoolean($value));
	}

	/**
	 * Returns the PRADO error message catalogue key used when a configuration
	 * property is changed after the module is initialized.
	 * @return string the message catalogue key
	 */
	protected function getIsInitializedExceptionKey(): string
	{
		return 'integritymanager_integrityfile_unchangeable';
	}
}

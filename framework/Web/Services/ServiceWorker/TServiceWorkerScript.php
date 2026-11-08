<?php

/**
 * TServiceWorkerScript class file
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
use Prado\Web\Javascripts\TJavaScript;

/**
 * TServiceWorkerScript class
 *
 * TServiceWorkerScript builds the script of one service worker from the core
 * runtime and the fragments of the contributors
 * ({@see IServiceWorkerContributor}). {@see TServiceWorkerModule} creates one
 * for the worker the browser requests and passes it to each contributor.
 * ```php
 * public function contributeServiceWorker(TServiceWorkerScript $script): void
 * {
 *     $script->addFragment('greeting', 'prado.onMessage("hello", (name) => "Hello " + name);');
 *     $script->addFragmentFile('sync', __DIR__ . '/sync-worker.js', 20, ['interval' => 60]);
 *     $script->addPrecache(['/index.php?page=Offline', '/images/logo.png']);
 *     $script->addExclude('/index.php?page=Admin');
 * }
 * ```
 *
 * A fragment is the body of a function that receives the runtime API as
 * `prado` and its configuration as `config`. The rendered script is:
 *
 * | Part | Content |
 * |---|---|
 * | Options | `const PRADO_SERVICE_WORKER = {version, worker, scope, precache, exclude, fragments}` |
 * | Runtime | `worker.js`, which defines `prado` |
 * | Fragments | `prado.addFragment(name, function (prado, config) {…}, config)`, by ascending priority, then in the order added |
 * | Start | `prado.start()` |
 *
 * {@see getVersion() Version} is a hash of the other parts. A changed fragment,
 * precache URL, or option changes the bytes of the script, so the browser
 * installs it as an update, and the runtime names its caches by the version.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TServiceWorkerScript extends \Prado\TComponent
{
	/**
	 * The file of the core runtime.
	 */
	public const RUNTIME_FILE = 'worker.js';

	/**
	 * The file of the worker that removes itself.
	 */
	public const UNREGISTER_FILE = 'worker-unregister.js';

	/**
	 * The default priority of a fragment.
	 */
	public const DEFAULT_PRIORITY = 10.0;

	/** @var TServiceWorkerDefinition the worker being built */
	private TServiceWorkerDefinition $_worker;

	/** @var array<string, array{code: string, priority: float, config: array, order: int}> fragments by name */
	private array $_fragments = [];

	/** @var string[] precache URLs */
	private array $_precache = [];

	/** @var string[] excluded URL path prefixes */
	private array $_exclude = [];

	/** @var int order of the next fragment */
	private int $_order = 0;

	/**
	 * Constructs the script of a worker.
	 * @param TServiceWorkerDefinition $worker the worker being built
	 */
	public function __construct(TServiceWorkerDefinition $worker)
	{
		$this->_worker = $worker;
		parent::__construct();
	}

	/**
	 * @return TServiceWorkerDefinition the worker being built
	 */
	public function getWorker(): TServiceWorkerDefinition
	{
		return $this->_worker;
	}

	/**
	 * Adds a fragment, or replaces the fragment of the same name.
	 * @param string $name the fragment name: letters, digits, `_`, `-`, and `.`
	 * @param string $code the body of the fragment function, which receives `prado` and `config`
	 * @param float $priority the order of the fragment; a lower priority runs first
	 * @param array $config the JSON-encodable configuration passed as `config`
	 * @throws TInvalidDataValueException if the name is not valid.
	 */
	public function addFragment(string $name, string $code, float $priority = self::DEFAULT_PRIORITY, array $config = []): void
	{
		if (!preg_match('/^[A-Za-z0-9_.-]+$/', $name)) {
			throw new TInvalidDataValueException('serviceworkerscript_fragment_name_invalid', $name);
		}
		$this->_fragments[$name] = ['code' => $code, 'priority' => $priority, 'config' => $config, 'order' => $this->_order++];
	}

	/**
	 * Adds a fragment whose body is a file.
	 * @param string $name the fragment name
	 * @param string $file the path of the JavaScript file
	 * @param float $priority the order of the fragment; a lower priority runs first
	 * @param array $config the JSON-encodable configuration passed as `config`
	 * @throws TConfigurationException if the file cannot be read.
	 */
	public function addFragmentFile(string $name, string $file, float $priority = self::DEFAULT_PRIORITY, array $config = []): void
	{
		$this->addFragment($name, $this->readFile($file), $priority, $config);
	}

	/**
	 * @param string $name the fragment name
	 * @return bool whether the script has the fragment
	 */
	public function hasFragment(string $name): bool
	{
		return isset($this->_fragments[$name]);
	}

	/**
	 * Removes a fragment.
	 * @param string $name the fragment name
	 */
	public function removeFragment(string $name): void
	{
		unset($this->_fragments[$name]);
	}

	/**
	 * @return array<string, array{code: string, priority: float, config: array}> the
	 *   fragments by name, in the order they run
	 */
	public function getFragments(): array
	{
		$fragments = $this->_fragments;
		uasort($fragments, fn ($a, $b) => [$a['priority'], $a['order']] <=> [$b['priority'], $b['order']]);
		return array_map(fn ($fragment) => array_diff_key($fragment, ['order' => true]), $fragments);
	}

	/**
	 * Adds URLs that the worker stores on install.
	 * @param string|string[] $urls a URL or URLs
	 */
	public function addPrecache(array|string $urls): void
	{
		foreach ((array) $urls as $url) {
			$url = TPropertyValue::ensureString($url);
			if ($url !== '' && !in_array($url, $this->_precache, true)) {
				$this->_precache[] = $url;
			}
		}
	}

	/**
	 * @return string[] the precache URLs
	 */
	public function getPrecache(): array
	{
		return $this->_precache;
	}

	/**
	 * Adds URL path prefixes, with query, that the fetch handlers never see.
	 * @param string|string[] $prefixes a prefix or prefixes, such as `/index.php?sw=`
	 */
	public function addExclude(array|string $prefixes): void
	{
		foreach ((array) $prefixes as $prefix) {
			$prefix = TPropertyValue::ensureString($prefix);
			if ($prefix !== '' && !in_array($prefix, $this->_exclude, true)) {
				$this->_exclude[] = $prefix;
			}
		}
	}

	/**
	 * @return string[] the excluded URL path prefixes
	 */
	public function getExclude(): array
	{
		return $this->_exclude;
	}

	/**
	 * Returns the options of the script, without the version.
	 * @return array the `PRADO_SERVICE_WORKER` options
	 */
	public function getOptions(): array
	{
		$fragments = [];
		foreach ($this->getFragments() as $name => $fragment) {
			$fragments[$name] = (object) $fragment['config'];
		}
		return [
			'worker' => $this->getWorker()->getName(),
			'scope' => $this->getWorker()->getScope(),
			'precache' => $this->getPrecache(),
			'exclude' => $this->getExclude(),
			'fragments' => (object) $fragments,
		];
	}

	/**
	 * @return string the hash of the options, the runtime, and the fragments
	 */
	public function getVersion(): string
	{
		return $this->hashBody(TJavaScript::jsonEncode($this->getOptions(), JSON_UNESCAPED_SLASHES), $this->renderBody());
	}

	/**
	 * Renders the worker script.
	 * @return string the JavaScript of the worker
	 */
	public function render(): string
	{
		$options = $this->getOptions();
		$body = $this->renderBody();
		$options = ['version' => $this->hashBody(TJavaScript::jsonEncode($options, JSON_UNESCAPED_SLASHES), $body)] + $options;
		return 'const PRADO_SERVICE_WORKER = ' . TJavaScript::jsonEncode($options, JSON_UNESCAPED_SLASHES) . ";\n\n" . $body;
	}

	/**
	 * Renders the script of a worker that clears its caches and unregisters itself.
	 * @param TServiceWorkerDefinition $worker the disabled worker
	 * @return string the JavaScript of the worker
	 */
	public static function renderUnregister(TServiceWorkerDefinition $worker): string
	{
		$options = ['worker' => $worker->getName(), 'scope' => $worker->getScope()];
		return 'const PRADO_SERVICE_WORKER = ' . TJavaScript::jsonEncode($options, JSON_UNESCAPED_SLASHES) . ";\n\n"
			. self::readSource(self::UNREGISTER_FILE);
	}

	/**
	 * Returns the path of a file of the framework's service worker sources.
	 * @param string $file the file name, such as `worker.js`
	 * @return string the path
	 */
	public static function getSourcePath(string $file): string
	{
		return Prado::getFrameworkPath() . '/Web/Javascripts/source/prado/serviceworker/' . $file;
	}

	/**
	 * Renders the runtime, the fragments, and the start call.
	 * @return string the JavaScript after the options
	 */
	protected function renderBody(): string
	{
		$body = self::readSource(self::RUNTIME_FILE) . "\n";
		foreach ($this->getFragments() as $name => $fragment) {
			$quoted = TJavaScript::jsonEncode($name);
			$body .= "prado.addFragment($quoted, function (prado, config) {\n" . $fragment['code']
				. "\n}, PRADO_SERVICE_WORKER.fragments[$quoted]);\n\n";
		}
		return $body . "prado.start();\n";
	}

	/**
	 * @param string $options the JSON of the options
	 * @param string $body the JavaScript after the options
	 * @return string the version hash
	 */
	protected function hashBody(string $options, string $body): string
	{
		return substr(hash('sha256', $options . "\n" . $body), 0, 16);
	}

	/**
	 * Reads a framework service worker source file.
	 * @param string $file the file name
	 * @throws TConfigurationException if the file cannot be read.
	 * @return string the file content
	 */
	protected static function readSource(string $file): string
	{
		return self::readScriptFile(self::getSourcePath($file));
	}

	/**
	 * Reads a fragment file.
	 * @param string $file the path of the file
	 * @throws TConfigurationException if the file cannot be read.
	 * @return string the file content
	 */
	protected function readFile(string $file): string
	{
		return self::readScriptFile($file);
	}

	/**
	 * Reads a JavaScript file.
	 * @param string $file the path of the file
	 * @throws TConfigurationException if the file cannot be read.
	 * @return string the file content
	 */
	private static function readScriptFile(string $file): string
	{
		if (!is_file($file) || ($code = @file_get_contents($file)) === false) {
			throw new TConfigurationException('serviceworkerscript_file_invalid', $file);
		}
		return $code;
	}
}

<?php

/**
 * TGlobalizationProxy class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\I18N;

use Prado\Exceptions\TConfigurationException;
use Prado\IModuleDependency;
use Prado\IProxy;
use Prado\Prado;
use Prado\TComponent;
use Prado\TComponentProxyTrait;
use Prado\TModule;
use Prado\Util\Log\TLogger;
use Prado\Xml\TXmlElement;

/**
 * TGlobalizationProxy class.
 *
 * TGlobalizationProxy is a transparent globalization module that delegates every
 * public {@see TGlobalization} property and method to another TGlobalization
 * module registered with the application. The application globalization slot
 * can be swapped at configuration time without changing the consumers that
 * depend on it.
 *
 * ## Configuration
 *
 * {@see getBackingGlobalizationId BackingGlobalizationId} names the backing
 * globalization module. TGlobalizationProxy declares that module as a required
 * {@see IModuleDependency}, so the application initializes it first.
 *
 * ## Transparency
 *
 * The culture, charset, default culture, default charset, translation
 * configuration, translation catalogue, culture variants, localized resource
 * lookup, and RTL detection all read from and write to the backing module.
 * Base-class methods are not reached by `__call`, so each public method of
 * {@see TGlobalization} is overridden to delegate. Other property reads and
 * writes, method calls, and events reach the backing through
 * {@see TComponentProxyTrait}, which wires the backing's public `on` events on
 * first resolution. `dy` and `fx` names are never forwarded.
 *
 * {@see init()} registers the proxy as the application globalization module and
 * raises `dyInit`; it does not run {@see TGlobalization::init()}. The translation
 * configuration belongs to the backing module, so a `<translation>` element (or a
 * `translate` or `translation` key in a PHP configuration) on the proxy throws.
 *
 * Replacing a {@see setBackingGlobalizationId BackingGlobalizationId} logs a
 * {@see TLogger::WARNING} so a runtime swap is visible in the application log.
 *
 * Configure in `application.xml`:
 * ```xml
 * <module id="globalization" class="Prado\I18N\TGlobalizationProxy" BackingGlobalizationId="realGlobalization" />
 * <module id="realGlobalization" class="Prado\I18N\TGlobalization" DefaultCulture="en_US">
 *     <translation type="gettext" source="Application.messages" autosave="true" cache="true" />
 * </module>
 * ```
 *
 * Or instantiate directly:
 * ```php
 * $proxy = new TGlobalizationProxy();
 * $proxy->setBackingGlobalizationId('realGlobalization');
 * $proxy->init(null);
 * // All operations now delegate to the 'realGlobalization' module.
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TGlobalizationProxy extends TGlobalization implements IModuleDependency, IProxy
{
	use TComponentProxyTrait;

	/** @var string module ID of the backing globalization module; empty until configured */
	private string $_backingGlobalizationId = '';

	// ----------------------------------------------------------------- lifecycle

	/**
	 * Declares the backing globalization module as a required dependency so that
	 * {@see \Prado\TApplication} initializes it before this proxy, in every pass.
	 * @param bool $isPreInit `true` for the dyPreInit pass, `false` for init(); not used
	 * @return ?string the backing globalization module ID, or null when none is configured
	 */
	public function getModuleDependencies(bool $isPreInit = false): ?string
	{
		$id = $this->getBackingGlobalizationId();
		return $id === '' ? null : $id;
	}

	/**
	 * Initializes the proxy module. The proxy registers itself as the application
	 * globalization module and raises `dyInit`; {@see TGlobalization::init()} is
	 * not called because its culture defaults and translation configuration belong
	 * to the backing module. A `<translation>` element (or a `translate` or
	 * `translation` key in a PHP configuration) on the proxy is rejected.
	 * @param null|array|\Prado\Xml\TXmlElement $config module configuration
	 * @throws TConfigurationException when {@see getBackingGlobalizationId BackingGlobalizationId} is empty
	 * @throws TConfigurationException when the configuration carries a translation element
	 */
	public function init($config)
	{
		if ($this->getBackingGlobalizationId() === '') {
			throw new TConfigurationException('globalizationproxy_backing_globalization_id_required');
		}
		if ($this->hasTranslationConfig($config)) {
			throw new TConfigurationException('globalizationproxy_translation_not_allowed', $this->getID());
		}
		$this->setAppGlobalization();
		TModule::init($config);
	}

	/**
	 * Returns whether a module configuration carries a translation element.
	 * @param null|array|\Prado\Xml\TXmlElement $config module configuration
	 * @return bool whether a `<translation>` element, or a `translate` or `translation` key, is present
	 */
	protected function hasTranslationConfig($config): bool
	{
		if (is_array($config)) {
			return isset($config['translate']) || isset($config['translation']);
		}
		return $config instanceof TXmlElement && $config->getElementByTagName('translation') !== null;
	}

	// ----------------------------------------------------------------- TComponentProxyTrait implementation

	/**
	 * Returns the backing globalization module through {@see getGlobalization()}.
	 * @throws TConfigurationException when {@see getBackingGlobalizationId BackingGlobalizationId} is empty
	 * @throws TConfigurationException when the referenced module does not exist
	 * @throws TConfigurationException when the referenced module is not a {@see TGlobalization}
	 * @return ?TComponent the backing globalization module
	 */
	public function getProxyBacking(): ?TComponent
	{
		return $this->getGlobalization();
	}

	/**
	 * Returns whether a {@see getBackingGlobalizationId BackingGlobalizationId} is
	 * configured, which enables lazy resolution from the module registry.
	 * @return bool whether lazy resolution is possible
	 */
	protected function canResolveProxyBacking(): bool
	{
		return $this->getBackingGlobalizationId() !== '';
	}

	// --------------------------------------------------------------- accessors

	/**
	 * @return string the stored module ID of the backing globalization module
	 */
	protected function getBackingGlobalizationIdDirect(): string
	{
		return $this->_backingGlobalizationId;
	}

	/**
	 * @param string $value the module ID to store
	 */
	protected function setBackingGlobalizationIdDirect(string $value): void
	{
		$this->_backingGlobalizationId = $value;
	}

	/**
	 * @return string the module ID of the backing globalization module
	 */
	public function getBackingGlobalizationId(): string
	{
		return $this->getBackingGlobalizationIdDirect();
	}

	/**
	 * Sets the module ID of the backing globalization module. Replacing a non-empty
	 * ID detaches the event forwarders, logs a {@see TLogger::WARNING}, and drops
	 * the resolved backing so the next operation resolves the new module.
	 * @param string $value the module ID of the globalization module to proxy
	 */
	public function setBackingGlobalizationId(string $value): void
	{
		$current = $this->getBackingGlobalizationIdDirect();
		if ($value === $current) {
			return;
		}
		if ($current !== '') {
			$this->detachProxy();
			Prado::log(
				sprintf("TGlobalizationProxy.BackingGlobalizationId changed from '%s' to '%s'.", $current, $value),
				TLogger::WARNING,
				'prado.i18n'
			);
		}
		$this->setBackingGlobalizationIdDirect($value);
		$this->setGlobalizationDirect(null);
	}

	/**
	 * Returns the stored backing globalization module, narrowing the trait's
	 * `?TComponent` to `?TGlobalization`.
	 * @return ?TGlobalization the backing globalization module, or null when not yet resolved
	 */
	protected function getGlobalizationDirect(): ?TGlobalization
	{
		$backing = $this->getProxyBackingDirect();
		return $backing instanceof TGlobalization ? $backing : null;
	}

	/**
	 * @param ?TGlobalization $value the backing globalization module to store
	 */
	protected function setGlobalizationDirect(?TGlobalization $value): void
	{
		$this->setProxyBackingDirect($value);
	}

	/**
	 * Returns the backing {@see TGlobalization}, resolving it through
	 * {@see \Prado\TApplication::getModule()} on first call. The first resolution
	 * calls {@see attachProxy()}.
	 * @throws TConfigurationException when {@see getBackingGlobalizationId BackingGlobalizationId} is empty
	 * @throws TConfigurationException when the referenced module does not exist
	 * @throws TConfigurationException when the referenced module is not a {@see TGlobalization}
	 * @return TGlobalization the backing globalization module
	 */
	public function getGlobalization(): TGlobalization
	{
		$globalization = $this->getGlobalizationDirect();
		if ($globalization === null) {
			$id = $this->getBackingGlobalizationId();
			if ($id === '') {
				throw new TConfigurationException('globalizationproxy_backing_globalization_id_required');
			}
			$globalization = $this->getApplication()->getModule($id);
			if ($globalization === null) {
				throw new TConfigurationException('globalizationproxy_globalization_not_found', $id);
			}
			if (!($globalization instanceof TGlobalization)) {
				throw new TConfigurationException('globalizationproxy_invalid_globalization_type', $id);
			}
			$this->setGlobalizationDirect($globalization);
			$this->attachProxy();
		}
		return $globalization;
	}

	// ----------------------------------------------------------------- TGlobalization delegation

	/**
	 * @return bool whether the backing translates from the default culture
	 */
	public function getTranslateDefaultCulture()
	{
		return $this->getGlobalization()->getTranslateDefaultCulture();
	}

	/**
	 * @param bool $value whether the backing translates from the default culture
	 */
	public function setTranslateDefaultCulture($value)
	{
		$this->getGlobalization()->setTranslateDefaultCulture($value);
	}

	/**
	 * @return string the backing's default culture
	 */
	public function getDefaultCulture()
	{
		return $this->getGlobalization()->getDefaultCulture();
	}

	/**
	 * @param string $culture the backing's default culture, e.g. `en_US`
	 */
	public function setDefaultCulture($culture)
	{
		$this->getGlobalization()->setDefaultCulture($culture);
	}

	/**
	 * @return string the backing's default charset
	 */
	public function getDefaultCharset()
	{
		return $this->getGlobalization()->getDefaultCharset();
	}

	/**
	 * @param string $charset the backing's default charset, e.g. `UTF-8`
	 */
	public function setDefaultCharset($charset)
	{
		$this->getGlobalization()->setDefaultCharset($charset);
	}

	/**
	 * @return string the backing's current culture
	 */
	public function getCulture()
	{
		return $this->getGlobalization()->getCulture();
	}

	/**
	 * Sets the backing's active culture; BCP 47 hyphens are normalized by the backing.
	 * @param string $culture culture, e.g. `en_US`
	 */
	public function setCulture($culture)
	{
		$this->getGlobalization()->setCulture($culture);
	}

	/**
	 * @return string the backing's current charset
	 */
	public function getCharset()
	{
		return $this->getGlobalization()->getCharset();
	}

	/**
	 * @param string $charset the backing's charset, e.g. `UTF-8`
	 */
	public function setCharset($charset)
	{
		$this->getGlobalization()->setCharset($charset);
	}

	/**
	 * @return null|\Prado\Collections\TMap the backing's translation source configuration
	 */
	public function getTranslationConfiguration()
	{
		return $this->getGlobalization()->getTranslationConfiguration();
	}

	/**
	 * @return string the backing's current translation catalogue
	 */
	public function getTranslationCatalogue()
	{
		return $this->getGlobalization()->getTranslationCatalogue();
	}

	/**
	 * @param string $value the backing's translation catalogue
	 */
	public function setTranslationCatalogue($value)
	{
		$this->getGlobalization()->setTranslationCatalogue($value);
	}

	/**
	 * Returns the variants of a culture from the backing; null uses the backing's current culture.
	 * @param ?string $culture the culture string
	 * @return array variants of the culture
	 */
	public function getCultureVariants($culture = null)
	{
		return $this->getGlobalization()->getCultureVariants($culture);
	}

	/**
	 * Returns the candidate localized files for a resource from the backing.
	 * @param string $file filename
	 * @param ?string $culture culture string, null to use the backing's current culture
	 * @return array list of possible localized resource files
	 */
	public function getLocalizedResource($file, $culture = null)
	{
		return $this->getGlobalization()->getLocalizedResource($file, $culture);
	}

	/**
	 * Returns whether a culture is right to left, from the backing.
	 * @param ?string $culture the culture, null to use the backing's current culture
	 * @return bool whether the culture is right to left
	 */
	public function getIsCultureRTL($culture = null)
	{
		return $this->getGlobalization()->getIsCultureRTL($culture);
	}

	/**
	 * @param bool $rtl whether the backing's current culture is right to left
	 */
	public function setIsCultureRTL($rtl)
	{
		$this->getGlobalization()->setIsCultureRTL($rtl);
	}

	// -------------------------------------------------- serialization

	/**
	 * Excludes the transient forwarder list, the resolved backing (re-resolved
	 * from the module registry after unserialization), and an empty backing ID
	 * from serialization.
	 * @param array $exprops excluded-properties list, passed by reference
	 */
	protected function _getZappableSleepProps(&$exprops)
	{
		parent::_getZappableSleepProps($exprops);
		$this->_addProxyEventNamesZappable($exprops);
		$this->_addProxyBackingZappable($exprops);
		if ($this->getBackingGlobalizationIdDirect() === '') {
			$exprops[] = "\0" . __CLASS__ . "\0_backingGlobalizationId";
		}
	}
}

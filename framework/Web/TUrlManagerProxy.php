<?php

/**
 * TUrlManagerProxy class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web;

use Prado\Exceptions\TConfigurationException;
use Prado\IModuleDependency;
use Prado\IProxy;
use Prado\Prado;
use Prado\TComponent;
use Prado\TComponentProxyTrait;
use Prado\Util\Log\TLogger;

/**
 * TUrlManagerProxy class.
 *
 * TUrlManagerProxy is a transparent URL manager module that delegates URL
 * construction and parsing to another {@see TUrlManager} module registered with
 * the application. {@see THttpRequest::setUrlManager UrlManager} names one
 * module ID, so the proxy lets the configuration swap the URL rule set (a
 * {@see TUrlMapping}, for example) without changing the request configuration.
 *
 * ## Configuration
 *
 * {@see getBackingUrlManagerId BackingUrlManagerId} names the backing URL manager
 * module. TUrlManagerProxy declares that module as a required
 * {@see IModuleDependency}, so the application initializes it first.
 *
 * ## Transparency
 *
 * {@see constructUrl()} and {@see parseUrl()} call the backing's methods. Other
 * property reads and writes, method calls, and events reach the backing through
 * {@see TComponentProxyTrait}, which wires the backing's public `on` events on
 * first resolution.
 *
 * Configure in `application.xml`:
 * ```xml
 * <module id="request" class="Prado\Web\THttpRequest" UrlManager="urls" />
 * <module id="urls" class="Prado\Web\TUrlManagerProxy" BackingUrlManagerId="friendlyUrls" />
 * <module id="friendlyUrls" class="Prado\Web\TUrlMapping" EnableCustomUrl="true">
 *     <url ServiceParameter="Home" pattern="home" />
 * </module>
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TUrlManagerProxy extends TUrlManager implements IModuleDependency, IProxy
{
	use TComponentProxyTrait;

	/** @var string module ID of the backing URL manager; empty until configured */
	private string $_backingUrlManagerId = '';

	// ----------------------------------------------------------------- lifecycle

	/**
	 * Declares the backing URL manager module as a required dependency so that
	 * {@see \Prado\TApplication} initializes it before this proxy, in every pass.
	 * @param bool $isPreInit `true` for the dyPreInit pass, `false` for init(); not used
	 * @return ?string the backing module ID, or null when none is configured
	 */
	public function getModuleDependencies(bool $isPreInit = false): ?string
	{
		$id = $this->getBackingUrlManagerId();
		return $id === '' ? null : $id;
	}

	/**
	 * Initializes the proxy module.
	 * @param null|array|\Prado\Xml\TXmlElement $config module configuration
	 * @throws TConfigurationException when {@see getBackingUrlManagerId BackingUrlManagerId} is empty
	 */
	public function init($config)
	{
		if ($this->getBackingUrlManagerId() === '') {
			throw new TConfigurationException('urlmanagerproxy_backing_url_manager_id_required');
		}
		parent::init($config);
	}

	// ----------------------------------------------------------------- TComponentProxyTrait implementation

	/**
	 * Returns the backing URL manager through {@see getUrlManager()}.
	 * @throws TConfigurationException when {@see getBackingUrlManagerId BackingUrlManagerId} is empty
	 * @throws TConfigurationException when the referenced module does not exist
	 * @throws TConfigurationException when the referenced module is not a {@see TUrlManager}
	 * @return ?TComponent the backing URL manager
	 */
	public function getProxyBacking(): ?TComponent
	{
		return $this->getUrlManager();
	}

	/**
	 * Returns whether a {@see getBackingUrlManagerId BackingUrlManagerId} is
	 * configured, which enables lazy resolution from the module registry.
	 * @return bool whether lazy resolution is possible
	 */
	protected function canResolveProxyBacking(): bool
	{
		return $this->getBackingUrlManagerId() !== '';
	}

	// --------------------------------------------------------------- accessors

	/**
	 * @return string the stored module ID of the backing URL manager
	 */
	protected function getBackingUrlManagerIdDirect(): string
	{
		return $this->_backingUrlManagerId;
	}

	/**
	 * @param string $value the module ID to store
	 */
	protected function setBackingUrlManagerIdDirect(string $value): void
	{
		$this->_backingUrlManagerId = $value;
	}

	/**
	 * @return string the module ID of the backing URL manager
	 */
	public function getBackingUrlManagerId(): string
	{
		return $this->getBackingUrlManagerIdDirect();
	}

	/**
	 * Sets the module ID of the backing URL manager. Replacing a non-empty ID
	 * detaches the event forwarders, logs a {@see TLogger::WARNING}, and drops the
	 * resolved backing so the next operation resolves the new module.
	 * @param string $value the module ID of the URL manager to proxy
	 */
	public function setBackingUrlManagerId(string $value): void
	{
		$current = $this->getBackingUrlManagerIdDirect();
		if ($value === $current) {
			return;
		}
		if ($current !== '') {
			$this->detachProxy();
			Prado::log(
				sprintf("TUrlManagerProxy.BackingUrlManagerId changed from '%s' to '%s'.", $current, $value),
				TLogger::WARNING,
				'prado.web'
			);
		}
		$this->setBackingUrlManagerIdDirect($value);
		$this->setUrlManagerDirect(null);
	}

	/**
	 * Returns the stored backing URL manager, narrowing the trait's `?TComponent` to `?TUrlManager`.
	 * @return ?TUrlManager the backing URL manager, or null when not yet resolved
	 */
	protected function getUrlManagerDirect(): ?TUrlManager
	{
		$backing = $this->getProxyBackingDirect();
		return $backing instanceof TUrlManager ? $backing : null;
	}

	/**
	 * @param ?TUrlManager $value the backing URL manager to store
	 */
	protected function setUrlManagerDirect(?TUrlManager $value): void
	{
		$this->setProxyBackingDirect($value);
	}

	/**
	 * Returns the backing {@see TUrlManager}, resolving it through
	 * {@see \Prado\TApplication::getModule()} on first call. The first resolution
	 * calls {@see attachProxy()}.
	 * @throws TConfigurationException when {@see getBackingUrlManagerId BackingUrlManagerId} is empty
	 * @throws TConfigurationException when the referenced module does not exist
	 * @throws TConfigurationException when the referenced module is not a {@see TUrlManager}
	 * @return TUrlManager the backing URL manager module
	 */
	public function getUrlManager(): TUrlManager
	{
		$manager = $this->getUrlManagerDirect();
		if ($manager === null) {
			$id = $this->getBackingUrlManagerId();
			if ($id === '') {
				throw new TConfigurationException('urlmanagerproxy_backing_url_manager_id_required');
			}
			$manager = $this->getApplication()->getModule($id);
			if ($manager === null) {
				throw new TConfigurationException('urlmanagerproxy_url_manager_not_found', $id);
			}
			if (!($manager instanceof TUrlManager)) {
				throw new TConfigurationException('urlmanagerproxy_invalid_url_manager_type', $id);
			}
			$this->setUrlManagerDirect($manager);
			$this->attachProxy();
		}
		return $manager;
	}

	// ----------------------------------------------------------------- TUrlManager overrides

	/**
	 * Constructs a URL through the backing URL manager.
	 * @param string $serviceID service ID
	 * @param string $serviceParam service parameter
	 * @param null|array|\Traversable $getItems GET parameters, null if not provided
	 * @param bool $encodeAmpersand whether to encode the ampersand in URL
	 * @param bool $encodeGetItems whether to encode the GET parameters (their names and values)
	 * @return string URL
	 */
	public function constructUrl($serviceID, $serviceParam, $getItems, $encodeAmpersand, $encodeGetItems)
	{
		return $this->getUrlManager()->constructUrl($serviceID, $serviceParam, $getItems, $encodeAmpersand, $encodeGetItems);
	}

	/**
	 * Parses the request URL through the backing URL manager.
	 * @return array list of input parameters, indexed by parameter names
	 */
	public function parseUrl()
	{
		return $this->getUrlManager()->parseUrl();
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
		if ($this->getBackingUrlManagerIdDirect() === '') {
			$exprops[] = "\0" . __CLASS__ . "\0_backingUrlManagerId";
		}
	}
}

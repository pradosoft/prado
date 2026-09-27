<?php

/**
 * IProxy interface file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado;

/**
 * IProxy interface
 *
 * IProxy marks every transparent-proxy class in the framework, so a consumer
 * can test for a proxy without knowing the concrete proxy type, and exposes the
 * component the proxy stands for through {@see getProxyBacking()}. Every
 * implementation uses {@see TComponentProxyTrait}, which supplies the method:
 *
 * ```php
 * if ($module instanceof IProxy) {
 *     $real = $module->getProxyBacking();
 * }
 * ```
 *
 * Implementations:
 * - {@see TComponentProxy} wraps any {@see TComponent} set directly.
 * - {@see TModuleProxy} wraps any {@see TModule} registered with the application.
 * - {@see \Prado\Caching\TCacheProxy} wraps a {@see \Prado\Caching\TCache} module.
 * - {@see \Prado\Data\TDataSourceConfigProxy} wraps a {@see \Prado\Data\TDataSourceConfig} module.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
interface IProxy
{
	/**
	 * Returns the component the proxy forwards to, resolving it lazily when needed.
	 * @throws \Prado\Exceptions\TConfigurationException when the backing is
	 *   required but not configured or cannot be found
	 * @return ?TComponent the backing component, or null when unavailable
	 */
	public function getProxyBacking(): ?TComponent;
}

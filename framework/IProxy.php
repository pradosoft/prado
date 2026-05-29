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
 * IProxy is a marker interface implemented by every transparent-proxy class in
 * the framework. It provides a single identity for the proxy family so that
 * consumers can test whether an object is a proxy without knowing the concrete
 * proxy type. Every IProxy implementation also uses {@see TComponentProxyTrait},
 * which supplies `getProxyBacking()`:
 *
 * ```php
 * if ($module instanceof IProxy) {
 *     $real = $module->getProxyBacking();
 * }
 * ```
 *
 * Implementations:
 * - {@see TComponentProxy} — wraps any {@see TComponent} set directly.
 * - {@see TModuleProxy} — wraps any {@see TModule} registered with the application.
 * - {@see \Prado\Caching\TCacheProxy} — wraps a {@see \Prado\Caching\TCache} module.
 * - {@see \Prado\Data\TDataSourceConfigProxy} — wraps a {@see \Prado\Data\TDataSourceConfig} module.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
interface IProxy
{
}

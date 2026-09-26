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
 * can test for a proxy without knowing the concrete proxy type. Every
 * implementation uses {@see TComponentProxyTrait}, which supplies
 * `getProxyBacking()`:
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
}

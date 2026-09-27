<?php

/**
 * ICompressionConfigurable interface file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\Compression;

/**
 * ICompressionConfigurable interface.
 *
 * A component that holds its own {@see TCompressionConfig}.  Code that compresses on a
 * component's behalf checks for this interface and reads the settings from it; a
 * component without it is compressed under the caller's own settings.
 * {@see TCompressionConfigTrait} implements both methods.
 *
 * ```php
 * class TMyPersister extends \Prado\TComponent implements \Prado\Web\UI\IPageStatePersister, ICompressionConfigurable
 * {
 *     use TCompressionConfigTrait;
 * }
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
interface ICompressionConfigurable
{
	/**
	 * @return TCompressionConfig the compression settings of this component.
	 */
	public function getCompression(): TCompressionConfig;

	/**
	 * @param TCompressionConfig $value the compression settings of this component.
	 */
	public function setCompression(TCompressionConfig $value): void;
}

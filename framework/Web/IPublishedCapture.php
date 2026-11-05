<?php

/**
 * IPublishedCapture interface
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web;

/**
 * IPublishedCapture interface
 *
 * A lightweight contract for an {@see IPublishable} that wants the published
 * destination path and URL handed back after {@see TAssetManager::publishFilePath}
 * writes it. A virtual control (e.g. a TDot SVG) implements this to learn where it
 * landed so it can render the published URL. Richer capturing publishers extend
 * this interface to capture through the same primitive.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
interface IPublishedCapture
{
	/**
	 * Captures the published destination path of the asset on publishing.
	 * @param string $path the published path of the asset.
	 */
	public function setPublishedPath($path): void;

	/**
	 * Captures the published URL of the asset on publishing.
	 * @param string $path the published URL of the asset.
	 */
	public function setPublishedUrl($path): void;
}

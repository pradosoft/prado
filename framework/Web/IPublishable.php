<?php

/**
 * IPublishable interface
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web;

/**
 * IPublishable interface
 *
 * A lightweight contract for things that publish to the asset directory by
 * generating their own content under a translated file name, rather than being
 * copied from a source file on disk.
 *
 * {@see TAssetManager::publishFilePath} accepts an IPublishable directly, so a
 * control can be its own virtual file — generated on the fly, with a unique,
 * content-derived name (e.g. an SVG produced by a TDot control) — without being
 * copied from a source file on disk. Richer publishers extend this interface to
 * ride on the same publishing primitive.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
interface IPublishable
{
	/**
	 * The virtual file path that determines where the content publishes: its
	 * directory keys the published hash directory and its basename is the published
	 * file name. A trailing slash ("/") designates a directory.
	 * @return null|false|string the virtual file path; null cancels publishing,
	 *   false or an empty string is invalid.
	 */
	public function getAssetFilePath();

	/**
	 * The modification/version time used to decide whether a republish is needed.
	 * Distinct content should yield a distinct file name (so this can simply return 0).
	 * @return false|int the modification time in unix seconds, or false.
	 */
	public function getAssetModificationDate();

	/**
	 * Generates and writes the content to the destination.
	 * @param string $dst the destination file (or directory) path to write into.
	 * @return ?bool whether the content was published; null when published without
	 *   post-processing being raised.
	 */
	public function publish(string $dst): ?bool;
}

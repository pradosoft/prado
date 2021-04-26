<?php

/**
 * TPageNoCacheBehavior class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Behaviors;

use Prado\Web\UI\WebControls\TMetaTag;

/**
 * TPageNoCacheBehavior attaches to pages and adds no-cache meta to the head.
 * @author Brad Anderson <belisoful@icloud.com>
 * @package Prado\Util\Behaviors
 * @since 4.2.0
 */
class TPageNoCacheBehavior extends \Prado\Util\TBehavior
{
	
	/**
	 * This handles the TPage.OnInitComplete event to place no-cache
	 * meta in the head.
	 * @return array of events as keys and methods as values
	 */
	public function events()
	{
		return ['OnInitComplete' => 'addNoCacheMeta'];
	}
	
	/**
	 * This method places no-cache meta in the head.
	 * @param $page object raising the event
	 * @param $param mixed the parameter of the raised event
	 */
	public function addNoCacheMeta($page, $param)
	{
		if ($head = $page->getHead()) {
			$hasExpires = $hasPragma = $hasCacheControl = false;
			$metatags = $head->getMetaTags();
			foreach ($metatags as $meta) {
				if ($meta->getHttpEquiv() == 'Expires') {
					$hasExpires = true;
				} elseif ($meta->getHttpEquiv() == 'Pragma') {
					$hasPragma = true;
				} elseif ($meta->getHttpEquiv() == 'Cache-Control') {
					$hasCacheControl = true;
				}
			}
			if (!$hasExpires) {
				$meta = new TMetaTag();
				$meta->setHttpEquiv('Expires');
				$meta->setContent('Fri, Jan 01 1900 00:00:00 GMT');
				$metatags->add($meta);
			}
			if (!$hasPragma) {
				$meta = new TMetaTag();
				$meta->setHttpEquiv('Pragma');
				$meta->setContent('no-cache');
				$metatags->add($meta);
			}
			if (!$hasCacheControl) {
				$meta = new TMetaTag();
				$meta->setHttpEquiv('Cache-Control');
				$meta->setContent('no-cache');
				$metatags->add($meta);
			}
		}
	}
}

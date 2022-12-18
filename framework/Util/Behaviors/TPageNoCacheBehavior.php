<?php

/**
 * TPageNoCacheBehavior class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Behaviors;

use Prado\TPropertyValue;
use Prado\Web\UI\WebControls\TMetaTag;

/**
 * TPageNoCacheBehavior class.
 *
 * TPageNoCacheBehavior attaches to pages and adds no-cache meta to the head.
 *
 * {@link getCheckMetaNoCache} specifies whether or not to check the existing
 * meta tags (in THead of the TPage) before adding the no cache tags. By default
 * getCheckMetaNoCache is turned off for performance.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.2.0
 */
class TPageNoCacheBehavior extends \Prado\Util\TBehavior
{
	/** @var bool check the existing meta tags for the no cache before adding them */
	private $_checkMetaNoCache = false;

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
	 * @param object $page object raising the event
	 * @param mixed $param the parameter of the raised event
	 */
	public function addNoCacheMeta($page, $param)
	{
		if ($this->getEnabled() && $head = $page->getHead()) {
			$hasExpires = $hasPragma = $hasCacheControl = false;
			$metatags = $head->getMetaTags();
			if ($this->_checkMetaNoCache) {
				foreach ($metatags as $meta) {
					$httpEquiv = strtolower($meta->getHttpEquiv());
					if ($httpEquiv == 'expires') {
						$hasExpires = true;
					} elseif ($httpEquiv == 'pragma') {
						$hasPragma = true;
					} elseif ($httpEquiv == 'cache-control') {
						$hasCacheControl = true;
					}
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

	/**
	 * @return bool checks existing meta tags for no cache
	 */
	public function getCheckMetaNoCache()
	{
		return $this->_checkMetaNoCache;
	}

	/**
	 * @param bool $value checks existing meta tags for no cache
	 */
	public function setCheckMetaNoCache($value)
	{
		$this->_checkMetaNoCache = TPropertyValue::ensureBoolean($value);
	}
}

<?php

/**
 * TPageGlobalizationCharsetBehavior class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Behaviors;

use Prado\Web\UI\WebControls\TMetaTag;

/**
 * TPageGlobalizationCharsetBehavior attaches to pages and adds charset
 * meta to the head from globalization.  If there is no globalization,
 * the default charset is used, 'utf-8'.
 * @author Brad Anderson <belisoful@icloud.com>
 * @package Prado\Util\Behaviors
 * @since 4.2.0
 */
class TPageGlobalizationCharsetBehavior extends \Prado\Util\TBehavior
{
	
	/**
	 * This handles the TPage.OnInitComplete event to place no-cache
	 * meta in the head.
	 * @return array of events as keys and methods as values
	 */
	public function events()
	{
		return ['OnInitComplete' => 'addCharsetMeta'];
	}
	
	/**
	 * This method places no-cache meta in the head.
	 * @param $page object raising the event
	 * @param $param mixed the parameter of the raised event
	 */
	public function addCharsetMeta($page, $param)
	{
		if ($head = $page->getHead()) {
			$hasCharset = false;
			$metatags = $head->getMetaTags();
			foreach ($metatags as $meta) {
				if (empty($meta->getHttpEquiv()) && empty($meta->getContent()) && empty($meta->getName()) && empty($meta->getScheme()) && !empty($meta->getCharset())) {
					$hasCharset = true;
				}
			}
			if (!$hasCharset) {
				$charset = 'utf-8';
				if ($globalization = \Prado::getApplication()->getGlobalization()) {
					$charset = $globalization->getCharset();
				}
				$meta = new TMetaTag();
				$meta->setCharset($charset);
				$metatags->add($meta);
			}
		}
	}
}

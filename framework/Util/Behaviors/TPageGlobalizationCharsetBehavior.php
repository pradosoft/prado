<?php

/**
 * TPageGlobalizationCharsetBehavior class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Behaviors;

use Prado\Prado;
use Prado\TPropertyValue;
use Prado\Web\UI\WebControls\TMetaTag;

/**
 * TPageGlobalizationCharsetBehavior class.
 *
 * TPageGlobalizationCharsetBehavior attaches to pages and adds charset
 * meta to the head from globalization.  If there is no globalization,
 * the default charset is used, 'utf-8'.
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.2.0
 */
class TPageGlobalizationCharsetBehavior extends \Prado\Util\TBehavior
{
	/** @var bool check the existing meta tags for the charset before adding it */
	private $_checkMetaCharset = false;

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
	 * @param object $page object raising the event
	 * @param mixed $param the parameter of the raised event
	 */
	public function addCharsetMeta($page, $param)
	{
		if ($this->getEnabled() && ($head = $page->getHead())) {
			$hasCharset = false;
			$metatags = $head->getMetaTags();
			if ($this->_checkMetaCharset) {
				foreach ($metatags as $meta) {
					if (empty($meta->getHttpEquiv()) && empty($meta->getContent()) && empty($meta->getName()) && empty($meta->getScheme()) && !empty($meta->getCharset())) {
						$hasCharset = true;
					}
				}
			}
			if (!$hasCharset) {
				$charset = 'utf-8';
				if ($globalization = Prado::getApplication()->getGlobalization()) {
					$charset = $globalization->getCharset();
				}
				$meta = new TMetaTag();
				$meta->setCharset($charset);
				$metatags->add($meta);
			}
		}
	}

	/**
	 * @return bool checks existing meta tags for no cache
	 */
	public function getCheckMetaCharset()
	{
		return $this->_checkMetaCharset;
	}

	/**
	 * @param bool $value checks existing meta tags for no cache
	 */
	public function setCheckMetaCharset($value)
	{
		$this->_checkMetaCharset = TPropertyValue::ensureBoolean($value);
	}
}

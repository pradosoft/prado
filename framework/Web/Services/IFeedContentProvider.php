<?php
/**
 * TFeedService and TFeed class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @author Knut Urdalen <knut.urdalen@gmail.com>
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\Services
 */

namespace Prado\Web\Services;

/**
 * IFeedContentProvider interface.
 *
 * IFeedContentProvider interface must be implemented by a feed class who
 * provides feed content.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @author Knut Urdalen <knut.urdalen@gmail.com>
 * @package Prado\Web\Services
 * @since 3.1
 */
interface IFeedContentProvider
{
	/**
	 * Initializes the feed content provider.
	 * This method is invoked (before {@link getFeedContent})
	 * when the feed provider is requested by a user.
	 * @param TXmlElement $config configurations specified within the &lt;feed&gt; element
	 * corresponding to this feed provider when configuring {@link TFeedService}.
	 */
	public function init($config);
	/**
	 * @return string feed content in proper XML format
	 */
	public function getFeedContent();
	/**
	 * Sets the content type of the feed content to be sent.
	 * Some examples are:
	 * RSS 1.0 feed: application/rdf+xml
	 * RSS 2.0 feed: application/rss+xml or application/xml or text/xml
	 * ATOM feed: application/atom+xml
	 * @return string the content type for the feed content.
	 * @since 3.1.1
	 */
	public function getContentType();
}

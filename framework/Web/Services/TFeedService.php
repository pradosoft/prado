<?php
/**
 * TFeedService and TFeed class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @author Knut Urdalen <knut.urdalen@gmail.com>
 * @link http://www.pradosoft.com
 * @copyright Copyright &copy; 2006 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package System.Web.Services
 */

/**
 * TFeedService class
 *
 * TFeedService provides to end-users feed content.
 *
 * TFeedService manages a set of {@link TFeed}, each representing specific feed content.
 * The service parameter, referring to the ID of the feed, specifies
 * which feed content to be provided to end-users.
 *
 * To use TFeedService, configure it in application configuration as follows,
 * <code>
 *  <service id="feed" class="System.Services.TFeedService">
 *    <feed id="ch1" class="Path.To.FeedClass1" .../>
 *    <feed id="ch2" class="Path.To.FeedClass2" .../>
 *    <feed id="ch3" class="Path.To.FeedClass3" .../>
 *  </service>
 * </code>
 * where each feed is specified via a &lt;feed&gt; element. Initial property
 * values can be configured in a &lt;feed&gt; element.
 *
 * To retrieve the feed content provided by "ch2", use the URL
 * <code>index.php?feed=ch2</code>
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @author Knut Urdalen <knut.urdalen@gmail.com>
 * @package System.Web.Services
 * @since 3.1
 */
class TFeedService extends TService
{
	private $_feeds=array();

	/**
	 * Initializes this module.
	 * This method is required by the IModule interface.
	 * @param TXmlElement configuration for this module, can be null
	 */
	public function init($config)
	{
		foreach($config->getElementsByTagName('feed') as $feed)
		{
			if(($id=$feed->getAttribute('id'))!==null)
				$this->_feeds[$id]=$feed;
			else
				throw new TConfigurationException('feedservice_id_required');
		}
	}

	/**
	 * @return string the requested feed path
	 */
	protected function determineRequestedFeedPath()
	{
		return $this->getRequest()->getServiceParameter();
	}

	/**
	 * Runs the service.
	 * This method is invoked by application automatically.
	 */
	public function run()
	{
		$id=$this->getRequest()->getServiceParameter();
		if(isset($this->_feeds[$id]))
		{
			$feedConfig=$this->_feeds[$id];
			$properties=$feedConfig->getAttributes();
			if(($class=$properties->remove('class'))!==null)
			{
				$feed=Prado::createComponent($class);
				if($feed instanceof TFeed)
				{
					// init feed properties
					foreach($properties as $name=>$value)
						$feed->setSubproperty($name,$value);
					$feed->init($feedConfig);

					$content=$feed->getFeedContent();
				    //$this->getResponse()->setContentType('application/rss+xml');
				    $this->getResponse()->setContentType('text/xml');
				    $this->getResponse()->write($content);
				}
				else
					throw new TConfigurationException('feedservice_feedtype_invalid',$id);
			}
			else
				throw new TConfigurationException('feedservice_class_required',$id);
		}
		else
			throw new THttpException(404,'feedservice_feed_unknown',$id);
	}
}

/**
 * TFeed class.
 *
 * TFeed is the base class for all feed provider classes.
 *
 * Derived classes should override {@link getFeedContent()} to return
 * an XML string that represents the feed content.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Services
 * @since 3.1
 */
abstract class TFeed extends TApplicationComponent
{
	private $_id='';

	/**
	 * Initializes the feed.
	 * @param TXmlElement configurations specified in {@link TFeedService}.
	 */
	public function init($config)
	{
	}

	/**
	 * @return string ID of this feed
	 */
	public function getID()
	{
		return $this->_id;
	}

	/**
	 * @param string ID of this feed
	 */
	public function setID($value)
	{
		$this->_id=$value;
	}

	/**
	 * @return string an XML string representing the feed content
	 */
	public function getFeedContent()
	{
		return '';
	}
}

?>
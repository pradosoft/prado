<?php
/**
 * TRssFeedDocument, TRssFeedItem, TRssFeedTextInput, TRssFeedCloud class file
 * 
 * @author Knut Urdalen <knut.urdalen@gmail.com>
 * @link http://www.pradosoft.com
 * @copyright Copyright &copy; 2006 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package System.Xml
 */

Prado::using('System.Xml.TFeedDocument');

/**
 * TRssFeedDocument class
 * 
 * TRssFeedDocument represents an RSS feed. RSS is a family of web feed formats, specified in XML and 
 * used for Web syndication. RSS is used by (among other things) news websites, weblogs and 
 * podcasting.
 *
 * @author Knut Urdalen <knut.urdalen@gmail.com>
 * @package System.Xml
 * @since 3.1
 */
class TRssFeedDocument extends TFeedDocument {
  
  private $_rss; // reference to rss node
  private $_channel; // reference to channel node

  /**
   * Constructor
   */
  public function __construct($encoding = null) {
    parent::__construct($encoding);

    $this->formatOutput = true;

    $this->_rss = $this->createElement('rss');
    $this->_rss->setAttribute('version', '0.91');
    $this->appendChild($this->_rss);

    $this->_channel = $this->createElement('channel');
    $this->_rss->appendChild($this->_channel);
  }

  public function init() {
    
  }

  /**
   * @return RSS version
   */
  public function getVersion() {
    return $this->_rss->getAttribute('version');
  }

  /**
   * @param string $version RSS version
   */
  public function setVersion($version) {
    if($version == '0.91' or $version == '0.92' or $version == '2.0') {
      $this->_rss->setAttribute('version', $version);
    } else {
      throw new TInvalidDataTypeException('rssfeed_version_invalid', $version);
    }
  }

  /**
   * @return string The name of the channel.
   */
  public function getTitle() {
    return $this->get('title');
  }

  /**
   * The name of the channel. It's how people refer to your service. If you have an HTML 
   * website that contains the same information as your RSS file, the title of your channel 
   * should be the same as the title of your website.
   *
   * @param string $title The name of the channel.
   */
  public function setTitle($title) {
    $this->set('title', $title);
  }

  /**
   * @return string The URL to the HTML website corresponding to the channel.
   */
  public function getLink() {
    return $this->get('link');
  }

  /**
   * The URL to the HTML website corresponding to the channel.
   *
   * @param string $link The URL to the HTML website corresponding to the channel.
   */
  public function setLink($link) {
    $this->set('link', $link);
  }

  /**
   * @return string Phrase or sentence describing the channel.
   */
  public function getDescription() {
    return $this->get('description');
  }

  /**
   * @param string $description Phrase or sentence describing the channel.
   */
  public function setDescription($description) {
    $this->set('description', $description);
  }

  /**
   * @return string The language the channel is written in.
   */
  public function getLanguage() {
    return $this->get('language');
  }

  /**
   * The language the channel is written in. This allows aggregators to group all Italian 
   * language sites, for example, on a single page. A list of allowable values for this 
   * element, as provided by Netscape, is {@link http://www.rssboard.org/rss-language-codes here}. 
   * You may also use {@link http://www.w3.org/TR/REC-html40/struct/dirlang.html#langcodes values defined} 
   * by the W3C.
   */
  public function setLanguage($language) {
    $this->set('language', $language);
  }

  /**
   * @return string Copyright notice for content in the channel.
   */
  public function getCopyright() {
    return $this->get('copyright');
  }
  
  /**
   * @param string Copyright notice for content in the channel.
   */
  public function setCopyright($copyright) {
    $this->set('copyright', $copyright);
  }

  /**
   * @return string Email address for person responsible for editorial content.
   */
  public function getManagingEditor() {
    return $this->get('managingEditor');
  }
  
  /**
   * @param string Email address for person responsible for editorial content.
   */
  public function setManagingEditor($managingEditor) {
    $this->set('managingEditor', $managingEditor);
  }

  /**
   * @return string Email address for person responsible for technical issues relating to channel.
   */
  public function getWebMaster() {
    return $this->get('webMaster');
  }
  
  /**
   * @param string Email address for person responsible for technical issues relating to channel.
   */
  public function setWebMaster($webMaster) {
    $this->set('webMaster', $webMaster);
  }

  /**
   * @return string The {@link http://www.w3.org/PICS/ PICS} rating for the channel.
   */
  public function getRating() {
    return $this->get('rating');
  }
  
  /**
   * @param string The {@link http://www.w3.org/PICS/ PICS} rating for the channel.
   */
  public function setRating($rating) {
    $this->set('rating', $rating);
  }

  /**
   * @return string Publication date
   */
  public function getPublicationDate() {
    return $this->get('pubDate');
  }

  /**
   * The publication date for the content in the channel. For example, the New York Times 
   * publishes on a daily basis, the publication date flips once every 24 hours. That's when 
   * the pubDate of the channel changes. All date-times in RSS conform to the Date and Time 
   * Specification of {@link http://asg.web.cmu.edu/rfc/rfc822.html RFC 822}, with the exception 
   * that the year may be expressed with two characters or four characters (four preferred).
   *
   * @param string $pubDate Publication date
   */
  public function setPublicationDate($pubDate) {
    $this->set('pubDate', $pubDate);
  }

  /**
   * @return The last time the channel was modified.
   */
  public function getLastBuildDate() {
    return $this->get('lastBuildDate');
  }

  /**
   * @param string $date The last time the channel was modified (RFC 822).
   */
  public function setLastBuildDate($date) {
    return $this->set('lastBuildDate', $date);
  }

  /**
   * @return An URL that references a description of the channel.
   */
  public function getDocumentation() {
    return $this->get('docs');
  }
  
  /**
   * A URL that points to the documentation for the format used in the RSS file. It's probably 
   * a pointer to this page. It's for people who might stumble across an RSS file on a Web 
   * server 25 years from now and wonder what it is.
   * 
   * @param string $documentation An URL that references a description of the channel.
   */
  public function setDocumentation($documentation) {
    $this->set('docs', $documentation);
  }

  /**
   * @return array The days of the week, spelled out in English.
   */
  public function getSkipDays() {
    $skipDays = $this->_channel->getElementsByTagName('skipDays')->item(0);
    $return = array();
    if($skipDays instanceof DOMElement) {
      $days = $skipDays->getElementsByTagName('day');
      foreach($days as $day) {
        $return[] = $day->nodeValue;
      }
    }
    return $return;
  }

  /**
   * @param array $days The days of the week, spelled out in English.
   */
  public function setSkipDays($days) {

    $skipDays = $this->createElement('skipDays');
    foreach($days as $day) { // Append day
      $day = $this->createElement('day', $day);
      $skipDays->appendChild($day);
    }
    
    // Add skipDays
    $node = $this->_channel->getElementsByTagName('skipDays')->item(0);
    if($node instanceof DOMElement) {
      $this->_channel->replaceChild($skipDays, $node);
    } else {
      $this->_channel->appendChild($skipDays);
    }
  }

  /**
   * @return array
   */
  public function getSkipHours() {
    $skipHours = $this->_channel->getElementsByTagName('skipHours')->item(0);
    $return = array();
    if($skipHours instanceof DOMElement) {
      $hours = $skipHours->getElementsByTagName('hour');
      foreach($hours as $hour) {
        $return[] = (int)$hour->nodeValue;
      }
    }
    return $return;    
  }

  /**
   * A list of hour's indicating the hours in the day, GMT, when the channel is unlikely to 
   * be updated. If not set, the channel is assumed to be updated hourly.
   *
   * @param array $hours
   */
  public function setSkipHours($hours) {
    
    $skipHours = $this->createElement('skipHours');
    foreach($hours as $hour) { // Append hour
      $hour = $this->createElement('hour', $hour);
      $skipHours->appendChild($hour);
    }
    
    // Add skipHours
    $node = $this->_channel->getElementsByTagName('skipHours')->item(0);
    if($node instanceof DOMElement) {
      $this->_channel->replaceChild($skipHours, $node);
    } else {
      $this->_channel->appendChild($skipHours);
    }    
  }

  /**
   *
   *
   * @param TRssFeedItem $item 
   */
  public function addItem(TRssFeedItem $item) {

    //if($this->_version == '0.91' and count($this->getItems()) <= 15) {

      $fragment = $this->createDocumentFragment();
      if($fragment->appendXML($item->toString())) {
	$this->_channel->appendChild($fragment);
      } else {
	// TODO
      }
    /*} else {
      throw new TInvalidOperationException('');
    }*/
  }

  /**
   *
   *
   * @param TRssFeedImage $image The feed accompanying icon.
   */
  public function setImage(TRssFeedImage $image) {
    $fragment = $this->createDocumentFragment();
    $fragment->appendXML($image->toString());
    $node = $this->_channel->getElementsByTagName($name)->item(0);
    if($node instanceof DOMElement) {
      $this->_channel->replaceChild($fragment, $node);
    } else {
      $this->_channel->appendChild($fragment);
    }
  }

  /**
   *
   *
   * @param TRssFeedTextInput $textInput A small text box and a Submit button to associate with a CGI application.
   */
  public function setTextInput(TRssFeedTextInput $textInput) {
    $fragment = $this->createDocumentFragment();
    $fragment->appendXML($textInput->toString());
    $node = $this->_channel->getElementsByTagName($name)->item(0);
    if($node instanceof DOMElement) {
      $this->_channel->replaceChild($fragment, $node);
    } else {
      $this->_channel->appendChild($fragment);
    }
  }

  /**
   *
   * @param TRssFeedCloud $cloud
   * @since RSS 0.92
   */
  public function setCloud(TRssFeedCloud $cloud) {
    // TODO: RSS >= 0.92
    $fragment = $this->createDocumentFragment();
    $fragment->appendXML($cloud->toString());
    $node = $this->_channel->getElementsByTagName($name)->item(0);
    if($node instanceof DOMElement) {
      $this->_channel->replaceChild($fragment, $node);
    } else {
      $this->_channel->appendChild($fragment);
    }

  }
  
  /**
   * @return array A list of TRssItem's
   */
  public function getItems() {
    $return = array();
    $elements = $this->getElementsByTagName('item');
    
    $items = new TRssFeedItemList();
    
    /*foreach($items as $item) {
      $return[] = 
    }*/
    return $return;
  }

  /**
   * @return string Category path seperated with forward slash.
   */
  public function getCategory() {
    return $this->get('category');
  }

  /**
   * Specify one or more categories that the channel belongs to.
   *
   * @param string $category Category path seperated with forward slash.
   * @param string $domain Domain name which the category applies to.
   * @since RSS 2.0
   */ 
  public function setCategory($category, $domain) {
    $newNode = $this->createElement('category', $category);
    $newNode->setAttribute('domain', $domain);
    $node = $this->_channel->getElementsByTagName($category)->item(0); 
    if($node instanceof DOMElement) {
      $this->_channel->replaceChild($newNode, $node);
    } else {
      $this->_channel->appendChild($newNode);
    }
  }
  
  /**
   * @return string A string indicating the program used to generate the channel.
   */
  public function getGenerator() {
    return $this->get('generator');
  }

  /**
   * @param string $generator A string indicating the program used to generate the channel.
   * @since RSS 2.0
   */
  public function setGenerator($generator) {
    if($this->getVersion() >= '2.0') {
      $this->set('generator', $generator);
    } else {
      throw new TInvalidDataTypeException('rssfeed_generator_unsupported');
    }
  }

  /**
   * ttl stands for time to live. It's a number of minutes that indicates how long a 
   * channel can be cached before refreshing from the source.
   *
   * @param int Number of minutes that channel can be cached.
   * @since RSS 2.0
   */
  public function setTimeToLive($ttl) {
    
  }

  /**
   * Help getter
   */
  private function get($name) {
    return $this->getElementsByTagName($name)->item(0)->nodeValue;
  }

  /**
   * Helpt setter
   */
  private function set($name, $value) {
    $newNode = $this->createElement($name, $value);
    $node = $this->_channel->getElementsByTagName($name)->item(0); 
    if($node instanceof DOMElement) {
      $this->_channel->replaceChild($newNode, $node);
    } else {
      $this->_channel->appendChild($newNode);
    }
  }

  /**
   *
   * @return string Feed as XML
   */
  public function getFeedContent() {
    return $this->saveXML();
  }
}

/**
 * TRssFeedItem class
 * 
 * An item may represent a "story" -- much like a story in a newspaper or magazine; if so its 
 * description is a synopsis of the story, and the link points to the full story. An item may 
 * also be complete in itself, if so, the description contains the text (entity-encoded HTML 
 * is allowed), and the link and title may be omitted. All elements of an item are optional, 
 * however at least one of title or description must be present. 
 *
 * @author Knut Urdalen <knut.urdalen@gmail.com>
 * @package System.Xml
 * @since 3.1
 */
class TRssFeedItem extends TFeedItem {

  
  public function __construct() {
    parent::__construct('item');
  }

  /**
   * @return string The title of the item.
   */
  public function getTitle() {
    return $this->get('title');
  }

  /**
   * @param string $title The title of the item.
   */
  public function setTitle($title) {    
    $this->set('title', $title);
  }  

  /**
   * @return string The URL of the item.
   */
  public function getLink() {
    return $this->get('link');
  }

  /**
   * @param string $link The URL of the item.
   */
  public function setLink($link) {
    $this->set('link', $link);
  }

  /**
   * @return string The item synopsis.
   */
  public function getDescription() {
    return $this->get('description');
  }

  /**
   * @param string $description The item synopsis.
   */
  public function setDescription($description) {
    $this->set('description', $description);
  }

  
  /**
   * The RSS channel that the item came from. The purpose of this property is to propagate 
   * credit for links, to publicize the sources of news items. It can be used in the Post 
   * command of an aggregator. It should be generated automatically when forwarding an item 
   * from an aggregator to a weblog authoring tool.
   *
   * @param string $source Name of source
   * @param string $url Link back to source
   * @since RSS 0.92
   */
  public function setSource($source, $url) {
    $source = $this->getElementsByTagName('source');
    if($source instanceof TXmlElement) {
      $source->setValue($source);
      $source->setAttribute('url', $url);
    } else {
      $source = new TXmlElement('source');
      $source->setValue($source);
      $source->setAttribute('url', $url);
      $this->getElements()->add($source);
    }
  }

  /**
   * Describes a media object that is attached to the item.
   * 
   * @param string $url Where the enclosure is located.
   * @param int $length Size in bytes.
   * @param string $type  MIME type
   * @since RSS 0.92
   */
  public function setEnclosure($url, $length, $type) {
    $enclosure = $this->getElementsByTagName('enclosure');
    if($enclosure instanceof TXmlElement) {
      $enclosure->setAttribute('url', $url);
      $enclosure->setAttribute('length', $length);
      $enclosure->setAttribute('type', $type);
    } else {
      $enclosure = new TXmlElement('enclosure');
      $enclosure->setAttribute('url', $url);
      $enclosure->setAttribute('length', $length);
      $enclosure->setAttribute('type', $type);
      $this->getElements()->add($enclosure);
    }
  }

  /**
   * Includes the item in one or more categories.
   * 
   * @param string $category A forward-slash-separated string that identifies a hierarchic location in the indicated taxonomy.
   * @param string $domain 
   * @since RSS 0.92
   */
  public function setCategory($category, $domain) {
    $element = $this->getElementsByTagName('category');
    if($element instanceof TXmlElement) {
      $element->setValue($category);
      $element->setAttribute('domain', $domain);
    } else {
      $element = new TXmlElement('category');
      $element->setValue($category);
      $element->setAttribute('domain', $domain);
      $this->getElements()->insertAt(count($this->getElements()), $element);
    }
  }

  /**
   * @param string $comments URL of a page for comments relating to the item.
   * @since RSS 2.0
   */
  public function setComments($comments) {
    $this->set('comments', $comments);
  }

  /**
   * @param string $author Email address of the author of the item.
   * @since RSS 2.0
   */
  public function setAuthor($author) {
    $this->set('author', $author);
  }

  /**
   * @param string $pubDate Indicates when the item was published.
   * @since RSS 2.0
   */
  public function setPublicationDate($pubDate) {
    $this->set('pubDate', $pubDate);
  }

  /**
   * guid stands for globally unique identifier. It's a string that uniquely identifies the 
   * item. When present, an aggregator may choose to use this string to determine if an item 
   * is new.
   *
   * @param string $guid A string that uniquely identifies the item.
   * @param bool $isPermaLink If its value is false, the guid may not be assumed to be a url, or a url to anything in particular.
   * @since RSS 2.0
   */
  public function setGuid($guid, $isPermaLink = true) {
    
  }
}

/**
 * TRssFeedItemList class
 *
 * @author Knut Urdalen <knut.urdalen@gmail.com>
 * @package System.Xml
 * @since 3.1
 */
class TRssFeedItemList extends TList {
  

  /*public function insertAt($index, TRssFeedItem $item) {
    
  }

  public function removeAt($index) {
    
  }*/

}

/**
 * TRssFeedImage class
 *
 * Specifies a GIF, JPEG or PNG image that can be displayed with the channel.
 *
 * @author Knut Urdalen <knut.urdalen@gmail.com>
 * @package System.Xml
 * @since 3.1
 */
class TRssFeedImage extends TFeedElement {

  public function __construct() {
    parent::__construct('image');
  }

  /**
   * @return string Title of the image.
   */
  public function getTitle() {
    return $this->get('title');
  }

  /**
   * Title describes the image, it's used in the ALT attribute of the HTML <img> tag when the 
   * channel is rendered in HTML.
   *
   * @param string $title Title of the image.
   */
  public function setTitle($title) {
    $this->set('title', $title);
  }

  /**
   * @return string URL of a GIF, JPEG or PNG image that represents the channel.
   */
  public function getUrl() {
    return $this->get('url');
  }

  /**
   * @param string $url URL of a GIF, JPEG or PNG image that represents the channel.
   */
  public function setUrl($url) {
    $this->set('url', $url);
  }

  /**
   * @return string The URL that a user is expected to click on.
   */
  public function getLink() {
    return $this->get('link');
  }

  /**
   * The URL that a user is expected to click on, as opposed to a {@link TRssFeedImage::setUrl} 
   * that is for loading a resource, such as an image.
   *
   * The link must start with either "http://" or "ftp://". All other urls are considered 
   * invalid.
   * 
   * @param string $link The URL that a user is expected to click on.
   */
  public function setLink($link) {
    if(substr($link, 0, 7) == 'http://' or substr($link, 0, 6) == 'ftp://') {
      $this->set('link', $link);
    } else {
      throw new TInvalidDataValueException('rssfeedimage_link_invalid');
    }
  }

  /**
   * @return int Width of the image in pixels.
   */
  public function getWidth() {
    return $this->get('width');
  }

  /**
   * The value must be between 1 and 144. If ommitted, the default value is 88.
   * 
   * @param int Width of the image in pixels.
   */
  public function setWidth($width) {
    if($width >= 1 and $width <= 144) {
      $this->set('width', $width);
    } else {
      throw new TInvalidDataValueException('rssfeedimage_width_invalid', 1, 144);
    }
  }

  /**
   * @return int Height of the image in pixels.
   */
  public function getHeight() {
    return $this->get('height');
  }

  /**
   * The value must be between 1 and 400. If ommitted, the default value is 31.
   *
   * @param int $height Height of the image in pixels.
   */
  public function setHeight($height) {
    if($height >= 1 and $height <= 400) {
      $this->set('height', $height);
    } else {
      throw new TInvalidDataValueException('rssfeedimage_height_invalid', 1, 400);
    }
  }
  
  /**
   * @return string A plain text description of the image.
   */
  public function getDescription() {
    return $this->get('description');
  }

  /**
   * @param string $description A plain text description of the image.
   */
  public function setDescription($description) {
    $this->set('description', $description);
  }

}

/**
 * TRssFeedTextInput class
 *
 * The purpose of the textinput element is something of a mystery. You can use it to specify 
 * a search engine box. Or to allow a reader to provide feedback. Most aggregators ignore it.
 *
 * @author Knut Urdalen <knut.urdalen@gmail.com>
 * @package System.Xml
 * @since 3.1 
 */
class TRssFeedTextInput extends TFeedElement {
  
  public function __construct() {
    parent::__construct('textinput');
  }
  
  /**
   * @return string The label of the Submit button in the text input area.
   */
  public function getTitle() {
    return $this->get('title');
  }
  
  /**
   * @param string $title The label of the Submit button in the text input area.
   */
  public function setTitle($title) {
    $this->set('title', $title);
  }

  /**
   * @return string Explains the text input area.
   */
  public function getDescription() {
    return $this->get('description');
  }

  /**
   * @param string $description Explains the text input area.
   */
  public function setDescription($description) {
    $this->set('description', $description);
  }

  /**
   * @return string The name of the text object in the text input area.
   */
  public function getName() {
    return $this->get('name');
  }

  /**
   * @param string $name The name of the text object in the text input area.
   */
  public function setName($name) {
    $this->set('name', $name);
  }

  /**
   * @return string The URL of the script that processes text input requests.
   */
  public function getLink() {
    return $this->get('link');
  }

  /**
   * @param string $link The URL of the script that processes text input requests.
   */
  public function setLink($link) {
    $this->set('link', $link);
  }
}


/**
 * TRssFeedCloud class
 *
 * It specifies a web service that supports the rssCloud interface which can be 
 * implemented in HTTP-POST, XML-RPC or SOAP 1.1.
 *
 * Its purpose is to allow processes to register with a cloud to be notified of 
 * updates to the channel, implementing a lightweight publish-subscribe protocol 
 * for RSS feeds.
 *
 * @author Knut Urdalen <knut.urdalen@gmail.com>
 * @package System.Xml
 * @since 3.1
 * @link http://www.rssboard.org/rsscloud-interface RssCloud API
 */
class TRssFeedCloud extends TFeedElement {

  const PROTOCOL_HTTP_POST = 'HTTP-POST';
  const PROTOCOL_XML_RPC = 'XML-RPC';
  const PROTOCOL_SOAP = 'SOAP';

  public function __construct() {
    parent::__construct('cloud');
  }

  public function getDomain() {
    return $this->get('domain');
  }

  public function setDomain($domain) {
    $this->set('domain', $domain);
  }

  public function getPort() {
    return $this->get('port');
  }
  
  public function setPort($port) {
    $this->set('port', $port);
  }

  public function getPath() {
    return $this->get('path');
  }

  public function setPath($path) {
    $this->set('path', $path);
  }

  public function getRegisterProcedure() {
    return $this->get('registerProcedure');
  }

  public function setRegisterProcedure($registerProcedure) {
    $this->set('registerProcedure', $registerProcedure);
  }

  public function getProtocol() {
    return $this->get('protocol');
  }

  public function setProtocol($protocol) {
    if(strcasecmp($protocol, self::PROTOCOL_HTTP_POST) or
       strcasecmp($protocol, self::PROTOCOL_XML_RPC) or
       strcasecmp($protocol, self::PROTOCOL_SOAP)) {
      $this->set('protocol', $protocol);
    } else {
      throw new TInvalidDataTypeException('rssfeedcloud_protocol_invalid', $protocol);
    }
  }

  protected function get($name) {
    return $this->getAttribute($name)->nodeValue;
  }

  protected function set($name, $value) {
    $this->setAttribute($name, $value);
  }

}

?>
<?php
/**
 * TFeedDocument class file
 * 
 * @author Knut Urdalen <knut.urdalen@gmail.com>
 * @link http://www.pradosoft.com
 * @copyright Copyright &copy; 2006 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package System.Xml
 */

Prado::using('System.Web.Services.IFeedContentProvider');

/**
 * TFeedDocument class
 * 
 * TFeedDocument represents a Web feed used for Web syndication.
 *
 * @author Knut Urdalen <knut.urdalen@gmail.com>
 * @package System.Xml
 * @since 3.1
 */
abstract class TFeedDocument extends DOMDocument implements IFeedContentProvider {
  
  /**
   * 
   */
  public function __construct($encoding = null) {
    parent::__construct('1.0', $encoding);
  }

  /**
   *
   */
  public function getEncoding() {
    return $this->encoding;
  }

  /**
   *
   */
  public function setEncoding($encoding) {
    $this->encoding = $encoding;
  }
}

/**
 * TFeedElement class
 * 
 * @author Knut Urdalen <knut.urdalen@gmail.com>
 * @package System.Xml
 * @since 3.1
 */
abstract class TFeedElement extends TXmlElement {
  
  /**
   *
   */
  /*  public function getValue($name) {
    $element = $this->getElementByTagName($name);
    if($element instanceof TXmlElement) {
      return $element->getValue();
    }
    throw new Exception("Element '$name' not found");
  }*/

  /**
   *
   */
  /*public function setValue($name, $value) {

    if(($element = $this->getElementByTagName($name)) !== null) {
      $element->setValue($value);
    } else {
      $element = new TXmlElement($name);
      $element->setValue($value);
      $this->getElements()->add($element);
    }
  }*/
}

/**
 * TFeedItem class
 * 
 * @author Knut Urdalen <knut.urdalen@gmail.com>
 * @package System.Xml
 * @since 3.1
 */
abstract class TFeedItem extends TFeedElement {
   
}

?>
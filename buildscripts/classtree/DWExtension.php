<?php

/**
 * PradoVTMDocument class
 *
 * @author Stanislav Yordanov <stanprog[at]stanprog.com>
 * @author Qiang Xue <qiang.xue@gmail.com>
 */
class PradoVTMDocument
{
	private $_document;
	private $_attributes;

	public function __construct($controlName)
	{
		$this->_document = new DOMDocument('1.0', 'utf-8');
		$this->prepareDocument($controlName);
	}

	protected function prepareDocument($controlName)
	{
		$this->_document->formatOutput = true;

		//--- add <tag>
		$tag = $this->_document->createElement('tag');
		$tag->setAttribute('name',$controlName);
		$tag->setAttribute('casesensitive','yes');
		$this->_document->appendChild($tag);

		//--- add <tagformat>
		$tagFormat = $this->_document->createElement('tagformat');
		$tagFormat->setAttribute('nlbeforetag','1');
		$tagFormat->setAttribute('nlaftertag','1');
		$tagFormat->setAttribute('indentcontents','yes');
		$tag->appendChild($tagFormat);

		//--- add <tagdialog file="Control.htm" />
		//$tagDialog = $this->_document->createElement('tagdialog');
		//$tagDialog->setAttribute('file',$controlName.'.htm');
		//$tag->appendChild($tagDialog);

		$this->_attributes = $this->_document->createElement('attributes');
		$tag->appendChild($this->_attributes);
	}

	public function getDocument()
	{
		return $this->_document;
	}

	public function addAttribute($attribName, $attribType)
	{
		//--- add <attrib>
		$attrib = $this->_document->createElement('attrib');
		$attrib->setAttribute('name',$attribName);
		if (is_array($attribType))
		{
			$attrib->setAttribute('type','Enumerated');
			foreach ($attribType as $value)
			{
				$option = $this->_document->createElement('attriboption');
				$option->setAttribute('value',$value);
				$option->setAttribute('caption','');
				$attrib->appendChild($option);
			}
		}
		else if($attribType!=='')
		{
			$attrib->setAttribute('type',$attribType);
		}
		$attrib->setAttribute('casesensitive','yes');
		$this->_attributes->appendChild($attrib);
	}

	public function addEvent($eventName)
	{
		//--- add <attrib>
		$this->addAttribute($eventName,'');
		//--- add <event>
		$event = $this->_document->createElement('event');
		$event->setAttribute('name',$eventName);
		$this->_attributes->appendChild($event);
	}

	public function getXML()
	{
		return $this->_document->saveXML();
	}
}

/**
 * PradoMXIDocument class
 *
 * @author Stanislav Yordanov <stanprog@stanprog.com>
 * @author Qiang Xue <qiang.xue@gmail.com>
 */
class PradoMXIDocument
{
	private $_tagLibraryElement;
	private $_filesElement;
	private $_document;

	public function __construct($version)
	{
		$this->_document = new DOMDocument('1.0', 'utf-8');
		$this->prepareDocument($version);
	}

	protected function prepareDocument($version)
	{
		$this->_document->formatOutput = true;
		//--- add root element
		$rootElement = $this->_document->createElement('macromedia-extension');
		$rootElement->setAttribute('name','PRADO Taglib');
		$rootElement->setAttribute('version',$version);
		$rootElement->setAttribute('type','Suite');
		$rootElement->setAttribute('requires-restart','true');
		$this->_document->appendChild($rootElement);
		//--- add <author>
		$element = $this->_document->createElement('author');
		$element->setAttribute('name','Stanislav Yordanov, Qiang Xue');
		$rootElement->appendChild($element);
		$time = date('F j, Y, h:i:s a',time());
		//--- add <description>
		$description = <<<EOD
PRADO $version Tag Library
Authors: Stanislav Yordanov <stanprog@stanprog.com> and Qiang Xue <qiang.xue@gmail.com>
Time: $time
Requirement: Macromedia Dreamweaver MX/MX 2004/8.0 or above
Description: This suite adds PRADO tag library. The tag library contains PRADO component
tags, properties and events that are commonly used on PRADO templates.
EOD;
		$element = $this->_document->createElement('description');
		$element->appendChild($this->_document->createCDATASection($description));
		$rootElement->appendChild($element);
		//--- add <products>
		$productsElement = $this->_document->createElement('products');
		$rootElement->appendChild($productsElement);
		//--- add <product>
		$product = $this->_document->createElement('product');
		$product->setAttribute('name','Dreamweaver');
		$product->setAttribute('version','6');
		$product->setAttribute('primary','false');
		$productsElement->appendChild($product);
		//--- add <ui-access>
		$element = $this->_document->createElement('ui-access');
		$element->appendChild($this->_document->createCDATASection("PRADO"));
		$rootElement->appendChild($element);
		//--- add <files>
		$this->_filesElement = $this->_document->createElement('files');
		$rootElement->appendChild($this->_filesElement);
		//--- add <configuration-changes>
		$configChangeElement = $this->_document->createElement('configuration-changes');
		$rootElement->appendChild($configChangeElement);
		//--- add <taglibrary-changes>
		$tagLibChangeElement = $this->_document->createElement('taglibrary-changes');
		$configChangeElement->appendChild($tagLibChangeElement);
		//--- add <taglibrary-insert>
		$tagLibInsertElement = $this->_document->createElement('taglibrary-insert');
		$tagLibChangeElement->appendChild($tagLibInsertElement);
		//--- add <taglibrary>
		$this->_tagLibraryElement = $element = $this->_document->createElement('taglibrary');
		$element->setAttribute('doctypes','HTML,DWTemplate');
		$element->setAttribute('id','DWTagLibrary_PRADO_tags');
		$element->setAttribute('name','PRADO tags');
		$element->setAttribute('prefix','<com:');
		$element->setAttribute('tagchooser','PRADO/TagChooser.xml');
		$tagLibInsertElement->appendChild($element);

		$element = $this->_document->createElement('file');
		$element->setAttribute('name','Configuration/TagLibraries/PRADO/TagChooser.xml');
		$element->setAttribute('destination','$dreamweaver/Configuration/TagLibraries/PRADO/TagChooser.xml');
		$this->_filesElement->appendChild($element);
	}

	public function addTag($tagName)
	{
		$element = $this->_document->createElement('file');
		$element->setAttribute('name','Configuration/TagLibraries/PRADO/'.$tagName.'.vtm');
		$element->setAttribute('destination','$dreamweaver/Configuration/TagLibraries/PRADO/'.$tagName.'.vtm');
		$this->_filesElement->appendChild($element);

		$element = $this->_document->createElement('tagref');
		$element->setAttribute('file','PRADO/'.$tagName.'.vtm');
		$element->setAttribute('name',$tagName);
		$this->_tagLibraryElement->appendChild($element);
	}

	public function getDocument()
	{
		return $this->_document;
	}

	public function getXML()
	{
		return $this->_document->saveXML();
	}
}

/**
 * PradoTagChooser class
 *
 * @author Stanislav Yordanov <stanprog[at]stanprog.com>
 * @author Qiang Xue <qiang.xue@gmail.com>
 */
class PradoTagChooser
{
	private $_document;
	private $_tclibrary;
	private $_category;

	public function __construct()
	{
		$this->_document = new DOMDocument('1.0', 'utf-8');
		$this->prepareDocument();
	}

	protected function prepareDocument()
	{
		$this->_document->standalone = true;
		$this->_document->formatOutput = true;
		$tclibrary = $this->_document->createElement('tclibrary');
		$tclibrary->setAttribute('name','PRADO tags');
		$tclibrary->setAttribute('desc','A collection of all PRADO tags.');
		$tclibrary->setAttribute('reference','PRADO');
		$this->_document->appendChild($tclibrary);

		$this->_category = $this->_document->createElement('category');
		$this->_category->setAttribute('name','General');
		$this->_category->setAttribute('icon','Configuration/TagLibraries/Icons/Elements.gif');
		$tclibrary->appendChild($this->_category);
	}

	public function addElement($elementName)
	{
		$element = $this->_document->createElement('element');
		$element->setAttribute('name','com:'.$elementName);
		$element->setAttribute('value','<com:'.$elementName.'>');
		$element->setAttribute('reference','PRADO,COM:'.strtoupper($elementName));
		$this->_category->appendChild($element);
	}

	public function getXML()
	{
		$this->_document->normalize();
		/*
		$resultXML = $this->_document->saveXML();
		$resultXML = str_replace('&gt;','>',$resultXML);
		$resultXML = str_replace('&lt;','<',$resultXML);
		return $resultXML;
		*/
		return $this->_document->saveXML();
	}
}
?>
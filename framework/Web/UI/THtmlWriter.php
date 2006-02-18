<?php
/**
 * THtmlWriter class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $Date: $
 * @package System.Web.UI
 */

// todo: test if an attribute is a url
// keep nonclosing tag only
// add more utility methods (e.g. render....)
// implment encoding (for text and url)
/**
 * THtmlWriter class
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI
 * @since 3.0
 */
class THtmlWriter extends TApplicationComponent implements ITextWriter
{
	const TAG_INLINE=0;
	const TAG_NONCLOSING=1;
	const TAG_OTHER=2;
	const CHAR_NEWLINE="\n";
	const CHAR_TAB="\t";
	private static $_tagTypes=array(
		'*'=>2,
		'a'=>0,
		'acronym'=>0,
		'address'=>2,
		'area'=>1,
		'b'=>0,
		'base'=>1,
		'basefont'=>1,
		'bdo'=>0,
		'bgsound'=>1,
		'big'=>0,
		'blockquote'=>2,
		'body'=>2,
		'br'=>2,
		'button'=>0,
		'caption'=>2,
		'center'=>2,
		'cite'=>0,
		'code'=>0,
		'col'=>1,
		'colgroup'=>2,
		'del'=>0,
		'dd'=>0,
		'dfn'=>0,
		'dir'=>2,
		'div'=>2,
		'dl'=>2,
		'dt'=>0,
		'em'=>0,
		'embed'=>1,
		'fieldset'=>2,
		'font'=>0,
		'form'=>2,
		'frame'=>1,
		'frameset'=>2,
		'h1'=>2,
		'h2'=>2,
		'h3'=>2,
		'h4'=>2,
		'h5'=>2,
		'h6'=>2,
		'head'=>2,
		'hr'=>1,
		'html'=>2,
		'i'=>0,
		'iframe'=>2,
		'img'=>1,
		'input'=>1,
		'ins'=>0,
		'isindex'=>1,
		'kbd'=>0,
		'label'=>0,
		'legend'=>2,
		'li'=>0,
		'link'=>1,
		'map'=>2,
		'marquee'=>2,
		'menu'=>2,
		'meta'=>1,
		'nobr'=>0,
		'noframes'=>2,
		'noscript'=>2,
		'object'=>2,
		'ol'=>2,
		'option'=>2,
		'p'=>0,
		'param'=>2,
		'pre'=>2,
		'ruby'=>2,
		'rt'=>2,
		'q'=>0,
		's'=>0,
		'samp'=>0,
		'script'=>2,
		'select'=>2,
		'small'=>2,
		'span'=>0,
		'strike'=>0,
		'strong'=>0,
		'style'=>2,
		'sub'=>0,
		'sup'=>0,
		'table'=>2,
		'tbody'=>2,
		'td'=>0,
		'textarea'=>0,
		'tfoot'=>2,
		'th'=>0,
		'thead'=>2,
		'title'=>2,
		'tr'=>2,
		'tt'=>0,
		'u'=>0,
		'ul'=>2,
		'var'=>0,
		'wbr'=>1,
		'xml'=>2
	);
	private static $_attrEncode=array(
		'abbr'=>true,
		'accesskey'=>true,
		'alt'=>true,
		'axis'=>true,
		'background'=>true,
		'class'=>true,
		'content'=>true,
		'headers'=>true,
		'href'=>true,
		'longdesc'=>true,
		'onclick'=>true,
		'onchange'=>true,
		'src'=>true,
		'title'=>true,
		'value'=>true
	);
	private static $_styleEncode=array(
		'background-image'=>true,
		'list-style-image'=>true
	);
	private $_attributes=array();
	private $_openTags=array();
	private $_writer=null;
	private $_styles=array();

	public function __construct($writer)
	{
		$this->_writer=$writer;
	}

	public function isValidFormAttribute($name)
	{
		return true;
	}

	public function addAttributes($attrs)
	{
		foreach($attrs as $name=>$value)
			$this->_attributes[$name]=isset(self::$_attrEncode[$name])?THttpUtility::htmlEncode($value):$value;
	}

	public function addAttribute($name,$value)
	{
		$this->_attributes[$name]=isset(self::$_attrEncode[$name])?THttpUtility::htmlEncode($value):$value;
	}

	public function removeAttribute($name)
	{
		if(isset($this->_attributes[$name]))
			unset($this->_attributes[$name]);
	}
	
	public function addStyleAttribute($name,$value)
	{
		$this->_styles[$name]=isset(self::$_styleEncode[$name])?THttpUtility::htmlEncode($value):$value;
	}

	public function removeStyleAttribute($name)
	{
		if(isset($this->_styles[$name]))
			unset($this->_styles[$name]);
	}

	public function flush()
	{
		$this->_writer->flush();
	}

	public function write($str)
	{
		$this->_writer->write($str);
	}

	public function writeLine($str='')
	{
		$this->_writer->write($str.self::CHAR_NEWLINE);
	}

	public function writeBreak()
	{
		$this->_writer->write('<br/>');
	}

	public function writeAttribute($name,$value,$encode=false)
	{
		$this->_writer->write(' '.$name.='"'.($encode?THttpUtility::htmlEncode($value):$value).'"');
	}

	public function renderBeginTag($tagName)
	{
		$tagType=isset(self::$_tagTypes[$tagName])?self::$_tagTypes[$tagName]:self::TAG_OTHER;
		$str='<'.$tagName;
		foreach($this->_attributes as $name=>$value)
			$str.=' '.$name.'="'.$value.'"';
		if(!empty($this->_styles))
		{
			$str.=' style="';
			foreach($this->_styles as $name=>$value)
				$str.=$name.':'.$value.';';
			$str.='"';
		}
		if($tagType===self::TAG_NONCLOSING)
		{
			$str.=' />';
			array_push($this->_openTags,'');
		}
		else
		{
			$str.='>';
			array_push($this->_openTags,$tagName);
		}
		$this->_writer->write($str);
		$this->_attributes=array();
		$this->_styles=array();
	}

	public function renderEndTag()
	{
		if(!empty($this->_openTags) && ($tagName=array_pop($this->_openTags))!=='')
			$this->_writer->write('</'.$tagName.'>');
	}
}

?>
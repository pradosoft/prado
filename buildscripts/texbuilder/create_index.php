<?php

// Create quickstart search index
$zend_path = realpath(dirname(__FILE__).'/../../demos/quickstart/protected/index');
set_include_path(get_include_path().';'.$zend_path);
require_once ('Zend/Search/Lucene.php');


class quickstart_index
{
	private $_index;
	private $_dir;
	
	public function __construct($index_file)
	{
		$this->_index = new Zend_Search_Lucene($index_file, true);
		$this->_dir = $index_file;
		echo "Building search index...\n";
	}
	
	public function add($content, $section, $mtime)
	{
		foreach($this->split_headings($content) as $headers)
		{
			$doc = new Zend_Search_Lucene_Document();
			$link = "index.php?page=".preg_replace('/\/|\\\/', '.', $section);
			$link = str_replace('.page', '', $link).'#'.$headers['section'];
			
			//unsearchable text
			$doc->addField(Zend_Search_Lucene_Field::UnIndexed('link', $link));
			$doc->addField(Zend_Search_Lucene_Field::UnIndexed('mtime', $mtime));
			$doc->addField(Zend_Search_Lucene_Field::UnIndexed('title', $headers['title']));
			$doc->addField(Zend_Search_Lucene_Field::UnIndexed('text', $headers['content']));		
			
			//searchable text
			$doc->addField(Zend_Search_Lucene_Field::Keyword('page', strtolower($headers['title'])));
			$body = strtolower($this->sanitize($headers['content'])).' '.strtolower($headers['title']);
			$doc->addField(Zend_Search_Lucene_Field::Unstored('contents',$body));
			$this->_index->addDocument($doc);
		}		
	}
	
	function sanitize($input) 
	{
		return htmlentities(strip_tags( $input ));
	}	
	
	public function index()
	{
		return $this->_index;
	}
	
	protected function split_headings($html)
	{
		$html = preg_replace('/<\/?com:TContent[^<]*>/', '', $html);
		
		$html = preg_replace('/<b>([^<]*)<\/b>/', '$1', $html);
		$html = preg_replace('/<i>([^<]*)<\/i>/', '$1', $html);
		$html = preg_replace('/<tt>([^<]*)<\/tt>/', '$1', $html);
		
		$html = preg_replace('/<h1([^>]*)>([^<]*)<\/h1>/', '<hh$1>$2</hh>', $html);
		$html = preg_replace('/<h2([^>]*)>([^<]*)<\/h2>/', '<hh$1>$2</hh>', $html);
		$html = preg_replace('/<h3([^>]*)>([^<]*)<\/h3>/', '<hh$1>$2</hh>', $html);
		
		
		$sections = preg_split('/<hh[^>]*>([^<]+)<\/hh>/', $html,-1);
		$headers = array();
		preg_match_all('/<hh([^>]*)>([^<]+)<\/hh>/', $html, $headers);
		$contents = array();
		for($i = 1, $t = count($sections); $i < $t; $i++)
		{
			$content['title'] = trim($this->sanitize($headers[2][$i-1]));
			$sec = array();
			preg_match('/"([^"]*)"/', $headers[1][$i-1], $sec);
			$content['section'] = str_replace('"', '',$sec[0]);
			$content['content'] = trim($this->sanitize($sections[$i]));
			$contents[] = $content;
		}

		return $contents;
	}
	
	public function commit()
	{
		$this->_index->commit();		
		$count = $this->_index->count();
		echo "\nSaving search index ({$count}) to {$this->_dir}\n\n";
	}
}
?>
<?php

class ChmQuickstartBuilder
{
	private $base;
	const DEMO_URL = 'http://www.pradosoft.com/demos/quickstart/';
	const CSS_URL = 'assets/chm_style.css';
	private $output_dir;
	private $app;

	private $_viewed=array();

	public function __construct($base,$output)
	{
		$this->base = $base;
		$this->output_dir = $output.'/quickstart';

		if(!is_dir($this->output_dir))
		{
			mkdir($this->output_dir);
			mkdir($this->output_dir.'/assets/');
			copy(dirname(__FILE__).'/chm_style.css', $this->output_dir.'/assets/chm_style.css');
		}

		Prado::setPathOfAlias('Output', realpath($this->output_dir));
	}

	public function buildDoc($pages)
	{
		foreach($pages as $section)
		{
			foreach($section as $page)
			{
				$this->parsePage($page);
			}
		}
	}

	protected function initApp()
	{
		$this->app = new TApplication($this->base);
		$response = new THttpResponse();
		$response->setBufferOutput(false);
		$this->app->setResponse($response);
		$assets = new TAssetManager();
		$assets->setBasePath('Output.assets.*');
		$this->app->setAssetManager($assets);
	}

	public function parsePage($page)
	{
		$_GET['page'] = str_replace(array('/','.page'),array('.',''),$page);
		$_GET['notheme'] = 'true';

		$html = $this->parseHtmlContent($this->getApplicationContent());
		$file = str_replace(array('/','.page'), array('_','.html'),$page);
//		echo 'writing file '.$file."\n";
		file_put_contents($this->output_dir.'/'.$file, $html);
	}

	protected function getApplicationContent()
	{
		ob_start();
		$this->initApp();
		$this->app->run();
		$content = ob_get_contents();
		ob_end_clean();
		return $content;
	}

	protected function parseHtmlContent($content)
	{
		$html = preg_replace('/<input.*name="PRADO_PAGESTATE" [^>]+\/>/m', '', $content);
$html = str_replace('<div id="header">
<div class="title">Prado QuickStart Tutorial</div>
<div class="image"></div>
</div>', '', $html);
$html = preg_replace('/<div id="footer">.*?<\/div>/ms', '<div id="footer">
Copyright &copy; 2005-2007 <a href="http://www.pradosoft.com">PradoSoft</a>.</div>', $html);


		$html = str_replace('</head>', '<link rel="stylesheet" type="text/css" href="'.self::CSS_URL.'" /></head>', $html);

		$html = preg_replace_callback('/(?<!RunButton" )href=".*\?page=([a-zA-Z0-9\.#]+)"/',
			array($this, 'update_page_url'), $html);
		$html = preg_replace_callback('/(?<=RunButton" )href=".*\?page=([a-zA-Z0-9\.#]+)"/',
			array($this, 'update_run_url'), $html);

		$html = preg_replace('/(src|href)=("?)\//', '$1=$2assets/',$html);
		$html = str_replace('http://www.pradosoft.com/docs/manual', '../manual/CHMdefaultConverter', $html);
		$html = str_replace('target="_blank">View Source', '>View Source', $html);
		$html = preg_replace_callback('/href="\?page=ViewSource&(amp;){0,1}path=([a-zA-z0-9\.\/]+)"/',
			array($this, 'update_source_url'), $html);

		return $html;
	}

	protected function update_source_url($matches)
	{
		$page = $matches[2];
		$file = str_replace('/', '_',$page).'.html';

		if(!isset($this->_viewed[$page]))
		{
			$this->_viewed[$page]=true;
			$this->view_source_page($page);
		}
		return 'href="'.$file.'"';
	}

	protected function view_source_page($page)
	{
		$_GET['page'] = 'ViewSource';
		$_GET['path'] = $page;
		$_GET['lines'] = 'false';

		$html = $this->parseHtmlContent($this->getApplicationContent());
		$file = str_replace('/', '_',$page).'.html';
//		echo 'writing file '.$file."\n";
		file_put_contents($this->output_dir.'/'.$file, $html);
	}

	protected function update_page_url($matches)
	{
		$bits = explode('#',str_replace('.','_',$matches[1]));
		$anchor = isset($bits[1]) ? '#'.$bits[1] : '';
		return 'href="'.$bits[0].'.html'.$anchor.'"';
	}

	protected function update_run_url($matches)
	{
		return 'href="'.self::DEMO_URL.'?page='.$matches[1].'"';
	}
}

class HTMLHelpTOCBuilder
{

	public function buildToc($file,$output,$classes)
	{
		$contents = file_get_contents($file);
		$content = $this->prepareContent($contents);
		$ul = $this->parseUl($content);
		$toc = $this->header();
		$toc .= $this->to_string($ul);
		$toc .= $this->footer();
		$toc = $this->appendApiToc($output,$toc);
		$toc = $this->appendClassesToc($classes,$toc);
		file_put_contents($output.'/toc.hhc', $toc);
		file_put_contents($output.'/prado3_manual.hhp', $this->getHHP());
		file_put_contents($output.'/manual.html', $this->getIndexPage());
		$index = $output.'/manual/CHMdefaultConverter/index.hhk';
		file_put_contents($index, $this->updateIndex($index));
	}

	protected function updateIndex($file)
	{
		$content = file_get_contents($file);
		return preg_replace('/"Local" value="/', '"Local" value="manual\\CHMdefaultConverter\\', $content);
	}

	protected function appendClassesToc($classes, $toc)
	{
		$version = Prado::getVersion();
		$ul['classes']['params'][] = array('Name' => "Prado {$version} Class Index");
		foreach($classes as $class)
		{
			$ul['classes']['ul'][0]['params'][] =
				array('Name'=>$class, 'Local'=>'classdoc/'.$class.'.html');
		}
		$ul['wiki']['params'][] = array('Name' => "Prado Wiki", 'Local'=>'wiki\\index.html');
		$content = $this->to_string($ul);
		$toc = preg_replace('!(</BODY></HTML>)!', $content.'$1', $toc);
		return $toc;
	}

	protected function appendApiToc($output,$toc)
	{
		$content = file_get_contents($output.'/manual/CHMdefaultConverter/contents.hhc');
		$content = preg_replace('/"Local" value="/', '"Local" value="manual\\CHMdefaultConverter\\', $content);
		$toc = preg_replace('!(API Manual">\s*</OBJECT>)\s*(</UL>\s*</BODY></HTML>)!', '$1'."\n".$content.'$2', $toc);
		return preg_replace("/\r/","\n",$toc);
	}

	protected function getIndexPage()
	{
		$version = Prado::getVersion();
		$date = date('d M Y', time());
		$year = date('Y',time());
$content = <<<EOD
<!doctype html public "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<title>Prado Manual</title>
	<style type="text/css">
	/*<![CDATA[*/
	body
	{
	font-family: 'Lucida Grande', Verdana, Geneva, Lucida, Helvetica, Arial, sans-serif;
	font-weight:normal;
	}
	h1
	{
		color: #600;
	}
	/*]]>*/
	</style>
</head>

<body style="text-align:center">

<h1>Prado {$version} Manual</h1>

<div>Created On: {$date}</div>

<div style="margin-top:3em;margin-bottom:0.75em"><strong>Written By:</strong> Qiang Xue, Wei Zhuo</div>
<div style="margin-bottom:3em;"><strong>Edited By:</strong> Wei Zhuo</div>

<div id="footer">
Copyright &copy; 2005-{$year} <a href="http://www.pradosoft.com">PradoSoft</a>.</div>

</body>
</html>
EOD;
		return $content;
	}

	protected function getHHP()
	{
		$version = Prado::getVersion();
$content = <<<EOD
[OPTIONS]
Binary TOC=Yes
Compatibility=1.1 or later
Compiled File=prado3_manual.chm
Contents File=toc.hhc
Default Window=main
Default Topic=manual.html
Display compile progress=Yes
Error log file=_errorlog.txt
Full-text search=Yes
Language=0x409 English (United States)
Title=Prado {$version} Manual
Binary Index=Yes
Index file=manual\CHMdefaultConverter\index.hhk
Default Font=
Auto Index=Yes
Create CHI file=No
Full text search stop list file=
Display compile notes=Yes

[WINDOWS]
main="Prado {$version} Manual","toc.hhc","manual\CHMdefaultConverter\index.hhk","manual.html","manual.html",,,,,0x63520,250,0x104e,[10,10,900,700],0xb0000,,,,,,0

EOD;
		return $content;
	}

	protected function parseUl($content)
	{
		$ul = array();
		$current = null;
		$ul['index']['params'][] = array('Name'=>'Prado Manual', 'Local'=>'manual.html');

		foreach(explode("\n", $content) as $line)
		{
			$line = trim($line);
			if(strlen($line) > 0)
			{
				if(strpos($line,'^')===false)
				{
					$current = $line;
					$ul[$current]['params'][]['Name'] = $current;
				}
				else
				{
					list($page,$title) = explode('^', $line);
					$ul[$current]['ul'][0]['params'][] = array('Name'=>$title, 'Local'=>$this->getFileName($page));
				}
			}
		}
		$version = Prado::getVersion();
		$ul['api']['params'][] = array('Name' => "Prado {$version} API Manual");

		return $ul;
	}

	protected function getFileName($page)
	{
		return 'quickstart\\'.str_replace('.', '_',$page).'.html';
	}

	protected function prepareContent($content)
	{
		$content = preg_replace('/<\/?div[^>]*>/','', $content);
		$content = preg_replace('/<\/?ul>|<\/?li>|<\/a>/ms', '', $content);
		$content = str_replace('<a href="?page=', '', $content);
		$content = str_replace('">', '^', $content);
		return $content;
	}

	public function to_string($ul)
	{
		$contents = "<UL>\n";
		foreach($ul as $li)
		{
				if(isset($li['params']))
				{
					$contents .= $this->li_to_string($li);
				}
				if(isset($li['ul']))
				{
					$contents .= $this->to_string($li['ul']);
				}
		}
		$contents .= "</UL>\n";
		return $contents;
	}

	protected function li_to_string($li)
	{
		$contents = '';
		foreach($li['params'] as $param)
		{
			$contents .= "\t<LI>";
			$contents .= "<OBJECT type=\"text/sitemap\">\n";
			foreach($param as $name => $value)
				$contents .= "\t\t\t<param name=\"$name\" value=\"$value\">\n";
			$contents .= "\t\t</OBJECT>\n";
		}
		return $contents;
	}

	public function header()
	{
		$content = <<<EOD
<HTML>
<HEAD>
</HEAD>
<BODY>
   <OBJECT type="text/site properties">
     <param name="Window Styles" value="0x800025">
     <param name="FrameName" value="right">
     <param name="ImageType" value="Folder">
     <param name="comment" value="title:Online Help">
     <param name="comment" value="base:index.htm">
   </OBJECT>

EOD;
		return $content;
	}

	public function footer()
	{
		return '</BODY></HTML>';
	}
}

class ClassDocBuilder
{
	private $output;
	private $base;

	function __construct($base, $output)
	{
		$this->base = $base;
		$this->output = $output.'/classdoc';
		if(!is_dir($this->output))
		{
			mkdir($this->output);
			mkdir($this->output.'/assets/');
		}
		Prado::setPathOfAlias('Output', $this->output);
	}

	protected function initApp()
	{
		$this->app = new TApplication($this->base);
		$response = new THttpResponse();
		$response->setBufferOutput(false);
		$this->app->setResponse($response);
		$assets = new TAssetManager();
		$assets->setBasePath('Output.assets.*');
		$this->app->setAssetManager($assets);
	}

	public function buildDoc($class)
	{
		$this->parsePage($class);
	}

	public function parseBasePage()
	{
		$_GET['page'] = 'Classes';

		$html = $this->parseHtmlContent($this->getApplicationContent());
		$file = 'Classes.html';
//		echo 'writing file '.$file."\n";
		file_put_contents($this->output.'/'.$file, $html);
	}

	public function parsePage($class)
	{
		$_GET['page'] = 'ClassDoc';
		$_GET['class'] = $class;

		$html = $this->parseHtmlContent($this->getApplicationContent());
		$file = $class.'.html';
//		echo 'writing file '.$file."\n";
		file_put_contents($this->output.'/'.$file, $html);
	}

	protected function getApplicationContent()
	{
		ob_start();
		$this->initApp();
		$this->app->run();
		$content = ob_get_contents();
		ob_end_clean();
		$this->app->completeRequest();
		$this->app=null;
		return $content;
	}

	protected function parseHtmlContent($content)
	{
		$html = preg_replace('/<input.*name="PRADO_PAGESTATE" [^>]+\/>/m', '', $content);
		$html = preg_replace('!href="/(\w+)/style.css"!', 'href="assets/$1/style.css"', $html);
		return $html;
	}
}


?>
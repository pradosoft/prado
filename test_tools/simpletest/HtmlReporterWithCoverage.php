<?php

if (!defined('T_ML_COMMENT')) 
	define('T_ML_COMMENT', T_COMMENT);
else 
	define('T_DOC_COMMENT', T_ML_COMMENT);

class HtmlReporterWithCoverage extends HtmlReporter 
{
	protected $coverage = array();

	protected $painter;

	protected $base_dir;

	function __construct($painter = 'index.php', $base_dir)
	{
		$this->painter = $painter;
		$this->base_dir = $base_dir;
	}

	function paintHeader($test_name, $charset="UTF-8") 
	{
		$this->sendNoCacheHeaders();
		header('Content-Type: text/html; Charset='.$charset);
		print "<html>\n<head>\n<title>$test_name</title>\n";
		print "<meta http-equiv=\"Content-Type\" content=\"text/html; charset={$charset}\"/>";
		print "<style type=\"text/css\">\n";
		print $this->_getCss() . "\n";
		print "</style>\n";
		print "</head>\n<body>\n";
		print "<h1>$test_name</h1>\n";
		flush();
	
		if (extension_loaded('xdebug')) 
			xdebug_start_code_coverage(XDEBUG_CC_UNUSED);

	}	

	/**
	 *
	 */
	function _getCss() 
	{
		$contents = parent::_getCss()."\n ";
		$contents .= '
	 .bar { float: left; display: inline;  border: 1px solid #eee; width: 300px; white-space: nowrap;} 
	.percentage { float: left; background-color: #eef;  font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;  font-size: 0.65em;  padding: 5px;  margin-right: } 
	.coverage {margin: 0.4em; } 
	.coverage a {
		padding-left: 0.5em;
	}
	.coverage:after { 
	content: "."; 
	display: block; 
	height: 0; 
	clear: both; 
	visibility: hidden;
	}
	.coverage {display: inline-block;}
	/* Hides from IE-mac \*/
	* html .coverage {height: 1%;}
	.coverage {display: block;}
	/* End hide from IE-mac */
	';
		Return $contents;
	}

	function paintFooter($test_name) 
	{
		if (extension_loaded('xdebug')) 
		{
			$this->coverage = xdebug_get_code_coverage();
			xdebug_stop_code_coverage();
		}

		$colour = ($this->getFailCount() + $this->getExceptionCount() > 0 ? "red" : "green");
		print "<div style=\"";
		print "padding: 8px; margin-top: 1em; background-color: $colour; color: white;";
		print "\">";
		print $this->getTestCaseProgress() . "/" . $this->getTestCaseCount();
		print " test cases complete:\n";
		print "<strong>" . $this->getPassCount() . "</strong> passes, ";
		print "<strong>" . $this->getFailCount() . "</strong> fails and ";
		print "<strong>" . $this->getExceptionCount() . "</strong> exceptions.";
		print "</div>\n";
		$this->paintCoverage();
		print "</body>\n</html>\n";
	}

	function paintCoverage()
	{
		$dir = dirname(__FILE__);
		if(count($this->coverage) > 0)
			print '<h2>Code Coverage</h2>';
	
		
		ksort($this->coverage);		
		
		$details = array();
		foreach($this->coverage as $file => $coverage)
		{
			if(is_int(strpos($file, $dir)) == false
			&& is_int(strpos($file, 'simpletest')) == false
			&& is_int(strpos($file, $this->base_dir)))
			{
				$total = HTMLCoverageReport::codelines($file);
				$executed = count($coverage);
				$percentage = sprintf('%01d',$executed/$total*100);
				$width = $percentage * 3;
				$filename = str_replace($this->base_dir, '',$file);
				$link = $this->constructURL($filename, $coverage);
				
				$detail['total'] = $total;
				$detail['executed'] = $executed;
				$detail['width'] = $width;
				$detail['filename'] = $filename;
				$detail['link'] = $link;
				$details[$percentage][] = $detail;
			}
		}
		krsort($details);
		foreach($details as $percentage => $files)
		{
			foreach($files as $detail)
			{
				$total = $detail['total'];
				$executed = $detail['executed'];
				$width = $detail['width'];
				$filename = $detail['filename'];
				$link = $detail['link'];

				print "<div class=\"coverage\">";
				print "<span class=\"bar\">";
				print "<span class=\"percentage\" style=\"width:{$width}px\">";
				print "$executed/$total\n";
				print "$percentage%</span></span>\n";
				print "<a href=\"{$link}\">{$filename}</a>\n";
				print "</div>\n";
			}
		}
	}

	function constructURL($file, $coverage)
	{
		$file = rawurlencode($file);
		$lines = implode(',', array_keys($coverage));
		return $this->painter.'?file='.$file.'&amp;lines='.$lines;
	}
}


class HTMLCoverageReport extends HtmlReporter 
{
	protected $file;
	protected $lines;
	protected $name;

	function __construct($file, $name, $lines)
	{
		$this->file = $file;
		$this->lines = $lines;
		$this->name = $name;
	}

	function show()
	{
		$this->paintHeader($this->name);

		$contents = file($this->file);
		foreach($contents as $count => $line)
		{
			$num = ($count+1);
			$line = preg_replace("/\\n|\\r/",'',$line);
			$line = htmlspecialchars($line);
			$line = str_replace(' ','&nbsp;',$line);
			$line = str_replace("\t",'&nbsp;&nbsp;&nbsp;&nbsp;',$line);
			if(in_array($count+1, $this->lines))
			echo "<div class=\"highlight\"><tt>$num $line</tt></div>\n";
			else
			echo "<tt>$num $line</tt><br />\n";
		}

		$this->paintFooter();
	}

	function paintHeader($file, $charset="UTF-8") 
	{
		$total = $this->codelines($this->file);
		$executed = count($this->lines);
		$percentage = sprintf('%01.2f',$executed/$total*100);

		$this->sendNoCacheHeaders();
		header('Content-Type: text/html Charset='.$charset);
		print "<html>\n<head>\n<title>Code Coverage: $file</title>\n";
		print "<meta http-equiv=\"Content-Type\" content=\"text/html; charset={$charset}\"/>";
		print "<style type=\"text/css\">\n";
		print $this->_getCss() . "\n";
		print ".highlight { background-color: #eef; } \n";
		print ".filename { margin-bottom: 2em; } \n";
		print "</style>\n";
		print "</head>\n<body>\n";
		print "<h1>Code Coverage</h1>\n";
		print "<div class=\"filename\"><strong>$file</strong></div>";
		print "<div class=\"filename\"><tt>&nbsp;&nbsp;&nbsp;&nbsp;Total code lines: {$total} <br /> Total lines executed: {$executed} ({$percentage}%)</tt></div>";
		flush();
	}

	function paintFooter($test_name)
	{
		print "</body>\n</html>\n";
	}

	static function codelines($file)
	{
		$source = file_get_contents($file);
		$tokens = @token_get_all($source);

		$lines = '';

		foreach ($tokens as $token) 
		{
			if (is_string($token)) 
			{
				// simple 1-character token
				$lines .= $token;
			} 
			else 
			{
					// token array
				list($id, $text) = $token;

				switch ($id) 
				{ 
					case T_COMMENT: 
					case T_ML_COMMENT: // we've defined this
					case T_DOC_COMMENT: // and this
					// no action on comments
					break;

					default:
					// anything else -> output "as is"
					//echo $text;
					$lines .= $text;
					break;
				}
			}
		}

		$lines =  preg_replace('/\\n\s*$/m',"",$lines);
		$codelines = explode("\n",$lines);
		$count = 0;
		$patterns[] = '^\s*{\s*$';
		$patterns[] = '<\?';
		$patterns[] = '^\s*(private|protected|public)\s+\$';
		$pattern = '/'.implode('|', $patterns).'/';
		foreach($codelines as $line)
		{
			if(!preg_match($pattern, $line))
				$count++;
		}
		return $count;
		//var_dump($codelines);
		//return count($codelines);
	}
}

?>

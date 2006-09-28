<?php

class Page2Tex
{
	private $_current_page;
	private static $header_count = 0;
	private $_base;
	private $_dir;

	private $_verb_find = array('\$','\%', '\{', '\}', "\t",'``','`');
	private $_verb_replace = array('$', '%', '{','}', "     ",'"','\'');

	function __construct($base, $dir, $current='')
	{
		$this->_base = $base;
		$this->_current_page = $current;
		$this->_dir = $dir;
	}

	function setCurrentPage($current)
	{
		$this->_current_page = $current;
	}

	function escape_verbatim($matches)
	{
		return "\begin{small}\begin{verbatim}".
				str_replace($this->_verb_find, $this->_verb_replace, $matches[1]).
				"\end{verbatim}\end{small}\n";
	}

	function escape_verb($matches)
	{
		$text = str_replace($this->_verb_find, $this->_verb_replace, $matches[1]);
		return '\begin{small}\verb<'.$text.'< \end{small}';
	}

	function include_image($matches)
	{

		$current_path = $this->_current_page;

		$image = dirname($current_path).'/'.trim($matches[1]);

		$file = realpath($image);
		$info = getimagesize($file);
		switch($info[2])
		{
			case 1:
				$im = imagecreatefromgif($file);
				break;
			case 2: $im = imagecreatefromjpeg($file); break;
			case 3: $im = imagecreatefrompng($file); break;
		}
		$base = $this->_base;

		if(isset($im))
		{
			$prefix = strtolower(str_replace(realpath($base), '', $file));
			$filename = preg_replace('/\\\|\//', '_', substr($prefix,1));
			$filename = substr($filename, 0, strrpos($filename,'.')).'.png';
			$newfile = $this->_dir.'/'.$filename;
			imagepng($im,$newfile);
			imagedestroy($im);

			return $this->include_figure($info, $filename);
		}
	}

	function include_figure($info, $filename)
	{
		$width = sprintf('%0.2f', $info[0]/(135/2.54));
		return '
	\begin{figure}[!ht]
		\centering
			\includegraphics[width='.$width.'cm]{'.$filename.'}
		\label{fig:'.$filename.'}
	\end{figure}
	';
	}

	function anchor($matches)
	{
		$page = $this->get_current_path();
		return '\hypertarget{'.$page.'/'.strtolower($matches[1]).'}{}';
	}

	function texttt($matches)
	{
		return '\texttt{'.str_replace(array('#','_','&amp;'),array('\#','\_','\&'), $matches[1]).'}';
	}

	function get_current_path()
	{
		$current_path = $this->_current_page;
		$base = $this->_base;
		$page = strtolower(substr(str_replace($base, '', $current_path),1));
		return $page;
	}

	function make_link($matches)
	{
		if(is_int(strpos($matches[1], '#')))
		{
			if(strpos($matches[1],'?') ===false)
			{
				$target = $this->get_current_path().'/'.substr($matches[1],1);
				return '\hyperlink{'.$target.'}{'.$matches[2].'}';
			}
			else
			{
				$page = strtolower(str_replace('?page=', '', $matches[1]));
				$page = str_replace('.','/',$page);
				$page = str_replace('#','.page/',$page);
				return '\hyperlink{'.$page.'}{'.$matches[2].'}';
			}
		}
		else if(is_int(strpos($matches[1],'?')))
		{
			$page = str_replace('?page=','',$matches[1]);
			return '\hyperlink{'.$page.'}{'.$matches[2].'}';
		}
		return '\href{'.$matches[1].'}{'.$matches[2].'}';
	}

	function parse_html($page,$html)
	{


		$html = preg_replace('/<\/?com:TContent[^>]*>/', '', $html);
		$html = preg_replace('/<\/?p [^>]*>/', '', $html);
		$html = preg_replace('/<\/?p>/', '', $html);

		$html = preg_replace('/(\s+|\(+|\[+)"/', '$1``', $html);
		$html = preg_replace('/(\s+|\(+|\[+)\'/', '$1`', $html);

		//escape { and }
		$html = preg_replace('/([^\s]+){([^}]*)}([^\s]+)/', '$1\\\{$2\\\}$3', $html);

		$html = preg_replace_callback('/<img\s+src="?<%~([^"]*)%>"?[^>]*\/>/', array($this, 'include_image'), $html);

		//escape %
		$html = str_replace('%', '\%', $html);

		//codes
		$html = str_replace('$', '\$', $html);

		$html = preg_replace_callback('/<com:TTextHighlighter[^>]*>((.|\n)*?)<\/com:TTextHighlighter>/', array($this,'escape_verbatim'), $html);
//		$html = preg_replace('/<\/com:TTextHighlighter>/', '`2`', $html);
//		$html = preg_replace_callback('/(`1`)([^`]*)(`2`)/m', array($this,'escape_verbatim'), $html);
		$html = preg_replace_callback('/(<div class="source">)((.|\n)*?)(<\/div>)/', array($this,'escape_verbatim'), $html);
		$html = preg_replace_callback('/(<pre>)([^<]*)(<\/pre>)/', array($this,'escape_verbatim'), $html);

		//<code>
		$html = preg_replace_callback('/<code>([^<]*)<\/code>/', array($this,'escape_verb'), $html);

		//runbar
		$html = preg_replace('/<com:RunBar\s+PagePath="([^"]*)"\s+\/>/',
				'\href{http://www.pradosoft.com/demos/quickstart/index.php?page=$1}{$1 Demo}', $html);

		//DocLink
		$html = preg_replace('/<com:DocLink\s+ClassPath="([^"]*)[.]([^.]*)"\s+\/>/',
	                        '\href{http://www.pradosoft.com/docs/manual/$1/$2.html}{$1.$2 API Reference}', $html);

		//text modifiers
		$html = preg_replace('/<b[^>]*>([^<]*)<\/b>/', '\textbf{$1}', $html);
		$html = preg_replace('/<i[^>]*>([^<]*)<\/i>/', '\emph{$1}', $html);
		$html = preg_replace_callback('/<tt>([^<]*)<\/tt>/', array($this,'texttt'), $html);

		//links
		$html = preg_replace_callback('/<a[^>]+href="([^"]*)"[^>]*>([^<]*)<\/a>/',
								array($this,'make_link'), $html);
		//anchor
		$html = preg_replace_callback('/<a[^>]+name="([^"]*)"[^>]*><\/a>/', array($this,'anchor'), $html);

		//description <dl>
		$html = preg_replace('/<dt>([^<]*)<\/dt>/', '\item[$1]', $html);
		$html = preg_replace('/<\/?dd>/', '', $html);
		$html = preg_replace('/<dl>/', '\begin{description}', $html);
		$html = preg_replace('/<\/dl>/', '\end{description}', $html);

		//item lists
		$html = preg_replace('/<ul[^>]*>/', '\begin{itemize}', $html);
		$html = preg_replace('/<\/ul>/', '\end{itemize}', $html);
		$html = preg_replace('/<ol[^>]*>/', '\begin{enumerate}', $html);
		$html = preg_replace('/<\/ol>/', '\end{enumerate}', $html);
		$html = preg_replace('/<li[^>]*>/', '\item ', $html);
		$html = preg_replace('/<\/li>/', '', $html);

		//headings
		$html = preg_replace('/<h1(\s+id="[^"]+")?>([^<]+)<\/h1>/', '\section{$2}', $html);
		$html = preg_replace('/<h2(\s+id="[^"]+")?>([^<]+)<\/h2>/', '\subsection{$2}', $html);
		$html = preg_replace('/<h3(\s+id="[^"]+")?>([^<]+)<\/h3>/', '\subsubsection{$2}', $html);

		//div box
		$html = preg_replace_callback('/<div class="[tipnofe]*?">((.|\n)*?)<\/div>/',
						array($this, 'mbox'), $html);

		//tabular
		$html = preg_replace_callback('/<!--\s*tabular:([^-]*)-->\s*<table[^>]*>((.|\n)*?)<\/table>/',
						array($this, 'tabular'), $html);

		$html = html_entity_decode($html);

		return $html;
	}

	function tabular($matches)
	{
		$options = array();
		foreach(explode(',', $matches[1]) as $string)
		{
			$sub = explode('=', trim($string));
			$options[trim($sub[0])] = trim($sub[1]);
		}

		$widths = explode(' ',preg_replace('/\(|\)/', '', $options['width']));
		$this->_tabular_widths = $widths;

		$this->_tabular_total = count($widths);
		$this->_tabular_col = 0;

		$begin = "\begin{table}[!hpt]\centering \n \begin{tabular}{".$options['align']."}\\hline";
		$end = "\end{tabular} \n \end{table}\n";
		$table = preg_replace('/<\/tr>/', '\\\\\\\\ \hline', $matches[2]);
		$table = preg_replace('/<tr>/', '', $table);
		$table = preg_replace('/<th>([^<]+)<\/th>/', '\textbf{$1} &', $table);
		$table = preg_replace_callback('/<td>((.|\n)*?)<\/td>/', array($this, 'table_column'), $table);
		$table = preg_replace('/<br \/>/', ' \\\\\\\\', $table);

		$table = preg_replace('/&\s*\\\\\\\\/', '\\\\\\\\', $table);
		return $begin.$table.$end;
	}

	function table_column($matches)
	{
		$width = $this->_tabular_widths[$this->_tabular_col];
		if($this->_tabular_col >= $this->_tabular_total-1)
			$this->_tabular_col = 0;
		else
			$this->_tabular_col++;
		return '\begin{minipage}{'.$width.'\textwidth}\vspace{3mm}'.
			$matches[1].'\vspace{3mm}\end{minipage} & ';
	}

	function mbox($matches)
	{
		return "\n\begin{mybox}\n".$matches[1]."\n\end{mybox}\n";
	}

	function get_chapter_label($chapter)
	{
		return '\hypertarget{'.str_replace(' ', '', $chapter).'}{}';
	}

	function get_section_label($section)
	{
		$section = str_replace('.page', '', $section);
		return '\hypertarget{'.str_replace('/', '.', $section).'}{}';
	}


	function set_header_id($content, $count)
	{
		self::$header_count = $count*100;
		$content = preg_replace_callback('/<h1>/', array($this,"h1"), $content);
		$content = preg_replace_callback('/<h2>/', array($this,"h2"), $content);
		$content = preg_replace_callback('/<h3>/', array($this,"h3"), $content);
		return $content;
	}

	function h1($matches)
	{
		return "<h1 id=\"".(++self::$header_count)."\">";
	}

	function h2($matches)
	{
		return "<h2 id=\"".(++self::$header_count)."\">";
	}

	function h3($matches)
	{
		return "<h3 id=\"".(++self::$header_count)."\">";
	}

}

?>
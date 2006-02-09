<?php
//page root location
$base = realpath(dirname(__FILE__).'/../../demos/quickstart/protected/pages/');

//list page into chapters
$pages['Getting Started'] = array(
	'GettingStarted/Introduction.page',
	'GettingStarted/AboutPrado.page',
	'GettingStarted/Installation.page');

$pages['Fundamentals'] = array(
	'Fundamentals/Architecture.page',
    'Fundamentals/Components.page',
    'Fundamentals/Controls.page',
    'Fundamentals/Pages.page',
    'Fundamentals/Modules.page',
    'Fundamentals/Services.page',
    'Fundamentals/Applications.page',
    'Fundamentals/HelloWorld.page',
    'Fundamentals/Hangman.page');

$pages['Configurations'] = array(
	'Configurations/Overview.page',
	'Configurations/Templates1.page',
	'Configurations/Templates2.page',
	'Configurations/Templates3.page',
	'Configurations/AppConfig.page',
	'Configurations/PageConfig.page');

$pages['Controls'] = array(
	'Controls/Overview.page',
	'Controls/Simple.page',
	'Controls/List.page',
	'Controls/Validation.page',
	'Controls/Repeater.page',
	'Controls/DataList.page',
	'Controls/DataGrid1.page',
	'Controls/DataGrid2.page',
	'Controls/NewControl.page');

$pages['Security'] = array(
	'Security/Auth.page',
	'Security/ViewState.page',
	'Security/XSS.page');

$pages['Advanced Topics'] = array(
	'Advanced/Assets.page',
	'Advanced/MasterContent.page',
	'Advanced/Themes.page',
	'Advanced/State.page',
	'Advanced/Logging.page',
	'Advanced/I18N.page',
	'Advanced/Error.page',
	'Advanced/Performance.page');


//-------------- END CONFIG ------------------


function escape_verbatim($matches)
{
	return "\begin{verbatim}".str_replace('\$', '$', $matches[2])."\end{verbatim}\n";
}

function escape_verb($matches)
{
	$text = str_replace(array('\{', '\}'), array('{','}'), $matches[1]);
	return '\verb<'.$text.'<';
}

function include_image($matches)
{
	global $current_path;

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
	global $base;

	if(isset($im))
	{
		$prefix = strtolower(str_replace(realpath($base), '', $file));
		$filename = preg_replace('/\\\|\//', '_', substr($prefix,1));
		$filename = substr($filename, 0, strrpos($filename,'.')).'.png';
		$newfile = dirname(__FILE__).'/'.$filename;
		imagepng($im,$newfile);
		imagedestroy($im);

		return include_figure($info, $filename);
	}
}

function include_figure($info, $filename)
{
	$width = sprintf('%0.2f', $info[0]/(135/2.54));
	return '
\begin{figure}[ht]
	\centering
		\includegraphics[width='.$width.'cm]{'.$filename.'}
	\label{fig:'.$filename.'}
\end{figure}
';
}

function anchor($matches)
{
	$page = get_current_path();
	return '\hypertarget{'.$page.'/'.$matches[1].'}{}';
}

function get_current_path()
{
	global $current_path, $base;
	$page = strtolower(substr(str_replace($base, '', $current_path),1));
	return $page;
}

function make_link($matches)
{
	if(is_int(strpos($matches[1], '#')))
	{
		if(strpos($matches[1],'?') ===false)
		{
			$target = get_current_path().'/'.substr($matches[1],1);
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
	$html = preg_replace('/<\/?p>/m', '', $html);

	//escape { and }
	$html = preg_replace('/([^\s]+){([^}]*)}([^\s]+)/', '$1\\\{$2\\\}$3', $html);

	//codes
	$html = str_replace('$', '\$', $html);
	$html = preg_replace('/<com:TTextHighlighter[^>]*>/', '`1`', $html);
	$html = preg_replace('/<\/com:TTextHighlighter>/', '`2`', $html);
	$html = preg_replace_callback('/(`1`)([^`]*)(`2`)/m', 'escape_verbatim', $html);
	$html = preg_replace_callback('/(<div class="source">)([^<]*)(<\/div>)/', 'escape_verbatim', $html);

	//<code>
	$html = preg_replace_callback('/<code>([^<]*)<\/code>/', 'escape_verb', $html);

	$html = preg_replace_callback('/<img\s+src="?<%~([^"]*)%>"?[^\\/]*\/>/', 'include_image', $html);

	//runbar
	$html = preg_replace('/<com:RunBar\s+PagePath="([^"]*)"\s+\/>/',
			'Try, \href{http://www.pradosoft.com/prado3/demos/quickstart/index.php?page=$1}{$1}', $html);

	//text modifiers
	$html = preg_replace('/<b>([^<]*)<\/b>/', '\textbf{$1}', $html);
	$html = preg_replace('/<i>([^<]*)<\/i>/', '\emph{$1}', $html);
	$html = preg_replace('/<tt>([^<]*)<\/tt>/', '\texttt{$1}', $html);

	//links
	$html = preg_replace_callback('/<a[^>]+href="([^"]*)"[^>]*>([^<]*)<\/a>/',
							'make_link', $html);
	//anchor
	$html = preg_replace_callback('/<a[^>]+name="([^"]*)"[^>]*><\/a>/', 'anchor', $html);

	//description <dl>
	$html = preg_replace('/<dt>([^<]*)<\/dt>/', '\item[$1]', $html);
	$html = preg_replace('/<\/?dd>/', '', $html);
	$html = preg_replace('/<dl>/', '\begin{description}', $html);
	$html = preg_replace('/<\/dl>/', '\end{description}', $html);

	//item lists
	$html = preg_replace('/<ul>/', '\begin{itemize}', $html);
	$html = preg_replace('/<\/ul>/', '\end{itemize}', $html);
	$html = preg_replace('/<ol>/', '\begin{enumerate}', $html);
	$html = preg_replace('/<\/ol>/', '\end{enumerate}', $html);
	$html = preg_replace('/<li>/', '\item ', $html);
	$html = preg_replace('/<\/li>/', '', $html);

	//headings
	$html = preg_replace('/<h1>([^<]+)<\/h1>/', '\section{$1}', $html);
	$html = preg_replace('/<h2>([^<]+)<\/h2>/', '\subsection{$1}', $html);
	$html = preg_replace('/<h3>([^<]+)<\/h3>/', '\subsubsection{$1}', $html);



	$html = html_entity_decode($html);


	return $html;
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

//--------------- BEGIN PROCESSING -------------------

$count = 1;
$current_path = '';
echo "Compiling .page files to Latex files\n\n";
foreach($pages as $chapter => $sections)
{
	$content = '\chapter{'.$chapter.'}'.get_chapter_label($chapter);
	echo "Creating ch{$count}.txt => Chapter {$count}: {$chapter}\n";
	echo str_repeat('-',60)."\n";
	foreach($sections as $section)
	{
		echo "    Adding $section\n";
		$page = $base.'/'.$section;
		$current_path = $page;
		$content .= get_section_label($section);
		$content .= parse_html($page,file_get_contents($page));
	}

	//var_dump($content);
	file_put_contents("ch{$count}.tex", $content);
	$count++;
	echo "\n";
}

if($count > 1)
{
	echo "** Use pdftex to compile prado3_quick_start.tex to obtain PDF version of quickstart tutorial. **\n";
}


?>

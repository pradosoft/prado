<?php

// TBD: subsections in Control Reference

$pdflatexExec = "C:/Wei/miktex/texmf/MiKTeX/bin/pdflatex.exe";
$pdfTex = "$pdflatexExec -interaction=nonstopmode -max-print-line=120 %s";

$mainTexFile = dirname(__FILE__).'/sqlmap.tex';

//page root location
$base = realpath(dirname(__FILE__).'/../../../demos/sqlmap/protected/pages/');

//-------------- END CONFIG ------------------

$pages = include('pages.php');

include('../../../../prado-3.0/buildscripts/texbuilder/Page2Tex.php');

// ---------------- Create the Tex files ---------
$count = 1;
$j = 1;
$current_path = '';
echo "Compiling .page files to Latex files\n\n";

$parser = new Page2Tex($base, dirname(__FILE__));

foreach($pages as $chapter => $sections)
{
	$content = '\chapter{'.$chapter.'}'.$parser->get_chapter_label($chapter);
	echo "Creating ch{$count}.txt => Chapter {$count}: {$chapter}\n";
	echo str_repeat('-',60)."\n";
	foreach($sections as $section)
	{
		echo "    Adding $section\n";
		$page = $base.'/'.$section;
		$current_path = $page;
		$parser->setCurrentPage($current_path);

		//add id to <h1>, <h2>, <3>
		$tmp_content = $parser->set_header_id(file_get_contents($page),$j++);
//		file_put_contents($page, $tmp_content);

		$content .= $parser->get_section_label($section);
		$file_content = file_get_contents($page);
		$tex =
		$content .= $parser->parse_html($page,$file_content);
	}

	//var_dump($content);
	file_put_contents("ch{$count}.tex", $content);
	$count++;
	echo "\n";
}

//$indexer->commit();

if($argc <= 1 && $count > 1)
{
	echo "** Use pdflatex to compile quickstart.tex to obtain PDF version of quickstart tutorial. **\n";
	exit;
}
if($argv[1] == 'pdf')
{
	if(is_file($pdflatexExec))
	{
		//build pdfTex
		$command=sprintf($pdfTex,$mainTexFile);
		system($command);
		system($command); //run it twice

		echo "\n\n** PDF file quickstart.pdf created **\n\n";

	}
	else
	{
		echo " Unable to find pdfLatex executable $pdflatexExec";
	}
}


?>

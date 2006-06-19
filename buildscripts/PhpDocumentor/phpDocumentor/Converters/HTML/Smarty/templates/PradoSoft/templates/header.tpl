<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" >

<head>
<title>PRADO API Manual: {$title}</title>

<meta http-equiv="Expires" content="Fri, Jan 01 1900 00:00:00 GMT"/>
<meta http-equiv="Pragma" content="no-cache"/>
<meta http-equiv="Cache-Control" content="no-cache"/>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<meta http-equiv="content-language" content="en"/>
<meta name="Keywords" content="PRADO PHP framework component template delphi asp.net event property OOP PHP5 object oriented programming Web programming development" />
<meta name="Description" content="PRADO is a component-based and event-driven framework for Web application development in PHP 5." />
<meta name="Author" content="Qiang Xue" />
<meta name="Subject" content="Web programming, PHP framework" />
<meta name="Language" content="en" />
<link rel="Shortcut Icon" href="/favicon.ico" />
<link rel="stylesheet" type="text/css" href="/css/style.css" />
<link rel="stylesheet" type="text/css" href="/css/manual.css" />
</head>
<body>

<div id="page">

    <div id="header">

      <div id="logo">
        <img src="/css/pradoheader.gif" alt="PRADO Component Framework for PHP 5" />
      </div>
      <div id="mainmenu">
        <ul>
          <li><a href="/">Home</a></li>
          <li><a href="/about/" >About</a></li>
          <li><a href="/testimonials/" >Testimonials</a></li>
          <li><a href="/demos/" >Demos</a></li>
          <li><a href="/download/" >Download</a></li>
          <li><a href="/documentation/" class="active">Documentation</a></li>
          <li><a href="/community/" >Community</a></li>
          <li><a href="/support/" >Support</a></li>
        </ul>
      </div><!-- mainmenu -->
    </div><!-- header -->

<div id="main">

<div id="navbar">
<ul>
<li><a href="/tutorials/">Tutorials</a></li>
<li><a href="/wiki/">Wiki</a></li>
<li><a href="/docs/classdoc/">Classes</a></li>
<li><a href="/docs/manual/" class="active">Manual</a></li>
</ul>
</div>

<table width="100%" border="0" cellpadding="0" cellspacing="0">
  <tr valign="top">
    <td width="200" id="infobar">
{if count($ric) >= 1}
	<div id="ric">
		{section name=ric loop=$ric}
			<p><a href="{$subdir}{$ric[ric].file}">{$ric[ric].name}</a></p>
		{/section}
	</div>
{/if}
      <b>Packages:</b><br />
      {section name=packagelist loop=$packageindex}
        <a href="{$subdir}{$packageindex[packagelist].link}">{$packageindex[packagelist].title}</a><br />
      {/section}
      <br /><br />
{if $tutorials}
		<b>Tutorials/Manuals:</b><br />
		{if $tutorials.pkg}
			<strong>Package-level:</strong>
			{section name=ext loop=$tutorials.pkg}
				{$tutorials.pkg[ext]}
			{/section}
		{/if}
		{if $tutorials.cls}
			<strong>Class-level:</strong>
			{section name=ext loop=$tutorials.cls}
				{$tutorials.cls[ext]}
			{/section}
		{/if}
		{if $tutorials.proc}
			<strong>Procedural-level:</strong>
			{section name=ext loop=$tutorials.proc}
				{$tutorials.proc[ext]}
			{/section}
		{/if}
{/if}
      {if !$noleftindex}{assign var="noleftindex" value=false}{/if}
      {if !$noleftindex}
{*
{if $compiledfileindex}
      <b>Files:</b><br />
      {eval var=$compiledfileindex}
      {/if}
*}
      {if $compiledclassindex}
      <b>Classes:</b><br />
      {eval var=$compiledclassindex}
      {/if}
      {/if}
    </td>
    <td>
      <table cellpadding="10" cellspacing="0" width="100%" border="0">
      <tr><td valign="top" align="center">
      <form type="get" action="/docs/manual/search.php">
      Keyword <input type="text" name="keyword" size="50" />
      <input type="submit" value="Search" />
      </form>
      </td></tr>
      <tr><td valign="top"><!-- content begin -->
{*
{if !$hasel}{assign var="hasel" value=false}{/if}
{if $hasel}
<h1>{$eltype|capitalize}: {$class_name}</h1>
Source Location: {$source_location}<br /><br />
{/if}
*}

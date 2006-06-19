{include file="header.tpl" top1=true}

<!-- Start of Class Data -->
<H2>
	{$smarty.capture.title}
</H2>
{section name=classtrees loop=$classtrees}
<h2>Root class {$classtrees[classtrees].class}</h2>
{$classtrees[classtrees].class_tree}
{/section}
{include file="footer.tpl"}
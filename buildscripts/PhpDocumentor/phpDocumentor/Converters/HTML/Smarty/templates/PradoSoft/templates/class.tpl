{include file="header.tpl" eltype="class" hasel=true contents=$classcontents}

<h1>{if $is_interface}Interface{else}Class{/if} {$class_name}</h1>

{*inheritence tree*}
<div class="inheritence-tree">
    <pre>{section name=tree loop=$class_tree.classes}{if $smarty.section.tree.last}<strong>{$class_tree.classes[tree]}</strong>{else}{$class_tree.classes[tree]}{/if}{$class_tree.distance[tree]}{/section}</pre>
</div>

{include file="_sub_classes.tpl"}
{include file="_class_description.tpl"}
{include file="_inherited_constants.tpl"}

{include file="_constructor_summary.tpl"}
{* include file="_destructor_summary.tpl" *}

{include file="_method_summary.tpl"}

{include file="_inherited_methods.tpl"}
{include file="_constant_summary.tpl"}
{include file="_constructor_details.tpl"}

{* include file="_destructor_details.tpl" *}

{include file="_method_details.tpl"}

{include file="_constant_details.tpl"}

{include file="footer.tpl"}

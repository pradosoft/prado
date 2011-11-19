{if $children}
    <div class="sub-classes">
        {if $is_interface}
            <h4>Direct Known Sub-interfaces:</h4>
        {else}
            <h4>Direct Known Sub-classes:</h4>
        {/if}

        <div><small>
        {section name=child loop=$children}
            {if !$smarty.section.child.first}
                , {$children[child].link}
            {else}
                {$children[child].link}
            {/if}
        {/section}
        </small></div>
    </div>
{/if}

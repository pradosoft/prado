{if $methods}
    {section name=method loop=$methods}
        {if $methods[method].function_name == "__construct"}
            <hr size="1" noshade="noshade"/>
            <a name="constructor-summary"></a>
            <table class="method-summary" cellspacing="1">
                <tr>
                    <th colspan="2">Constructor Summary</th>
                </tr>
                <tr>
                    <td class="type" nowrap="nowrap" width="1%">{$methods[method].access}</td>
                    <td>
                        <div class="declaration">
                            <a href="{$methods[method].id}">{$methods[method].function_name}</a>
                            {$methods[method].ifunction_call.params}
                        </div>
                        <div class="description">
                            {$methods[method].sdesc}
                        </div>
                    </td>
                </tr>
            </table>
        {/if}
    {/section}
{/if}

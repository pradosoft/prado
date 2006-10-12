{if $methods && (count($methods) > 1 ||
   ($methods[0].function_name != "__construct" &&
    $methods[0].function_name != "__destruct"))}

    <hr size="1" noshade="noshade"/>
    <a name="method-details"></a>
    <table class="method-details" cellspacing="1">
        <tr>
            <th>Method Details</th>
        </tr>
        {section name=method loop=$methods}
            {if $methods[method].function_name != "__construct" &&
                $methods[method].function_name != "__destruct"}

                <tr>
                    <td class="method-data">

                        <a name="{$methods[method].method_dest}"></a>

                        <h2>{$methods[method].function_name}</h2>

                        <table class="method-detail" cellspacing="0">
                            <tr>
                                <td nowrap="nowrap">{strip}
                                    {if $methods[method].access == "protected"}
                                        protected&nbsp;
                                    {/if}

                                    {if $methods[method].access == "public"}
                                        public&nbsp;
                                    {/if}

                                    {if $methods[method].abstract == 1}
                                        abstract&nbsp;
                                    {/if}

                                    {if $methods[method].static == 1}
                                        static&nbsp;
                                    {/if}

                                    {$methods[method].function_return}&nbsp;


                                    <strong>{$methods[method].function_name}</strong>
                                {/strip}</td>
                                <td width="100%">{strip}
                         (
                        {if $methods[method].ifunction_call.params}
                            {foreach item=param name="method" from=$methods[method].ifunction_call.params}
                                {$param.type} {$param.name} {if !$smarty.foreach.method.last}, {/if}
                            {/foreach}

                        {/if}
                        )
				{/strip}</td>
                            </tr>
                        </table>

                        <p>{$methods[method].sdesc}</p>

                        {if $methods[method].desc}
                            {$methods[method].desc}
                        {/if}
			{* $methods[method]|print_r *}	
			<div class="tag-list"><table class="method-summary" cellspacing="1">
			{if $methods[method].ifunction_call.params}
				<tr><th colspan="3" class="small">Input</th></tr>
                            {foreach item=param name="method" from=$methods[method].ifunction_call.params}
                                <tr><td valign="top">{$param.type}</td><td valign="top"><strong>{$param.name}</strong><td valign="top">{$param.description}</td></tr>
                            {/foreach}
                        {/if}
			{if $methods[method].tags}
				<tr><th colspan="3" class="small">Output</th></tr>
			    
                            {foreach item=param name="method" from=$methods[method].tags}
				{if $param.keyword == "return"}
                                <tr><td valign="top">
                                    {$methods[method].function_return}
				</td><td valign="top" colspan="2">{$param.data}</td></tr>
                            	{/if}
			    {/foreach}
                        {/if}
 			{if $methods[method].tags}
				<tr><th colspan="3" class="small">Exception</th></tr>
			    
                            {foreach item=param name="method" from=$methods[method].tags}
				{if $param.keyword == "throws"}
                                <tr><td valign="top">{$param.keyword}</td><td valign="top" colspan="2">{$param.data}</td></tr>
                            	{/if}
			    {/foreach}
                        {/if}
			</table></div>
                                              
 {* include file="_tags.tpl" tags=$methods[method].tags *}
                        <p/>
                    </td>
                </tr>
            {/if}
        {/section}
    </table>
{/if}

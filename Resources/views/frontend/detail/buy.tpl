{extends file="parent:frontend/detail/buy.tpl"}

{block name="frontend_detail_buy_button_container"}

	{if {config namespace="CohaOrdPosCom" name="show"} && {config namespace="CohaOrdPosCom" name="showPositionCommentOnDetail"} == true}

		{if {config namespace="CohaOrdPosCom" name="showOnlyOnSpecificProducts"} == false || ({config namespace="CohaOrdPosCom" name="showOnlyOnSpecificProducts"} && $sArticle.coha_ord_pos_com_active == true)}

			<div class="coha-ord-pos-com">
				<label>
					{if $sArticle.coha_ord_pos_com_text}
						{$sArticle.coha_ord_pos_com_text}
					{else}
					{s name="frontend/plugins/cohaordposcom/comment_pretext" namespace="frontend/plugins/cohaordposcom"}Kommentar zur Position:{/s}
					{/if}
				</label>
				{if {config name="commentAsTextarea" namespace="CohaOrdPosCom"} != true}
				<input type="text" name="coha_ord_pos_com" value="" {if {config namespace="CohaOrdPosCom" name="commentRequired"} == 1}required="required"{/if}	/>
				{else}
					<textarea name="coha_ord_pos_com" value="" {if {config namespace="CohaOrdPosCom" name="commentRequired"} == 1}required="required"{/if}></textarea>
				{/if}
			</div>

		{/if}

	{/if}

	{$smarty.block.parent}
{/block}
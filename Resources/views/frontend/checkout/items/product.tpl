{extends file="parent:frontend/checkout/items/product.tpl"}

{block name='frontend_checkout_cart_item_delete_article'}
	{$smarty.block.parent}
	{if {config namespace="CohaOrdPosCom" name="show"}}
		{block name="coha_ord_pos_com_frontend_checkout_items_product"}
			{if ({config namespace="CohaOrdPosCom" name="showOnlyOnSpecificProducts"} == false || ($sBasketItem.additional_details.coha_ord_pos_com_active == true))}
			{* The additional customer comment for the order *}
			<div style="clear: both; margin-bottom: 1rem;">
				<div class="coha-ord-pos-com-inner{if $sBasketItem.additional_details.coha_ord_pos_com_by_qty} by-qty{/if}">
					{if $sBasketItem.additional_details.coha_ord_pos_com_text}
						{$sBasketItem.additional_details.coha_ord_pos_com_text}
					{else}
						{s name="frontend/plugins/cohaordposcom/comment_pretext" namespace="frontend/plugins/cohaordposcom"}Kommentar zur Position:{/s}
					{/if}
					{if {config name="commentAsTextarea" namespace="CohaOrdPosCom"} != true}
						<input type="text"
							data-coha-ord-pos-com="true"
							data-url="{url module="widgets" controller="CohaOrdPosCom" action="saveBasketOrderPositionComment"}"
							data-basketId="{$sBasketItem.id}"
							name="user_position_comment[{$sBasketItem.id}]"
							value="{if $cohaOrdPosComs[$sBasketItem.id]}{$cohaOrdPosComs[$sBasketItem.id]}{/if}"
							placeholder="{$sBasketItem.additional_details.coha_ord_pos_com_placeholder}"
						/>
					{else}
						<textarea data-coha-ord-pos-com="true"
								data-url="{url module="widgets" controller="CohaOrdPosCom" action="saveBasketOrderPositionComment"}"
								data-basketId="{$sBasketItem.id}"
								name="user_position_comment[{$sBasketItem.id}]"
								placeholder="{$sBasketItem.additional_details.coha_ord_pos_com_placeholder}"
						>{$cohaOrdPosComs[$sBasketItem.id]}</textarea>
					{/if}
				</div>
			</div>
			{/if}
		{/block}
	{/if}
{/block}
<p class="payment_module" id="occp_container">
	<a href="{if $active}{$this_path_ssl}validation.php{else}javascript:alert('{l s='The Merchant has not configured this payment method yet, Order will not be valid' mod='offlinecreditcard'}');location.href='{$this_path_ssl}validation.php'{/if}" title="{l s='Pay with a Credit Card' mod='offlinecreditcard'}">
		<img src="{$this_path}img/combo.jpg" alt="{$occp_cards}" />
		{l s='Credit/Debit Card Terminal(Visa/Mastercard/Amex)' mod='offlinecreditcard'}
		<br style="clear:both;" />
	</a>
</p>
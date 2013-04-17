<div id="center_column" class=" grid_5">
<div id="primary_block">
{capture name=path}{l s='Payment'}{/capture}
{include file="$tpl_dir./breadcrumb.tpl"}
<script type="text/javascript" src="{$this_path_ssl}js/statesManagement.js"></script>
<script type="text/javascript">
//<![CDATA[
idSelectedCountry = {if isset($id_state)}{$id_state|intval}{elseif isset($address->id_state)}{$address->id_state|intval}{else}false{/if};
countries = new Array();
countriesNeedIDNumber = new Array();
{foreach from=$countries item='country'}
	{if isset($country.states) && $country.contains_states}
		countries[{$country.id_country|intval}] = new Array();
		{foreach from=$country.states item='state' name='states'}
			countries[{$country.id_country|intval}].push({ldelim}'id' : '{$state.id_state}', 'name' : '{$state.name|escape:'htmlall':'UTF-8'}'{rdelim});
		{/foreach}
	{/if}
{/foreach}
$(function(){ldelim}
	$('.id_state option[value={if isset($id_state)}{$id_state}{else}{$address->id_state|escape:'htmlall':'UTF-8'}{/if}]').attr('selected', 'selected');
{rdelim});
//]]>
</script>

<div class="rte">
<h2>{l s='Order Summary - Card Payment' mod='offlinecreditcard'}</h2>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}
{if $occp_visa}
	<img src="{$this_path_ssl}img/visa.gif" alt="{l s='Visa' mod='offlinecreditcard'}" />
{/if}
{if $occp_mc}
	<img src="{$this_path_ssl}img/mc.gif" alt="{l s='Mastercard' mod='offlinecreditcard'}" />
{/if}
{if $occp_amex}
	<img src="{$this_path_ssl}img/amex.gif" alt="{l s='American Express' mod='offlinecreditcard'}" />
{/if}
{if $occp_discover}
	<img src="{$this_path_ssl}img/discover.gif" alt="{l s='Discover' mod='offlinecreditcard'}" />
{/if}
{if $occp_jcb}
	<img src="{$this_path_ssl}img/jcb.gif" alt="{l s='JCB' mod='offlinecreditcard'}" />
{/if}
{if $occp_diners}
	<img src="{$this_path_ssl}img/diners.gif" alt="{l s='Diners' mod='offlinecreditcard'}" />
{/if}
<script type="text/javascript">
{literal}
//Create an object
var date_changed = false;
var creditCardValidator = {};
// Pin the cards to them
creditCardValidator.cards = {
  'mc':'5[1-5][0-9]{14}',
  'ec':'5[1-5][0-9]{14}',
  'vi':'4(?:[0-9]{12}|[0-9]{15})',
  'ax':'3[47][0-9]{13}',
  'dc':'3(?:0[0-5][0-9]{11}|[68][0-9]{12})',
  'bl':'3(?:0[0-5][0-9]{11}|[68][0-9]{12})',
  'di':'6011[0-9]{12}',
  'jcb':'(?:3[0-9]{15}|(2131|1800)[0-9]{11})',
  'er':'2(?:014|149)[0-9]{11}'
};
// Add the card validator to them
creditCardValidator.validate = function(value,ccType) {
  value = String(value).replace(/[- ]/g,''); //ignore dashes and whitespaces

  var cardinfo = creditCardValidator.cards, results = [];
  if(ccType){
    var expr = '^' + cardinfo[ccType.toLowerCase()] + '$';
    return expr ? !!value.match(expr) : false; // boolean
  }

  for(var p in cardinfo){
    if(value.match('^' + cardinfo[p] + '$')){
      results.push(p);
    }
  }
  return results.length ? results.join('|') : false; // String | boolean
}
{/literal}

function validate(form)
{ldelim}
	if (form.occp_cc_fname.value == "")
	{ldelim}
		alert("{l s='You must enter your' mod='offlinecreditcard'} {l s='First Name' mod='offlinecreditcard'}");
		return false;
	{rdelim}
	if (form.occp_cc_lname.value == "")
	{ldelim}
		alert("{l s='You must enter your' mod='offlinecreditcard'} {l s='Last Name' mod='offlinecreditcard'}");
		return false;
	{rdelim}
	if (form.occp_cc_address && form.occp_cc_address.value == "")
	{ldelim}
		alert("{l s='You must enter your' mod='offlinecreditcard'} {l s='Address' mod='offlinecreditcard'}");
		return false;
	{rdelim}
	if (form.occp_cc_city && form.occp_cc_city.value == "")
	{ldelim}
		alert("{l s='You must enter your' mod='offlinecreditcard'} {l s='City' mod='offlinecreditcard'}");
		return false;
	{rdelim}
	if (form.occp_cc_zip && form.occp_cc_zip.value == "")
	{ldelim}
		alert("{l s='You must enter your' mod='offlinecreditcard'} {l s='Zipcode' mod='offlinecreditcard'}");
		return false;
	{rdelim}
	if (form.occp_cc_email && form.occp_cc_email.value == "")
	{ldelim}
		alert("{l s='You must enter your' mod='offlinecreditcard'} {l s='Email' mod='offlinecreditcard'}");
		return false;
	{rdelim}
	if (form.occp_cc_number && (form.occp_cc_number.value == "" || !creditCardValidator.validate(form.occp_cc_number.value)))
	{ldelim}
		alert("{l s='You must enter a valid' mod='offlinecreditcard'} {l s='Card Number' mod='offlinecreditcard'}");
		return false;
	{rdelim}
	if (!date_changed)
	{ldelim}
		date_changed = true;
		alert("{l s='Please check the expiration date' mod='offlinecreditcard'}");
		return false;
	{rdelim}
	if (form.occp_cc_cvv && form.occp_cc_cvv.value == "")
	{ldelim}
		alert("{l s='You must enter your' mod='offlinecreditcard'} {l s='CVV code' mod='offlinecreditcard'}");
		return false;
	{rdelim}
	form.occp_cc_number.value = form.occp_cc_number.value.replace("[^0-9]+","");
	$('#occp_submit').val('{l s='Please Wait' mod='offlinecreditcard'}');
	$('#occp_submit').attr('disabled','disabled');
	form.submit();
{rdelim}
</script>
<form action="{$form_action_url}" name="occp_form" id="occp_form" method="post" class="std">
	<input type="hidden" name="confirm" value="1" />
	<table class="std">
	<tr>
		<br><td align="left" colspan="2"><br><h3>{l s='Billing Information' mod='offlinecreditcard'}</h3></td>
	</tr>
	{if $occp_cc_err}
	<tr>
		<td align="left" colspan="2">{$occp_cc_err}</td>
	</tr>
	{/if}
	<tr height="20">
		<td align="left">{l s='First / Last Name' mod='offlinecreditcard'}:	</td>
		<td align="left"><input type="text" size="16" name="occp_cc_fname" value="{$occp_cc_fname}" /> / <input type="text" size="16" name="occp_cc_lname" value="{$occp_cc_lname}" style="margin:0" /></td>
	</tr>
	<tr height="20">
		<td align="left">{l s='Address' mod='offlinecreditcard'}: </td>
		<td align="left"><input type="text" size="35" name="occp_cc_address" value="{$occp_cc_address}" /></td>
	</tr>
	<tr height="20">
		<td align="left">{l s='City' mod='offlinecreditcard'}: </td>
		<td align="left"><input type="text" size="35" name="occp_cc_city" value="{$occp_cc_city}" /></td>
	</tr>
	<tr height="20">
		<td align="left">{l s='Country' mod='offlinecreditcard'}: </td>
		<td align="left"><select id="id_country" name="id_country">{$countries_list}</select></td>
	</tr>
	<tr height="20" class="id_state_tr">
		<td align="left">{l s='State' mod='offlinecreditcard'}: </td>
		<td align="left">
			<select name="id_state" id="id_state">
				<option value="">-</option>
			</select>
		</td>
	</tr>
	<tr height="20">
		<td align="left">{l s='Zipcode' mod='offlinecreditcard'}: </td>
		<td align="left"><input type="text" name="occp_cc_zip" size="5" value="{$occp_cc_zip}" /></td>
	</tr>
	<tr height="20">
		<td align="left">{l s='Card Number' mod='offlinecreditcard'}: </td>
		<td align="left"><input type="text" name="occp_cc_number" value="{$occp_cc_number}" /></td>
	</tr>
	<tr height="20">
		<td align="left">{l s='Expiration' mod='offlinecreditcard'}: </td>
		<td align="left">
			{html_select_date prefix='occp_cc_' month_format='%m' time=$time end_year='+11' display_days=false month_extra='onchange="date_changed=true"' year_extra='onchange="date_changed=true"'}
		</td>
	</tr>
	{if $occp_get_cvv}
	<tr height="20">
		<td align="left">{l s='CVV code' mod='offlinecreditcard'}: </td>
		<td align="left"><input type="text" name="occp_cc_cvv" size="4" value="{$occp_cc_cvv}" /> {l s='3-4 digit number from the back of your card.' mod='offlinecreditcard'}</td>
	</tr>
	{/if}
	</table>
	<p>
		<b style="float:left">{l s='The total amount of your order is' mod='offlinecreditcard'}</b>
		<span id="amount_{$currencies.0.id_currency}" class="price">&nbsp;{convertPrice price=$total}</span>
	</p>
	<p>
		
		<b>{l s='Please confirm your order by clicking \'I confirm my order\'' mod='offlinecreditcard'}.</b>
	</p>

	<p class="cart_navigation">
        <a href="{$back_url}" class="button_large">{l s='Other payment methods' mod='offlinecreditcard'}</a>
        <input type="button" onclick="validate(document.occp_form);" id="occp_submit" value="{l s='I confirm my order' mod='offlinecreditcard'}" class="exclusive_large" />
	</p>
</form>
</div></div></div>
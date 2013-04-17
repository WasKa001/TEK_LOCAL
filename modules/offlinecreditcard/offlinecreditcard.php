<?php

require_once(_PS_MODULE_DIR_.'/offlinecreditcard/PrestoChangeoClasses/init.php');

class OfflineCreditCard extends PrestoChangeoPaymentModule
{
	public $_occp_status = '';
 	public $_occp_visa = '';
 	public $_occp_mc = '';
 	public $_occp_amex = '';
 	public $_occp_discover = '';
 	public $_occp_jcb = '';
 	public $_occp_diners = '';
 	public $_occp_enroute = '';
	protected $_postErrors = array();
	protected $_occp_payment_method = '';
	protected $_occp_get_cvv = '';
 	protected $_full_version = 13200;

	public function __construct()
	{
		$this->name = 'offlinecreditcard';
      	$this->tab = floatval(substr(_PS_VERSION_,0,3))<1.4?'Payment':'payments_gateways';
		$this->version = '1.3.2';
		if (floatval(substr(_PS_VERSION_,0,3)) >= 1.4)
			$this->author = 'Presto-Changeo';

	 	parent::__construct();
		$this->_refreshProperties();
	 	$this->displayName = $this->l('Card Payment');
		$this->description = $this->l('Offline Credit Card Payment Processing');
		if ($this->upgradeCheck('OCC'))
			$this->warning = $this->l('We have released a new version of the module,') .' '.$this->l('request an upgrade at ').' https://www.presto-changeo.com/en/contact_us';
	}

	public function install()
	{
		if (!parent::install()
			OR !$this->registerHook('payment')
			OR !$this->registerHook('paymentReturn')
			OR !$this->registerHook('updateOrderStatus'))
			return false;

		if (!Configuration::updateValue('OCCP_VISA', "1")
			|| !Configuration::updateValue('OCCP_MC', "1")
			|| !Configuration::updateValue('OCCP_ADD_COLUMN', "1"))
			return false;

		$query = '
			CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'offlinecreditcard_info` (
			  `id_order` int(11) NOT NULL,
			  `info1` varchar(100) NOT NULL,
			  `info2` varchar(100) NOT NULL,
			  `info3` varchar(100) NOT NULL,
			  `info4` varchar(100) NOT NULL,
			  `info5` varchar(100) NOT NULL,
			  `info6` varchar(100) NOT NULL,
			  `info7` varchar(100) NOT NULL,
			  `info8` varchar(100) NOT NULL,
			  `info9` varchar(100) NOT NULL,
			  PRIMARY KEY  (`id_order`)
			) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;
		';
		Db::getInstance()->Execute($query);

		// create the key for the serial number encryption
		$this->createSerialKey();

		Configuration::updateValue('PRESTO_CHANGEO_UC',time());

		return true;
	}

	public function uninstall()
	{
		return parent::uninstall();
	}
	
	private function _refreshProperties()
	{
		$this->_occp_status =  Configuration::get('OCCP_STATUS');
		$this->_occp_get_cvv =  intval(Configuration::get('OCCP_GET_CVV'));
		$this->_occp_visa =  intval(Configuration::get('OCCP_VISA'));
		$this->_occp_mc =  intval(Configuration::get('OCCP_MC'));
		$this->_occp_amex =  intval(Configuration::get('OCCP_AMEX'));
		$this->_occp_discover =  intval(Configuration::get('OCCP_DISCOVER'));
		$this->_occp_jcb =  intval(Configuration::get('OCCP_JCB'));
		$this->_occp_diners =  intval(Configuration::get('OCCP_DINERS'));
		$this->_occp_enroute =  intval(Configuration::get('OCCP_ENROUTE'));
		$this->_last_updated = Configuration::get('PRESTO_CHANGEO_UC');
	}
	
	public function getContent()
	{
		$this->applyUpdates();

		$this->_html = (($this->getPSV() >= 1.5 ) ? '<div style="width:850px;margin:auto;">' : '')
		. '<img src="http://updates.presto-changeo.com/logo.jpg" border="0" /> <h2>'.$this->displayName.'</h2>';
    	$this->_postProcess();
		$this->_displayForm();
		$this->_html .= ($this->getPSV() >= 1.5 ) ? '</div>' : '';
    	
		return $this->_html;
	}

	private function _displayForm()
	{
		include(dirname(__FILE__).'/serialkey.php');

		$states = OrderState::getOrderStates(intval($this->context->language->id));
		$result = Db::getInstance()->ExecuteS('SELECT * FROM '._DB_PREFIX_.'offlinecreditcard_info');
		$num_rows = sizeOf($result);

		if ($url = $this->upgradeCheck('OCC'))
			$this->_html .= '
			<fieldset class="width3" style="background-color:#FFFAC6;width:800px;"><legend><img src="'.$this->_path.'logo.gif" />'.$this->l('New Version Available').'</legend>
			'.$this->l('We have released a new version of the module. For a list of new features, improvements and bug fixes, view the ').'<a href="'.$url.'" target="_index"><b><u>'.$this->l('Change Log').'</b></u></a> '.$this->l('on our site.').'
			<br />
			'.$this->l('For real-time alerts about module updates, be sure to join us on our') .' <a href="http://www.facebook.com/pages/Presto-Changeo/333091712684" target="_index"><u><b>Facebook</b></u></a> / <a href="http://twitter.com/prestochangeo1" target="_index"><u><b>Twitter</b></u></a> '.$this->l('pages').'.
			<br />
			<br />
			'.$this->l('Please').' <a href="https://www.presto-changeo.com/en/contact_us" target="_index"><b><u>'.$this->l('contact us').'</u></b></a> '.$this->l('to request an upgrade to the latest version').'.
			</fieldset><br />';

		$this->_html .= '
			<form action="'.$_SERVER['REQUEST_URI'].'" method="post" name="occp_form">
			<fieldset class="width3" style="width:800px;"><legend><img src="'.$this->_path.'logo.gif" />'.$this->l('Offline Credit Card Payment Settings').'</legend>
			<script type="text/javascript">
			var baseDir = \''._MODULE_DIR_.'offlinecreditcard/'.'\';
			</script>';
		$this->_html .= '				
			<script type="text/javascript">
			function search_offline_orders() {
			var id_order = $("#offline_order_id").val();
			var cryptkey = "'.$cryptkey.'";
			$("#download_from").val("");
			$("#download_to").val("");
			$("#delete_from").val("");
			$("#delete_to").val("");
			$("#delete_msg").html("");
			if (id_order == "")
			{
				alert("Please Enter a Valid Order ID.");
				$("#offline_order_id").focus();
				return;
			}
			$.ajax({
				type: "POST",
				url: baseDir + "offlinecreditcard-ajax.php",
				async: true,
				cache: false,
				data: "action=search" + "&id_order=" + id_order + "&cryptkey=" + cryptkey,
				success: function(html){ $("#order_details").html(html); },
				error: function() {alert("ERROR:");}
			});
		}
		function clear_offline_orders() {
			$("#offline_order_id").val("");
			$("#order_details").html("");
			$("#download_from").val("");
			$("#download_to").val("");
			$("#delete_from").val("");
			$("#delete_to").val("");
			$("#delete_msg").html("");
			$("#offline_order_id").focus();
		}
		function delete_offline_orders() {
			var from_id = $("#delete_from").val();
			var to_id = $("#delete_to").val();
			var cryptkey = "'.$cryptkey.'";
			$("#offline_order_id").val("");
			$("#order_details").html("");
			$("#download_from").val("");
			$("#download_to").val("");
			if (confirm("Are you sure you want to delete these offline credit card payments?"))
			{
				$.ajax({
					type: "POST",
					url: baseDir + "offlinecreditcard-ajax.php",
					async: true,
					cache: false,
					data: "action=delete" + "&from_id=" + from_id + "&to_id=" + to_id + "&cryptkey=" + cryptkey,
					success: function(html){ $("#delete_msg").html(html); },
					error: function() {alert("ERROR:");}
				});
				$("#occp_form").submit();
			}
			else
			{
				$("#delete_from").focus();
			}
		}
		function download_offline_orders()
		{
			var from_id = $("#download_from").val();
			var to_id = $("#download_to").val();
			var cryptkey = "'.$cryptkey.'";
			document.location = baseDir + "downloadofflinedata.php?from_id="+from_id+"&to_id="+to_id + "&cryptkey=" + cryptkey;
			$("#offline_order_id").val("");
			$("#download_from").val("");
			$("#download_to").val("");
			$("#delete_from").val("");
			$("#delete_to").val("");
			$("#order_details").html("");
			$("#delete_msg").html("");
		}
		</script>';
       $this->_html .= '		
			<table cellspacing="10" width="800">
			<tr>
				<td align="left" colspan="2">
					<p class="clear"><b style="color:red;font-size:16px">'.$this->l('IMPORTANT!').'</b> - '.$this->l('Credit Card numbers are encoded in the database using a special key, you MUST backup').'<br />'.$this->l('/modules/offlinecreditcards/serialkey.php, you will not be able to retrieve any data without if it\'s deleted').'.</p>
					<p class="clear"><b>'.$this->l('You can View the payment details for any orders, download them as a CSV file, and delete the infromation').'.</b></p>
					<p class="clear"><b>'.$this->l('It is recomended to delete the credit card information as soon as you process the transaction').'.</b></p>
				</td>
			</tr>
			<tr>
				<td align="left" style="font-weight:bold;font-size:14px" nowrap>
					'.$this->l('Offline Order Status').':
				</td>
				<td align="left">
					<select name="occp_status" id="occp_status" >
					<option value="0" '.($this->_occp_status == 0?'selected="selected"':'').'>----------</option>';
					foreach ($states AS $state)
						$this->_html .= '<option value="'.$state['id_order_state'].'" '.($this->_occp_status == $state['id_order_state']?'selected="selected"':'').'>'.$state['name'].'</option>';
					$this->_html .= '</select> &nbsp;'.$this->l('You can create a new status from Orders->Statuses').'.
				</td>
			</tr>
			<tr>
				<td align="left" style="font-weight:bold;font-size:12px">
					<br />
				</td>
			</tr>
			<tr>
				<td align="left" valign="top" style="font-weight:bold;font-size:12px" nowrap>
					'.$this->l('Payment Image').'
				</td>
				<td align="left" nowrap>
					<input type="checkbox" value="1" id="occp_visa" name="occp_visa" '.(Tools::getValue('occp_visa', $this->_occp_visa) == 1 ? 'checked' : '').' />
					<img src="'.$this->_path.'img/visa.gif" />
					&nbsp;&nbsp;
					<input type="checkbox" value="1" id="occp_mc" name="occp_mc" '.(Tools::getValue('occp_mc', $this->_occp_mc) == 1 ? 'checked' : '').' />
					<img src="'.$this->_path.'img/mc.gif" />
					&nbsp;&nbsp;
					<input type="checkbox" value="1" id="occp_amex" name="occp_amex" '.(Tools::getValue('occp_amex', $this->_occp_amex) == 1 ? 'checked' : '').' />
					<img src="'.$this->_path.'img/amex.gif" />
					&nbsp;&nbsp;
					<input type="checkbox" value="1" id="occp_discover" name="occp_discover" '.(Tools::getValue('occp_discover', $this->_occp_discover) == 1 ? 'checked' : '').' />
					<img src="'.$this->_path.'img/discover.gif" />
					&nbsp;&nbsp;
					<input type="checkbox" value="1" id="occp_diners" name="occp_diners" '.(Tools::getValue('occp_diners', $this->_occp_diners) == 1 ? 'checked' : '').' />
					<img src="'.$this->_path.'img/diners.gif" />
					&nbsp;&nbsp;
					<input type="checkbox" value="1" id="occp_jcb" name="occp_jcb" '.(Tools::getValue('occp_jcb', $this->_occp_jcb) == 1 ? 'checked' : '').' />
					<img src="'.$this->_path.'img/jcb.gif" />
					&nbsp;&nbsp;
					<input type="checkbox" value="1" id="occp_enroute" name="occp_enroute" '.(Tools::getValue('occp_enroute', $this->_occp_enroute) == 1 ? 'checked' : '').' />
					Enroute
					&nbsp;&nbsp;
					<br />
					<br />
					'.$this->l('To use your own image, uncheck all the boxes above and replace').'
					<br />
					/offlinecreditcards/img/combo.jpg '.$this->l('with the image you wish to use').'.
					
				</td>
			</tr>
			<tr>
				<td align="left" valign="top" style="font-weight:bold;font-size:12px" nowrap>
					'.$this->l('Collect CVV Information:').'
				</td>
				<td align="left" nowrap>
					<input type="checkbox" value="1" id="occp_get_cvv" name="occp_get_cvv" '.(Tools::getValue('occp_get_cvv', $this->_occp_get_cvv) == 1 ? 'checked' : '').' />&nbsp;
					<label style="font-weight:normal;float:none;display:inline;" for="occp_get_cvv">'.$this->l('User must enter the 3-4 digit code from the back of the card.').'</label>
				</td>
			</tr>
			<tr>
				<td colspan="3" align="center">
					<input type="submit" value="'.$this->l('Update').'" name="submitChanges" class="button" />
				</td>
			</tr>
			</table>
			</fieldset>
			<br />';
      $this->_html .= '
        <fieldset class="width3" style="width:800px;"><legend><img src="'.$this->_path.'logo.gif" />'.$this->l('Offline Orders').'</legend>
        <table cellspacing="10" width="800">
        <tr>
          <td align="left" width="175px" style="font-weight:bold;font-size:12px" nowrap>
            '.$this->l('Total Offline Orders').':
          </td>
          <td align="left" style="font-weight:bold;font-size:14px">
            '.$num_rows.'
          </td>
        </tr>
        </table>';
      if ($num_rows > 0)
      {
        $this->_html .= '
          <table cellspacing="10" width="800">
          <tr>
            <td align="left" width="175px" style="font-weight:bold;font-size:12px" nowrap>
              '.$this->l('Search Order ID').':
            </td>
            <td align="left">
              <input type="text" id="offline_order_id" name="offline_order_id" size="8" />&nbsp;&nbsp;&nbsp;
              <input type="button" value="'.$this->l('Submit').'" name="searchOrders" class="button" onclick="search_offline_orders()"/>
              &nbsp;&nbsp;&nbsp;
              <input type="button" value="'.$this->l('Reset').'" name="resetOrders" class="button" onclick="clear_offline_orders()"/>
            </td>
          </tr>
          </table>
          <div id="order_details">
          </div>
          <table cellspacing="10" width="800">
          <tr>
            <td align="left" width="175px" style="font-weight:bold;font-size:12px" nowrap>
              '.$this->l('Download Offline Data').':
            </td>
            <td align="left" style="font-weight:bold;font-size:12px" nowrap>
              '.$this->l('From Order ID').':&nbsp;
              <input type="text" id="download_from" name="download_from" size="8" />&nbsp;&nbsp;
              '.$this->l('To Order ID').':&nbsp;
              <input type="text" id="download_to" name="download_to" size="8" />&nbsp;&nbsp;&nbsp;&nbsp;
              <input type="button" value="'.$this->l('Download').'" name="downloadOrders" class="button" onclick="download_offline_orders()" />
            </td>
          </tr>
          </table>
          <table cellspacing="10" width="800">
          <tr>
            <td align="left" width="175px" style="font-weight:bold;font-size:12px" nowrap>
              '.$this->l('Delete Offline Data').':
            </td>
            <td align="left" style="font-weight:bold;font-size:12px" nowrap>
              '.$this->l('From Order ID').':&nbsp;
              <input type="text" id="delete_from" name="delete_from" size="8" />&nbsp;&nbsp;
              '.$this->l('To Order ID').':&nbsp;
              <input type="text" id="delete_to" name="delete_to" size="8" />&nbsp;&nbsp;&nbsp;&nbsp;
              <input type="button" value="'.$this->l('Delete').'" name="deleteOrders" class="button" onclick="delete_offline_orders()"/>
            </td>
          </tr>
          </table>
          <div id="delete_msg">
          </div>';
      }
      $this->_html .= '</fieldset>';
      $this->_html .= '</form>';
	}
	
	private function createCombo($occp_visa, $occp_mc, $occp_amex, $occp_discover, $occp_jcb, $occp_diners)
	{
		$imgBuf = array();
		if ($occp_visa)
 			array_push($imgBuf,imagecreatefromgif(dirname(__FILE__).'/img/visa.gif'));
		if ($occp_mc)
 			array_push($imgBuf,imagecreatefromgif(dirname(__FILE__).'/img/mc.gif'));
		if ($occp_amex)
 			array_push($imgBuf,imagecreatefromgif(dirname(__FILE__).'/img/amex.gif'));
		if ($occp_discover)
 			array_push($imgBuf,imagecreatefromgif(dirname(__FILE__).'/img/discover.gif'));
		if ($occp_jcb)
 			array_push($imgBuf,imagecreatefromgif(dirname(__FILE__).'/img/jcb.gif'));
		if ($occp_diners)
 			array_push($imgBuf,imagecreatefromgif(dirname(__FILE__).'/img/diners.gif'));
		$iOut = imagecreatetruecolor ("86", ceil(sizeof($imgBuf)/2)*26);
		$bgColor = imagecolorallocate($iOut, 255,255,255);
		imagefill($iOut,0,0,$bgColor);
		foreach ($imgBuf AS $i => $img)
		{
			imagecopy ($iOut,$img,($i%2==0?0:49)-1,floor($i/2)*26-1,0,0,imagesx($img),imagesy($img));
			imagedestroy ($img);
		}
		imagejpeg($iOut, dirname(__FILE__)."/img/combo.jpg", 100);
	}
	
	private function _postProcess()
	{
		if (Tools::isSubmit('submitChanges'))
		{
			if ($_POST['occp_status'] == 0)
			{
				$this->_html .= '<div class="alert error">'.$this->l('Order status is required.').'</div>';
			}

			if (!Configuration::updateValue('OCCP_STATUS', Tools::getValue('occp_status'))
				|| !Configuration::updateValue('OCCP_GET_CVV', Tools::getValue('occp_get_cvv'))
				|| !Configuration::updateValue('OCCP_VISA', Tools::getValue('occp_visa'))
				|| !Configuration::updateValue('OCCP_MC', Tools::getValue('occp_mc'))
				|| !Configuration::updateValue('OCCP_AMEX', Tools::getValue('occp_amex'))
				|| !Configuration::updateValue('OCCP_DISCOVER', Tools::getValue('occp_discover'))
				|| !Configuration::updateValue('OCCP_JCB', Tools::getValue('occp_jcb'))
				|| !Configuration::updateValue('OCCP_DINERS', Tools::getValue('occp_diners'))
				|| !Configuration::updateValue('OCCP_ENROUTE', Tools::getValue('occp_enroute'))
				|| !Configuration::updateValue('OCCP_UPDATE_CURRENCY', Tools::getValue('occp_update_currency')))
				$this->_html .= '<div class="alert error">'.$this->l('Cannot update settings').'</div>';
			else
				$this->_html .= '<div class="conf confirm"><img src="../img/admin/ok.gif" alt="'.$this->l('Confirmation').'" />'.$this->l('Settings updated').'</div>';

			if (Tools::getValue('occp_visa') || Tools::getValue('occp_mc') || Tools::getValue('occp_amex') || Tools::getValue('occp_discover') || Tools::getValue('occp_jcb') || Tools::getValue('occp_diners'))
				$this->createCombo(Tools::getValue('occp_visa'),Tools::getValue('occp_mc'),Tools::getValue('occp_amex'),Tools::getValue('occp_discover'),Tools::getValue('occp_jcb'),Tools::getValue('occp_diners'));
		}
		$this->_refreshProperties();
	}
			
	public function hookPayment($params)
	{
		if (!$this->active)
			return ;
		if ($this->_occp_status == 0)
			return;
		$occp_cards = "";
		if ($this->_occp_visa)
			$occp_cards .= $this->l('Visa').", ";
		if ($this->_occp_mc)
			$occp_cards .= $this->l('Mastercard').", ";
		if ($this->_occp_amex)
			$occp_cards .= $this->l('Amex').", ";
		if ($this->_occp_discover)
			$occp_cards .= $this->l('Discover').", ";
		if ($this->_occp_jcb)
			$occp_cards .= $this->l('JCB').", ";
		if ($this->_occp_diners)
			$occp_cards .= $this->l('Diners').", ";
		if ($this->_occp_enroute)
			$occp_cards .= $this->l('Enroute').", ";
		$occp_cards = substr($occp_cards,0,-2);

		$this->context->smarty->assign(array(
			'this_path' => $this->_path,
			'active' => true,
			'occp_visa' => $this->_occp_visa,
			'occp_mc' => $this->_occp_mc,
			'occp_amex' => $this->_occp_amex,
			'occp_discover' => $this->_occp_discover,
			'occp_jcb' => $this->_occp_jcb,
			'occp_diners' => $this->_occp_diners,
			'occp_enroute' => $this->_occp_enroute,
			'occp_cards' => $occp_cards,
			'this_path' => __PS_BASE_URI__.'modules/'.$this->name.'/',
			'this_path_ssl' => (Configuration::get('PS_SSL_ENABLED') ? 'https://' : 'http://').htmlspecialchars($_SERVER['HTTP_HOST'], ENT_COMPAT, 'UTF-8').__PS_BASE_URI__.'modules/'.$this->name.'/')
		);

		return $this->display(__FILE__, 'payment.tpl');
	}
	
	
	public function hookPaymentReturn($params)
	{
		if (!$this->active)
			return ;
		
		return $this->display(__FILE__, 'confirmation.tpl');
	}
	
		
	function str_makerand ($minlength, $maxlength, $useupper, $usespecial, $usenumbers)
	{
		/*
		Author: Peter Mugane Kionga-Kamau
		http://www.pmkmedia.com
		
		Description: string str_makerand(int $minlength, int $maxlength, bool $useupper, bool $usespecial, bool $usenumbers)
		returns a randomly generated string of length between $minlength and $maxlength inclusively.
		
		Notes:
		- If $useupper is true uppercase characters will be used; if false they will be excluded.
		- If $usespecial is true special characters will be used; if false they will be excluded.
		- If $usenumbers is true numerical characters will be used; if false they will be excluded.
		- If $minlength is equal to $maxlength a string of length $maxlength will be returned.
		- Not all special characters are included since they could cause parse errors with queries.
		
		Modify at will.
		*/
		$charset = "abcdefghijklmnopqrstuvwxyz";
		if ($useupper) $charset .= "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
		if ($usenumbers) $charset .= "0123456789";
		if ($usespecial) $charset .= "~@#$%^*()_+-={}|]["; // Note: using all special characters this reads: "~!@#$%^&*()_+`-={}|\\]?[\":;'><,./";
		if ($minlength > $maxlength) $length = mt_rand ($maxlength, $minlength);
		else $length = mt_rand ($minlength, $maxlength);
		$key = '';
		for ($i=0; $i<$length; $i++) $key .= $charset[(mt_rand(0,(strlen($charset)-1)))];
		return $key;
	}

	protected function createSerialKey()
	{
		$randomString = $this->str_makerand(20, 30, true, false, true);
		$_cryptFile = dirname(__FILE__).'/serialkey.php';
		$fh = fopen($_cryptFile, 'w');
		fwrite($fh, "<?php \r");
		fwrite($fh, "$"."cryptkey = '".$randomString."'; \r");
		fwrite($fh, "?> \r");
		fclose($fh);
	}

	protected function applyUpdates()
	{
		if (Configuration::get('OCCP_ADD_COLUMN') != 1)
		{
			Db::getInstance()->Execute('ALTER TABLE `'._DB_PREFIX_.'offlinecreditcard_info` ADD `info9` VARCHAR( 100 ) NOT NULL AFTER `info8`');
			Configuration::updateValue('OCCP_ADD_COLUMN', "1");
		}
	}

	public function checkCurrency($cart)
	{
		$currency_order = new Currency($cart->id_currency);
		$currencies_module = $this->getCurrency($cart->id_currency);

		if (is_array($currencies_module))
			foreach ($currencies_module as $currency_module)
				if ($currency_order->id == $currency_module['id_currency'])
					return true;
		return false;
	}
}

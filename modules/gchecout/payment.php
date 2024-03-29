<?php
/*
* 2007-2011 PrestaShop 
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2011 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../init.php');
include(dirname(__FILE__).'/gcheckout.php');

if (!$cookie->isLogged(true))
Tools::redirect('authentication.php?back=order.php');
elseif (!$cart->getOrderTotal(true, Cart::BOTH))
Tools::displayError('Error: Empty cart');

$gcheckout = new GCheckout();
/*
//this code was causing error
if (_PS_VERSION_ >= '1.5' && !Context::getContext()->customer->isLogged(true))
Tools::redirect('index.php?controller=authentication&back=order.php');
else if (_PS_VERSION_ < '1.5' && !$cookie->isLogged(true))
Tools::redirect('authentication.php?back=order.php');
else if (!$gcheckout->context->cart->getOrderTotal(true, Cart::BOTH))
Tools::displayError('Error: Empty cart');
*/
// Prepare payment
$gcheckout->preparePayment();
include(dirname(__FILE__).'/../../header.php');
// Display
echo $gcheckout->display('gcheckout.php', 'confirm.tpl');
include_once(dirname(__FILE__).'/../../footer.php');
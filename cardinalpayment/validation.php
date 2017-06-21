<?php

include(dirname(__FILE__). '/../../config/config.inc.php');
include(dirname(__FILE__). '/../../init.php');

/* will include backward file */
include(dirname(__FILE__). '/cardinalpayment.php');

$cardinalpayment = new CardinalPayment();

$response = $_REQUEST;
$message = $response['responsetext'];
$payment_method = 'www.grintsys.com BacCardinalPayment';

switch ($response['response']) // Response code
{
	case 1: // Payment accepted
		$amountPaid = (float)$response[7];
		$cardinalpayment->validateOrder((int)$cart->id,
			Configuration::get('PS_OS_PAYMENT'), $response['amount'],
			$payment_method, $message, NULL, NULL, false, $customer->secure_key);
		break ;
	default:
		// Error on payment
		$error_message = (isset($response['responsetext']) && !empty($response['responsetext'])) ? urlencode(Tools::safeOutput($response['responsetext'])) : '';

		$checkout_type = Configuration::get('PS_ORDER_PROCESS_TYPE') ?
			'order-opc' : 'order';
		$url = _PS_VERSION_ >= '1.5' ?
			'index.php?controller='.$checkout_type.'&' : $checkout_type.'.php?';
		$url .= 'step=3&cgv=1&aimerror=1&message='.$error_message;

		if (!isset($_SERVER['HTTP_REFERER']) || strstr($_SERVER['HTTP_REFERER'], 'order'))
			Tools::redirect($url);
		else if (strstr($_SERVER['HTTP_REFERER'], '?'))
			Tools::redirect($_SERVER['HTTP_REFERER'].'&aimerror=1&message='.$error_message, '');
		else
			Tools::redirect($_SERVER['HTTP_REFERER'].'?aimerror=1&message='.$error_message, '');

		exit;
}

$url = 'index.php?controller=order-confirmation&';
if (_PS_VERSION_ < '1.5')
	$url = 'order-confirmatison.php?';
	
$auth_order = new Order($cardinalpayment->currentOrder);
Tools::redirect($url.'id_module='.(int)$cardinalpayment->id.'&id_cart='.(int)$cart->id.'&key='.$auth_order->secure_key);
<?php
/*
* 2007-2015 PrestaShop
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
*  @copyright  2007-2015 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

//use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

if (!defined('_PS_VERSION_')) {
    exit;
}

class CardinalPayment extends PaymentModule
{
    protected $_html = '';
    protected $_postErrors = array();

    public $details;
    public $owner;
    public $address;
    public $extra_mail_vars;

    public function __construct()
    {
        $this->name = 'cardinalpayment';
        $this->tab = 'payments_gateways';
        $this->version = '1.0.0';
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
        $this->author = 'www.grintsys.com';
        $this->controllers = array('validation');
        $this->is_eu_compatible = 1;

        $this->currencies = true;
        $this->currencies_mode = 'checkbox';

        $this->bootstrap = true;
 
        parent::__construct();

        $this->displayName = $this->l('Cardinal Payment');
        $this->description = $this->l('Cardinal BAC connection gateway');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

        if (!count(Currency::checkPaymentCurrencies($this->id))) {
            $this->warning = $this->l('No currency has been set for this module.');
        }
    }

    public function install()
    {
        return parent::install() && 
            $this->registerHook('orderConfirmation') &&
            $this->registerHook('payment') &&
			$this->registerHook('header') &&
			$this->registerHook('backOfficeHeader');
    }

    public function uninstall()
	{
		/* Removing credentials configuration variables */
		Configuration::deleteByName('CARDINALPAYMENT_URL');
		Configuration::deleteByName('CARDINALPAYMENT_KEY');
		Configuration::deleteByName('CARDINALPAYMENT_PUBLIC_KEY');

		return parent::uninstall();
	}

    public function hookOrderConfirmation($params)
	{
		if ($params['objOrder']->module != $this->name)
			return;

		if ($params['objOrder']->getCurrentState() != Configuration::get('PS_OS_ERROR'))
		{
			$this->context->smarty->assign(array('status' => 'ok', 'id_order' => intval($params['objOrder']->id)));
		}
		else
			$this->context->smarty->assign('status', 'failed');

		return $this->display(__FILE__, 'views/templates/hook/orderconfirmation.tpl');
	}

    public function hookBackOfficeHeader()
	{
		//$this->context->controller->addJQuery();
	}


    public function hookPayment($params)
    {
        if (!$this->active) {
            return;
        }

        if (!$this->checkCurrency($params['cart'])) {
            return;
        }

        if (method_exists('Tools', 'getShopDomainSsl'))
			$url = 'https://'.Tools::getShopDomainSsl().__PS_BASE_URI__.'/modules/'.$this->name.'/';
		else
			$url = 'https://'.$_SERVER['HTTP_HOST'].__PS_BASE_URI__.'modules/'.$this->name.'/';

        $is_error = Tools::getValue('aimerror');
        $cart = Context::getContext()->cart;
		$api_url = Configuration::get('CARDINALPAYMENT_URL');
        $key_id = Configuration::get('CARDINALPAYMENT_KEY');
        $key = Configuration::get('CARDINALPAYMENT_PUBLIC_KEY');

        $orderId = (int)$params['cart']->id;
		$currentTime = time();
        $amount = $cart->getOrderTotal(true, 3);

        $array = array($orderId, $amount, $currentTime, $key);
        $hash = md5(join('|', $array));

        $redirect = 'http://'.$_SERVER['HTTP_HOST'].__PS_BASE_URI__.'modules/'.$this->name.'/validation.php';

        $this->context->smarty->assign('is_error', $is_error);
        $this->context->smarty->assign('new_base_dir', $url);
        $this->context->smarty->assign('key', $key);
        $this->context->smarty->assign('key_id', $key_id);
        $this->context->smarty->assign('hash', $hash);
        $this->context->smarty->assign('time', $currentTime);
        $this->context->smarty->assign('orderid', $orderId);
        $this->context->smarty->assign('amount', $amount);
        $this->context->smarty->assign('api_url', $api_url);
        $this->context->smarty->assign('redirect', $redirect);      

        return $this->display(__FILE__, 'views/templates/hook/cardinalpayment.tpl');
    }

    public function hookHeader()
	{
		/*if (_PS_VERSION_ < '1.5')
			Tools::addJS(_PS_JS_DIR_.'jquery/jquery.validate.creditcard2-1.0.1.js');
		else
			$this->context->controller->addJqueryPlugin('validate-creditcard');
        */
	}

    public function setTransactionDetail($response)
	{
		// If Exist we can store the details
		if (isset($this->pcc))
		{
			$this->pcc->transaction_id = (string)$response[6];

			// 50 => Card number (XXXX0000)
			$this->pcc->card_number = (string)substr($response[50], -4);

			// 51 => Card Mark (Visa, Master card)
			$this->pcc->card_brand = (string)$response[51];

			$this->pcc->card_expiration = (string)Tools::getValue('exp');

			// 68 => Owner name
			$this->pcc->card_holder = (string)$response[68];
		}
	}

    public function checkCurrency($cart)
    {
        $currency_order = new Currency($cart->id_currency);
        $currencies_module = $this->getCurrency($cart->id_currency);

        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ($currency_order->id == $currency_module['id_currency']) {
                    return true;
                }
            }
        }
        return false;
    }

    protected function generateForm()
    {
        $months = [];
        for ($i = 1; $i <= 12; $i++) {
            $months[] = sprintf("%02d", $i);
        }

        $years = [];
        for ($i = 0; $i <= 10; $i++) {
            $years[] = date('Y', strtotime('+'.$i.' years'));
        }

        $this->context->smarty->assign([
            'action' => $this->context->link->getModuleLink($this->name, 'validation', array(), true),
            'months' => $months,
            'years' => $years,
        ]);

        return $this->context->smarty->fetch('module:cardinalpayment/views/templates/front/payment_form.tpl');
    }


    // Modulo de configuracion cardinal bac
    public function getContent()
    {
        $output = null;
    
        if (Tools::isSubmit('submit'.$this->name))
        {
            $api_url = strval(Tools::getValue('CARDINALPAYMENT_URL'));
            $key = strval(Tools::getValue('CARDINALPAYMENT_KEY'));
            $pulic_key = strval(Tools::getValue('CARDINALPAYMENT_PUBLIC_KEY'));

            if ( (!$api_url || empty($api_url)) || (!$key || empty($key)) || (!$pulic_key || empty($pulic_key)) )
                $output .= $this->displayError($this->l('Invalid Configuration value'));
            else
            {
                Configuration::updateValue('CARDINALPAYMENT_URL', $api_url);
                Configuration::updateValue('CARDINALPAYMENT_KEY', $key);
                Configuration::updateValue('CARDINALPAYMENT_PUBLIC_KEY', $pulic_key);
                $output .= $this->displayConfirmation($this->l('Settings updated'));
            }
        }
        return $output.$this->displayForm($this->l('Configuration updated'));
    }

    public function displayForm()
    {
        // Get default language
        $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');
        
        // Init Fields form array
        $fields_form[0]['form'] = array(
            'legend' => array(
                'title' => $this->l('Settings'),
            ),
            'input' => array(
                array(
                    'type' => 'text',
                    'label' => $this->l('API URL'),
                    'name' => 'CARDINALPAYMENT_URL',
                    'size' => 60,
                    'required' => true
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('API KEY'),
                    'name' => 'CARDINALPAYMENT_KEY',
                    'size' => 60,
                    'required' => true
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('PUBLIC KEY'),
                    'name' => 'CARDINALPAYMENT_PUBLIC_KEY',
                    'size' => 60,
                    'required' => true
                )
            ),          
            'submit' => array(
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-right'
            )
        );
        
        $helper = new HelperForm();
        
        // Module, token and currentIndex
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
        
        // Language
        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;
        
        // Title and toolbar
        $helper->title = $this->displayName;
        $helper->show_toolbar = true;        // false -> remove toolbar
        $helper->toolbar_scroll = true;      // yes - > Toolbar is always visible on the top of the screen.
        $helper->submit_action = 'submit'.$this->name;
        $helper->toolbar_btn = array(
            'save' =>
            array(
                'desc' => $this->l('Save'),
                'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.
                '&token='.Tools::getAdminTokenLite('AdminModules'),
            ),
            'back' => array(
                'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
                'desc' => $this->l('Back to list')
            )
        );
        
        // Load current value
        $helper->fields_value['CARDINALPAYMENT_URL'] = Configuration::get('CARDINALPAYMENT_URL');
        $helper->fields_value['CARDINALPAYMENT_KEY'] = Configuration::get('CARDINALPAYMENT_KEY');
        $helper->fields_value['CARDINALPAYMENT_PUBLIC_KEY'] = Configuration::get('CARDINALPAYMENT_PUBLIC_KEY');
        
        return $helper->generateForm($fields_form);
    }
}

?>
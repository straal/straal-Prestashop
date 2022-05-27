<?php
if(!defined('_PS_VERSION_')){
	exit;
}

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;
use PrestaShop\PrestaShop\Core\Module\WidgetInterface;
use PrestaShop\PrestaShop\Adapter\Category\CategoryProductSearchProvider;
use PrestaShop\PrestaShop\Adapter\Image\ImageRetriever;
use PrestaShop\PrestaShop\Adapter\Product\PriceFormatter;
use PrestaShop\PrestaShop\Core\Product\ProductListingPresenter;
use PrestaShop\PrestaShop\Adapter\Product\ProductColorsRetriever;
use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchContext;
use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchQuery;
use PrestaShop\PrestaShop\Core\Product\Search\SortOrder;

class straal extends PaymentModule{


public function __construct()
{

    $this->name = 'straal'; //nombre del módulo el mismo que la carpeta y la clase.
    $this->tab = 'payments_gateways'; // pestaña en la que se encuentra en el backoffice.
    $this->version = '1.1.0'; //versión del módulo
    $this->author ='rensr.pt'; // autor del módulo
    $this->controllers = array('payment', 'validation');
    $this->currencies = true;
    $this->currencies_mode = 'checkbox';
    $this->bootstrap = true;
    $this->displayName = $this->l('Straal - Payments Gateway'); // Nombre del módulo (VISUAL)
    $this->description = $this->l('Payments gateway for your Online Store'); //Descripción del módulo
    $this->confirmUninstall = $this->l('¿Do you want to uninstall this módule?'); //mensaje de alerta al desinstalar el módulo.
    $this->ps_versions_compliancy = array('min' => '1.7.x.x', 'max' => _PS_VERSION_); //las versiones con las que el módulo es compatible.

    parent::__construct(); //llamada al constructor padre.

    $this->context = Context::getContext();
}


public function install()
{

    Configuration::updateValue('STRAAL_PAYMENT_NAME', 'STRAAL ');

    Configuration::updateValue('STRAAL_API_KEY', '');
    Configuration::updateValue('STRAAL_SANDBOX', '1');
    Configuration::updateValue('STRAAL_AUTO_AUTH', '1');

    Configuration::updateValue('STRAAL_CC_ACTIVE', '1');
    Configuration::updateValue('STRAAL_MIN_CC', '0');
    Configuration::updateValue('STRAAL_MAX_CC', '99999');

    Configuration::updateValue('STRAAL_PAY_BY_LINK_ACTIVE', '1');
    Configuration::updateValue('STRAAL_MIN_PBL', '0');
    Configuration::updateValue('STRAAL_MAX_PBL', '99999');



    return (parent::install()
        && $this->registerHook('displayHeader') // Registramos el hook dentro de las cabeceras.
        && $this->registerHook('paymentOptions')
        && $this->registerHook('paymentReturn')
        && $this->registerHook('displayOrderDetail')
        && $this-> _installDb()
        && $this->addOrderStates()
//        && $this->create_backofficeTab()
    );
    
    return (bool) $return;
}


//TODO Editar esto
public function create_backofficeTab(){
     return true;
}



public function _installDb(){

    $sql =  "

                CREATE TABLE IF NOT EXISTS "._DB_PREFIX_."straal_payment_url
                (
                    id INT NOT NULL AUTO_INCREMENT,
                    id_order int,
                    payment_url varchar(255),
                    date datetime,
                    PRIMARY KEY (id)
                ) ENGINE = "._MYSQL_ENGINE_.";



                CREATE TABLE IF NOT EXISTS "._DB_PREFIX_."straal_users_map
                (
                    id INT NOT NULL AUTO_INCREMENT,
                    straal_id_user varchar(255),
                    prestashop_id_guest varchar(255),
                    prestashop_id_user int,
                    straal_email varchar(255),
                    straal_reference varchar(255),
                    straal_created_at int,
                    PRIMARY KEY (id)
                ) ENGINE = "._MYSQL_ENGINE_.";


                CREATE TABLE IF NOT EXISTS "._DB_PREFIX_."straal_logs 
                (
                    id int NOT NULL AUTO_INCREMENT, 
                    title varchar(255), 
                    description LONGTEXT, 
                    id_user int, 
                    date DATETIME, 
                    PRIMARY KEY (id)
                ) ENGINE="._MYSQL_ENGINE_.";
                
                CREATE TABLE IF NOT EXISTS "._DB_PREFIX_."straal_card_map
                (
                    id INT NOT NULL AUTO_INCREMENT,
                    straal_id_user varchar(255),
                    straal_id_card varchar(255),
                    straal_num_last_4 varchar(4),
                    straal_origin_ipaddr varchar(255),
                    created_date DATETIME,
                    PRIMARY KEY (id)
                ) ENGINE = "._MYSQL_ENGINE_.";
            ";

    Db::getInstance()->execute($sql);
    return true;
}

public function addOrderStates(){
        $id_en = 0;
        $sql = "SELECT * FROM "._DB_PREFIX_."lang WHERE iso_code = 'en'";
        $lingua = Db::getInstance()->executeS($sql);

        if(count($lingua)>0){
            $id_en = $lingua[0]['id_lang'];
        }
        
        if (!(Configuration::get('STRAAL_WAITING_PAYMENT_CC') > 0)) {
     
            $OrderState = new OrderState(null, Configuration::get('PS_LANG_DEFAULT'));
            $OrderState->name = 'Awaiting Straal Payment';
            $OrderState->invoice = false;
            $OrderState->send_email = false;
            $OrderState->module_name = $this->name;
            $OrderState->color = '#ec6100';
            $OrderState->unremovable = true;
            $OrderState->hidden = false;
            $OrderState->logable = false;
            $OrderState->delivery = false;
            $OrderState->shipped = false;
            $OrderState->paid = false;
            $OrderState->deleted = false;
            $OrderState->template = 'preparation';
            $OrderState->add();
            Configuration::updateValue('STRAAL_WAITING_PAYMENT_CC', $OrderState->id);

//            $upd = 'update '._DB_PREFIX_.'order_state_lang SET name="Waiting payment by Credit Card / Debit Card" WHERE id_order_state =  '.$OrderState->id.' AND id_lang= '.$id_en.'';
//            Db::getInstance()->execute($upd);

        }

        //Puede ser auth del usuario o del admin.
        if (!(Configuration::get('STRAAL_WAITING_PAYMENT_AUTH') > 0)) {
            $OrderState = new OrderState(null, Configuration::get('PS_LANG_DEFAULT'));
            $OrderState->name = 'Awaiting authorization';
            $OrderState->invoice = false;
            $OrderState->send_email = false;
            $OrderState->module_name = $this->name;
            $OrderState->color = '#67a150';
            $OrderState->unremovable = true;
            $OrderState->hidden = false;
            $OrderState->logable = false;
            $OrderState->delivery = false;
            $OrderState->shipped = false;
            $OrderState->paid = true;
            $OrderState->deleted = false;
            $OrderState->template = 'preparation';
            $OrderState->add();
            Configuration::updateValue('STRAAL_WAITING_PAYMENT_AUTH', $OrderState->id);

//            $upd = 'update '._DB_PREFIX_.'order_state_lang SET name="Authorizing payment" WHERE id_order_state =  '.$OrderState->id.' AND id_lang= '.$id_en.'';
//            Db::getInstance()->execute($upd);
        }
        
        if (!(Configuration::get('STRAAL_WAITING_PAY_BY_LINK') > 0)) {
     
            $OrderState = new OrderState(null, Configuration::get('PS_LANG_DEFAULT'));
            $OrderState->name = 'Awaiting Straal payment';
            $OrderState->invoice = false;
            $OrderState->send_email = false;
            $OrderState->module_name = $this->name;
            $OrderState->color = '#ec6100';
            $OrderState->unremovable = true;
            $OrderState->hidden = false;
            $OrderState->logable = false;
            $OrderState->delivery = false;
            $OrderState->shipped = false;
            $OrderState->paid = false;
            $OrderState->deleted = false;
            $OrderState->template = 'preparation';
            $OrderState->add();
            Configuration::updateValue('STRAAL_WAITING_PAY_BY_LINK', $OrderState->id);

//            $upd = 'update '._DB_PREFIX_.'order_state_lang SET name="Waiting for payment Visa / Mastercard" WHERE id_order_state =  '.$OrderState->id.' AND id_lang= '.$id_en.'';
//            Db::getInstance()->execute($upd);
            
        }

        //Pago aprobado
        if (!(Configuration::get('STRAAL_APROVED') > 0)) {
     
            $OrderState = new OrderState(null, Configuration::get('PS_LANG_DEFAULT'));
            $OrderState->name = 'Payment Success';
            $OrderState->invoice = true;
            $OrderState->send_email = 1;
            $OrderState->module_name = $this->name;
            $OrderState->color = '#77e366';
            $OrderState->unremovable = true;
            $OrderState->hidden = false;
            $OrderState->logable = false;
            $OrderState->delivery = false;
            $OrderState->shipped = false;
            $OrderState->paid = 1;
            $OrderState->deleted = false;
            $OrderState->pdf_invoice = true;
            $OrderState->template = 'payment';
            $OrderState->add();
            Configuration::updateValue('STRAAL_APROVED', $OrderState->id);
//            $upd = 'update '._DB_PREFIX_.'order_state_lang SET name="Payment accepted" WHERE id_order_state =  '.$OrderState->id.' AND id_lang= '.$id_en.'';
//            Db::getInstance()->execute($upd);
            
        }

        //Pago aprobado
        if (!(Configuration::get('STRAAL_ERROR') > 0)) {

            $OrderState = new OrderState(null, Configuration::get('PS_LANG_DEFAULT'));
            $OrderState->name = 'Payment Error';
            $OrderState->invoice = false;
            $OrderState->send_email = 1;
            $OrderState->module_name = $this->name;
            $OrderState->color = '#e00202';
            $OrderState->unremovable = true;
            $OrderState->hidden = false;
            $OrderState->logable = false;
            $OrderState->delivery = false;
            $OrderState->shipped = false;
            $OrderState->paid = 0;
            $OrderState->deleted = false;
            $OrderState->pdf_invoice = false;
            $OrderState->template = 'payment';
            $OrderState->add();
            Configuration::updateValue('STRAAL_ERROR', $OrderState->id);
    //            $upd = 'update '._DB_PREFIX_.'order_state_lang SET name="Payment accepted" WHERE id_order_state =  '.$OrderState->id.' AND id_lang= '.$id_en.'';
    //            Db::getInstance()->execute($upd);

        }

        return true;
}

public function hookDisplayHeader($params)
{
    $this->context->controller->registerStylesheet('straal-style', 'modules/'.$this->name.'/views/css/style.css', ['media' => 'all', 'priority' => 150]);
    $this->context->controller->registerJavascript('straal-script', 'modules/'.$this->name.'/views/js/script.js',[ 'position' => 'bottom','priority' => 0]);
}

//por editar
public function hookDisplayOrderDetail($params)
{


    $id_order = $_GET['id_order'];
    $objOrder = new Order($id_order);

    require_once(__DIR__ ."/classes/straalApi.php");
    $straal = new straalApi();
    $current_url = $straal->getOrderPaymentUrl($id_order);

    if(count($current_url)>0){
        $url = $current_url[0]['payment_url'];
    }else{
        $url = false;
    }

    if($objOrder->current_state == Configuration::get('STRAAL_APROVED')){
        $status = true;
    }else{
        $status = false;
    }

    if($objOrder->current_state == Configuration::get('STRAAL_ERROR') || $objOrder->current_state == Configuration::get('STRAAL_WAITING_PAY_BY_LINK') || $objOrder->current_state == Configuration::get('STRAAL_WAITING_PAYMENT_CC') || $objOrder->current_state == Configuration::get('STRAAL_APROVED')){
        $is_straal = true;
    }else{
        $is_straal = false;
    }

    Media::addJsDef(array(
        'rensr_id_order' => $id_order,
    ));

    $this->context->controller->registerJavascript('straal-orderDetails', 'modules/'.$this->name.'/views/js/orderDetails.js',[ 'position' => 'bottom','priority' => 0]);

    $this->context->smarty->assign([
            'status' => $status,
            'id_order' => $id_order,
            'current_url' => $url,
            'is_straal' => $is_straal,
            'is_ssl' => array_key_exists('HTTPS', $_SERVER) && $_SERVER['HTTPS'] == "on"?1:0
        ]);

    return $this->fetch('module:'.$this->name.'/views/templates/hook/orderDetails.tpl');

}


//por editar
public function uninstall()
{
  $this->_clearCache('*');

  if(!parent::uninstall() || !$this->unregisterHook('displayNav2'))
     return false;

  return true;
}



 public function hookPaymentOptions($params)
{
    /*
     * Verify if this module is active
     */

    if (!$this->active) {
        return;
    }



    /**
     * Form action URL. The form data will be sent to the
     * validation controller when the user finishes
     * the order process.
     */



    /**
     * Create a PaymentOption object containing the necessary data
     * to display this module in the checkout
     */
    //Por editar el setAction, el logo y el form
    $newOption = new PrestaShop\PrestaShop\Core\Payment\PaymentOption;


    /**
     * Create Credit/Debit Cards
     */
    $newOption->setModuleName($this->displayName)
        ->setCallToActionText(Configuration::get('STRAAL_PAYMENT_NAME'))
        ->setAction($this->context->link->getModuleLink($this->name, 'cc', array(), true))
        ->setLogo(Media::getMediaPath(_PS_MODULE_DIR_.$this->name.'/img/transaction.png'));
//        ->setForm($this->generateFormCC());






    /**
     *  Load form template to be displayed in the checkout step
     */
    $total_cart = $this->context->cart->getOrderTotal(true, Cart::BOTH);



    $opciones= Array();


//    if(Configuration::get('STRAAL_CC_ACTIVE')==1){
        array_push($opciones, $newOption);  
//    }


    return $opciones;
}



protected function generateFormCC()
    {
        $total_cart = $this->context->cart->getOrderTotal(true, Cart::BOTH);


        if((float) $total_cart>=(float)Configuration::get('STRAAL_MIN_CC') && (float) $total_cart<=(float) Configuration::get('STRAAL_MAX_CC')){
            $permite = 1;
        }else{
            $permite = 0;
        }

        $this->context->smarty->assign([
            'frequente' => $permite,
            'action' => $this->context->link->getModuleLink($this->name, 'visa', array(), true),
        ]);

        return $this->context->smarty->fetch('module:'.$this->name.'/views/templates/front/payment_infos_visa.tpl');

    }

protected function generateFormPBL()
    {
        $total_cart = $this->context->cart->getOrderTotal(true, Cart::BOTH);

        if((float) $total_cart>=(float)Configuration::get('STRAAL_MIN_PBL') && (float) $total_cart<=(float) Configuration::get('STRAAL_MAX_PBL')){
            $permite = 1;
        }else{
            $permite = 0;
        }
        $this->context->smarty->assign([
            'frequente' => $permite,
            'action' => $this->context->link->getModuleLink($this->name, 'visa', array(), true),
        ]);

        return $this->context->smarty->fetch('module:'.$this->name.'/views/templates/front/payment_infos_visa.tpl');
    }


public function hookPaymentReturn($params)
    {
        /**
         * Verify if this module is enabled
         */
        if (!$this->active) {
            return;
        }
 
        return $this->fetch('module:'.$this->name.'/views/templates/hook/payment_return.tpl');
    }



public function getContent()
{
    $output = null;

    if (Tools::isSubmit('submit'.$this->name)) {

        $payment_name = strval(Tools::getValue('STRAAL_PAYMENT_NAME'));

        $api_key = strval(Tools::getValue('STRAAL_API_KEY'));

//        $api_sandbox = strval(Tools::getValue('STRAAL_SANDBOX'));
//        $api_auto_auth = strval(Tools::getValue('STRAAL_AUTO_AUTH'));

//        $api_cc = strval(Tools::getValue('STRAAL_CC_ACTIVE'));
//        $api_cc_min = floatval(Tools::getValue('STRAAL_MIN_CC'));
//        $api_cc_max = floatval(Tools::getValue('STRAAL_MAX_CC'));

//        $api_pbl = strval(Tools::getValue('STRAAL_PAY_BY_LINK_ACTIVE'));
//        $api_pbl_min = floatval(Tools::getValue('STRAAL_MIN_PBL'));
//        $api_pbl_max = floatval(Tools::getValue('STRAAL_MAX_PBL'));


        Configuration::updateValue('STRAAL_PAYMENT_NAME', $payment_name);

        Configuration::updateValue('STRAAL_API_KEY', $api_key);
//        Configuration::updateValue('STRAAL_SANDBOX', $api_sandbox);

//        Configuration::updateValue('STRAAL_AUTO_AUTH', $api_auto_auth);

//        Configuration::updateValue('STRAAL_CC_ACTIVE', $api_cc);
//        Configuration::updateValue('STRAAL_MIN_CC', $api_cc_min);
//        Configuration::updateValue('STRAAL_MAX_CC', $api_cc_max);

//        Configuration::updateValue('STRAAL_PAY_BY_LINK_ACTIVE', $api_pbl);
//        Configuration::updateValue('STRAAL_MIN_PBL', $api_pbl_min);
//        Configuration::updateValue('STRAAL_MAX_PBL', $api_pbl_max);

        $output .= $this->displayConfirmation($this->l('Settings updated'));
    }

    return $output.$this->displayForm();
}


public function displayForm()
{
    // Get default language
    $defaultLang = (int)Configuration::get('PS_LANG_DEFAULT');

    // Init Fields form array

    $fieldsForm[0]['form'] = [
        'legend' => [
            'title' => $this->l('Settings'),
        ],
        'input' => [


            [
                'type' => 'text',
                'hint' => $this->l('Set name for your payment method'),
                'label' => $this->l('Payment Method Name'),
                'name' => 'STRAAL_PAYMENT_NAME',
                'size' => 20,
                'required' => true
            ],



            [
                'type' => 'text',
                'hint' => $this->l('You can get your API KEY on Straal\'s BackOffice'),
                'label' => $this->l('API KEY'),
                'name' => 'STRAAL_API_KEY',
                'class'    => 'panel-group',
                'size' => 20,
                'required' => true
            ],

//            [
//                'type' => 'switch',
//                'label' => $this->l('Enable SandBox'),
//                'name' => 'STRAAL_SANDBOX',
//                'is_bool' => true,
//                //'desc' => $this->l('Description'),
//                'values' => array(
//                    array(
//                        'id' => 'active_on',
//                        'value' => 1,
//                        'label' => $this->l('Enable')
//                    ),
//                    array(
//                        'id' => 'active_off',
//                        'value' => 0,
//                        'label' => $this->l('Disable')
//                    )
//                ),
//            ],


//            [
//                'type' => 'switch',
//                'label' => $this->l('Automatic Authorizations?'),
//                'name' => 'STRAAL_AUTO_AUTH',
//                'hint' => $this->l('Configure'),
//                'is_bool' => true,
//                //'desc' => $this->l('Description'),
//                'values' => array(
//                    array(
//                        'id' => 'active_on',
//                        'value' => 1,
//                        'label' => $this->l('Enable')
//                    ),
//                    array(
//                        'id' => 'active_off',
//                        'value' => 0,
//                        'label' => $this->l('Disable')
//                    )
//                ),
//            ],
                
                

            
//            [
//                'type' => 'switch',
//                'label' => $this->l('Enable Credit Card / Debit Card Payment'),
//                'name' => 'STRAAL_CC_ACTIVE',
//                'is_bool' => true,
//                //'desc' => $this->l('Description'),
//                'values' => array(
//                    array(
//                        'id' => 'active_on',
//                        'value' => 1,
//                        'label' => $this->l('Enable')
//                    ),
//                    array(
//                        'id' => 'active_off',
//                        'value' => 0,
//                        'label' => $this->l('Disable')
//                    )
//                ),
//            ],
//            [
//                'type' => 'text',
//                'label' => $this->l('Min amount for Straal payments'),
//                'name' => 'STRAAL_MIN_CC',
//                'suffix' => '€',
//                'size' => 20,
//                'required' => true
//            ],
//            [
//                'type' => 'text',
//                'label' => $this->l('Max amount for Straal payments'),
//                'name' => 'STRAAL_MAX_CC',
//                'class'    => 'panel-group',
//                'suffix' => '€',
//                'size' => 20,
//                'required' => true,
//                'attr'=> array('class'=>'form-group')
//            ],

//            [
//                'type' => 'switch',
//                'label' => $this->l('Enable Pay-By-Link Payment'),
//                'name' => 'STRAAL_PAY_BY_LINK_ACTIVE',
//                'is_bool' => true,
//                //'desc' => $this->l('Description'),
//                'values' => array(
//                    array(
//                        'id' => 'active_on',
//                        'value' => 1,
//                        'label' => $this->l('Enable')
//                    ),
//                    array(
//                        'id' => 'active_off',
//                        'value' => 0,
//                        'label' => $this->l('Disable')
//                    )
//                    ),
//            ],

//            [
//                'type' => 'text',
//                'label' => $this->l('Min amount Pay-By-Link'),
//                'name' => 'STRAAL_MIN_PBL',
//                'suffix' => '€',
//                'size' => 20,
//                'required' => true
//            ],
//            [
//                'type' => 'text',
//                'label' => $this->l('Max amount Pay-By-Link'),
//                'name' => 'STRAAL_MAX_PBL',
//                'class'    => 'panel-group',
//                'suffix' => '€',
//                'size' => 20,
//                'required' => true,
//                'attr'=> array('class'=>'form-group')
//            ],



            [
                'type' => 'text',
                'label' => $this->l('GENERIC LINK'),
                'name' => 'STRAAL_GENERIC_LINK',
                'hint' => $this->l('You need to include this URL into your Straal BackOffice'),
                'size' => 20,
                'required' => false
            ],

        ],
        
        'submit' => [
            'title' => $this->l('Save'),
            'class' => 'btn btn-default pull-right'
        ]
    ];

    $helper = new HelperForm();

    // Module, token and currentIndex
    $helper->module = $this;
    $helper->name_controller = $this->name;
    $helper->token = Tools::getAdminTokenLite('AdminModules');
    $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;

    // Language
    $helper->default_form_language = $defaultLang;
    $helper->allow_employee_form_lang = $defaultLang;

    // Title and toolbar
    $helper->title = $this->displayName;
    $helper->show_toolbar = true;        // false -> remove toolbar
    $helper->toolbar_scroll = true;      // yes - > Toolbar is always visible on the top of the screen.
    $helper->submit_action = 'submit'.$this->name;
    $helper->toolbar_btn = [
        'save' => [
            'desc' => $this->l('Save'),
            'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.
            '&token='.Tools::getAdminTokenLite('AdminModules'),
        ],
        'back' => [
            'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
            'desc' => $this->l('Back to list')
        ]
    ];

    // Load current value
//    $helper->fields_value['STRAAL_SANDBOX'] = Configuration::get('STRAAL_SANDBOX');

    $helper->fields_value['STRAAL_API_KEY'] = Configuration::get('STRAAL_API_KEY');
//    $helper->fields_value['STRAAL_AUTO_AUTH'] = Configuration::get('STRAAL_AUTO_AUTH');


//    $helper->fields_value['STRAAL_CC_ACTIVE'] = Configuration::get('STRAAL_CC_ACTIVE');
//    $helper->fields_value['STRAAL_MIN_CC'] = Configuration::get('STRAAL_MIN_CC');
//    $helper->fields_value['STRAAL_MAX_CC'] = Configuration::get('STRAAL_MAX_CC');
//
//
//    $helper->fields_value['STRAAL_PAY_BY_LINK_ACTIVE'] = Configuration::get('STRAAL_PAY_BY_LINK_ACTIVE');
//    $helper->fields_value['STRAAL_MIN_PBL'] = Configuration::get('STRAAL_MIN_PBL');
//    $helper->fields_value['STRAAL_MAX_PBL'] = Configuration::get('STRAAL_MAX_PBL');

    $helper->fields_value['STRAAL_PAYMENT_NAME'] = Configuration::get('STRAAL_PAYMENT_NAME');

    $helper->fields_value['STRAAL_GENERIC_LINK'] = _PS_BASE_URL_.__PS_BASE_URI__."?fc=module&module=straal&controller=agent";


    return $helper->generateForm($fieldsForm);
}


















}
?>
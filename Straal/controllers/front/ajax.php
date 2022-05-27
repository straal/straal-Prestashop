<?php
/**
 */
require_once(__DIR__ ."/../../classes/straalApi.php");

class straalAjaxModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();


        $this->setTemplate('module:straal/views/templates/front/agent.tpl');

    }

    public function displayAjaxCreateUrl()
    {
        $straal = new straalApi();
        $id_order = $_POST['id_order'];
        $objOrder = new Order($id_order);
        $currency = new Currency($objOrder->id_currency);
        $lang = new Language($objOrder->id_lang);

        $old_url = $straal->getOrderPaymentUrl($id_order);

        if(count($old_url)>0){
            $url = $old_url[0]['payment_url'];
            echo json_encode($url);
        }else{
            $response = $straal->createNewPaymentWithCC($currency->iso_code, $objOrder->total_paid, $lang->iso_code, "Prestashop - ".Configuration::get('PS_SHOP_NAME'), (string)$objOrder->id_cart, 'oneshot', (string)$objOrder->id_cart);
            $straal->mapOrderWithPaymentUrl($id_order, $response['checkout_url']);
            echo json_encode($response['checkout_url']);
        }

        return true;
    }
}

?>
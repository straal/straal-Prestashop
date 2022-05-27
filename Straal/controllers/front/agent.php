<?php
/**
 */
require_once(__DIR__ ."/../../classes/straalApi.php");

class straalAgentModuleFrontController extends ModuleFrontController
{
    public function initContent()
	{
		parent::initContent();

            $respuesta = file_get_contents('php://input');
            $respuesta = json_decode($respuesta, true);
            $errores = Array();

            //Classe straal
            $straal = new straalApi();
            $straal->createLog('Agent Controller executed2', "Response: ".json_encode($respuesta));

            if(isset($respuesta['event']) && ($respuesta['event']=="request_finished") ){
                $straal->createLog('Registrando request_finished/checkout_attempt_finished', "Nuevo request finished capturado: ".$respuesta['data']['response']['captured'].', para el carrito: '.$respuesta['data']['response']['order_reference']);

                if(count($respuesta['data']['response']['captures'])>0){
                    $total_capture = 0;
                    foreach($respuesta['data']['response']['captures'] as $capture){
                       if($capture['status']=='succeeded'){
                           $total_capture += (int)$capture['amount'];
                       }
                    }

                    if(isset($respuesta['data']['response']) && $total_capture >= $respuesta['data']['response']['amount']){
                        //Get order by cart ID
                        $id_cart = (int)$respuesta['data']['response']['order_reference'];

                        $objOrder = new Order(Order::getIdByCartId((int)$id_cart));

                        $objCustomer = new Customer($objOrder->id_customer);

                        $straal->createLog('Enviando email de pagamento', 'processing');

                        Mail::Send(
                            (int)$objOrder->id_lang, // defaut language id
                            'payment_success', // email template file to be use
                            Context::getContext()->getTranslator()->trans('Straal payment success'), // email subject
                            array(
                                '{id_order}' => (int)$objOrder->id,
                                '{order_reference}' => $objOrder->reference,
                                '{order_details}' => _PS_BASE_URL_.__PS_BASE_URI__.'index.php?controller=order-detail&id_order='.(int)$objOrder->id,
                                '{SHOPNAME}' => Configuration::get('PS_SHOP_NAME'),
                            ),
                            $objCustomer->email, // receiver email address
                            NULL, //receiver name
                            NULL, //from email address
                            NULL,  //from name
                            NULL,
                            NULL,
                            _PS_BASE_URL_.__PS_BASE_URI__.'modules/straal/mails/'
                        );

                        //Get the order for change state.
                        $history = new OrderHistory();
                        $history->id_order = (int)$objOrder->id;
                        $history->changeIdOrderState(Configuration::get('STRAAL_APROVED'), (int)$objOrder->id);
                        $history->add();

                        $straal->createLog('Encomienda pagada', 'Fué capturado '.$total_capture.' de '.$respuesta['data']['response']['amount']);

                    }else{
                        if(isset($respuesta['data']['transaction']['errors']) && count($respuesta['data']['transaction']['errors'])>0){
                            $errores = $respuesta['data']['transaction']['errors'];
                        }

                        $id_cart = (int)$respuesta['data']['response']['order_reference'];


                        $objOrder = new Order(Order::getIdByCartId((int)$id_cart));
                        $objCustomer = new Customer($objOrder->id_customer);

                        Mail::Send(
                            (int)$objOrder->id_lang, // defaut language id
                            'payment_error', // email template file to be use
                            Context::getContext()->getTranslator()->trans('Straal payment error'), // email subject
                            array(
                                '{id_order}' => (int)$objOrder->id,
                                '{order_reference}' => $objOrder->reference,
                                '{order_details}' => _PS_BASE_URL_.__PS_BASE_URI__.'index.php?controller=order-detail&id_order='.(int)$objOrder->id,
                                '{SHOPNAME}' => Configuration::get('PS_SHOP_NAME'),
                            ),
                            $objCustomer->email, // receiver email address
                            NULL, //receiver name
                            NULL, //from email address
                            NULL,  //from name
                            NULL,
                            NULL,
                            _PS_BASE_URL_.__PS_BASE_URI__.'modules/straal/mails/'
                        );

                        $history = new OrderHistory();
                        $history->id_order = (int)$objOrder->id;
                        $history->changeIdOrderState(Configuration::get('STRAAL_ERROR'), (int)$objOrder->id);
                        $history->add();

                        $straal->createLog('Encomienda parcialmente pagada', 'Fué capturado '.$total_capture.' de '.$respuesta['data']['response']['amount']);
                    }
                }

            }

            $this->context->smarty->assign(array(
                'ativar_nome' => Configuration::get('activar_nome'),
            ));

            $this->setTemplate('module:straal/views/templates/front/agent.tpl');
	}
}

?>
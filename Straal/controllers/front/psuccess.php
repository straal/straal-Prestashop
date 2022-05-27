<?php
/**
 */
require_once(__DIR__ ."/../../classes/straalApi.php");

class straalPsuccessModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();


        //Classe straal

        $this->context->smarty->assign(array(
            'logs' => '',
        ));
        $this->setTemplate('module:straal/views/templates/front/psuccess.tpl');
    }
}

?>
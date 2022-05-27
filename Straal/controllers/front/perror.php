<?php
/**
 */
require_once(__DIR__ ."/../../classes/straalApi.php");

class straalPerrorModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();


        //Classe straal

        $this->context->smarty->assign(array(
            'logs' => '',
        ));
        $this->setTemplate('module:straal/views/templates/front/perror.tpl');
    }
}

?>
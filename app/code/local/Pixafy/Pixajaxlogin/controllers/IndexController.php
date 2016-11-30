<?php

class Pixafy_Pixajaxlogin_IndexController extends Mage_Core_Controller_Front_Action{
    public function loginAction(){
        if ($this->getRequest()->isPost()) {
            $message = array();
            $session = Mage::getSingleton('customer/session');
            $form_key = $this->getRequest()->getPost('form_key');

            try {
                if( $form_key != Mage::getSingleton('core/session')->getFormKey() ){
                    throw(new Exception);
                }
                $login_data = $this->getRequest()->getPost('login', array());

                if( $session->login($login_data['username'], $login_data['password']) ){
                    $customer = $session->getCustomer();
                    $session->setCustomerAsLoggedIn($customer);

                    $message = array(
                        'success' => true
                    );
                }

            } catch (Exception $ex) {
                $message = array(
                    'error'         => -1,
                    'message'       => $ex->getMessage(),
                    'user_key'      => $form_key
                );
            }
            $this->getResponse()->clearHeaders()->setHeader('Content-type','application/json',true);
            $this->getResponse()->setBody(json_encode($message));
        }
    }
}
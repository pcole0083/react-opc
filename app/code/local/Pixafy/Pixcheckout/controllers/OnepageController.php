<?php
require_once 'Mage/Checkout/controllers/OnepageController.php';

class Pixafy_Pixcheckout_OnepageController extends Mage_Checkout_OnepageController
{
	/**
	 * [getShippingAddress description]
	 * @return [type] [description]
	 */
	public function getShippingAddress(){
		$quote = Mage::getModel('checkout/session')->getQuote();
  		if ($quote) {
    		if ($shipping_address = $quote->getShippingAddress()) {
				return $shipping_address;
      		}
		}
		return null;
	}
	/**
	 * [_getShippingRates description]
	 * @return [type] [description]
	 */
	protected function _getShippingRates()
	{
		$shipping = Mage::getModel('shipping/shipping');
		$address = $this->getShippingAddress();    
	    $result = $shipping->collectRatesByAddress($address)->getResult();
	    $shipping_methods = array();

	    foreach ($result->getAllRates() as $rate) {
	        if ($rate instanceof Mage_Shipping_Model_Rate_Result_Error) {
	            $errors[$rate->getCarrierTitle()] = 1;
	        } else {
	            $k = $rate->getCarrierTitle().' - '.$rate->getMethodTitle();
	            $k = $rate->getCarrier() . '_' . $rate->getMethod();

	            if ($address->getFreeShipping()) {
	                $price = 0;
	            } else {
	                $price = $rate->getPrice();
	            }

	            if ($price) {
	                $price = Mage::helper('tax')->getShippingPrice($price, false, $address);
	            }

	            $newrates[$k] = $price;
	        }
	    }

	    return $newrates;
	}

	/**
     * Get shipping method step JSON
     *
     * @return string
     */
    protected function _getShippingMethodsJSON()
    {
        $methods = Mage::getSingleton('shipping/config')->getActiveCarriers();
		$shipping_methods = array();
		$rates = $this->_getShippingRates();

		foreach($methods as $_ccode => $_carrier) {
		    if($_methods = $_carrier->getAllowedMethods())  {

		        if(!$_title = Mage::getStoreConfig("carriers/$_ccode/title")){
		            $_title = $_ccode;
		        }

		        $_price = '$0.00';

		        foreach($_methods as $_mcode => $_method) {
		            $_code = $_ccode . '_' . $_mcode;
		            if($rates[$_code]){
			        	$_price = Mage::helper('checkout')->formatPrice($rates[$_code]);
			        }

		            $shipping_methods[] = array(
		            	'name' 			 => 'shipping_method',
		            	'id' 			 => 's_method_'.$_code,
		            	'selected' 	 	 => '',
		            	'value' 		 => $_code,
		            	'title' 		 => $_method,
		            	'price'   		 => $rates[$_code],
		            	'labelText' 	 => array(
		            		'__html' => '<span class="method-title">'.$_title.'</span>'.$_price
		            	)
		            );
		        }
		    }
		}
		return $shipping_methods;
    }

    /**
     * [_getPaymentMethodsJSON description]
     * @return [type] [description]
     */
    protected function _getPaymentMethodsJSON()
    {
    	$quote = Mage::getModel('checkout/session')->getQuote();
    	$_paymentHelper = Mage::helper('payment');

    	$allActivePaymentMethods = $_paymentHelper->getStoreMethods(Mage::app()->getStore(), $quote);
    	//$allActivePaymentMethods = Mage::getSingleton('payment/config')->getActiveMethods();
    	$payment_methods = array();
    	$html = $this->_getOutPut();

    	foreach ($allActivePaymentMethods as $key => $method) {
    		$_code = $method->getCode();
    		// $alias = Mage::getStoreConfig('payment/'.$_code.'/model');
    		// $model = Mage::getModel($alias);
    		// $block = $this->getLayout()->createBlock('payment.method.'.$_code);
    		// 
    		//$instance = $_paymentHelper->getMethodInstance($_code);

    		if(!$paymentTitle = Mage::getStoreConfig('payment/'.$_code.'/title')){
    			$paymentTitle = $_code;
    		}

    		if( $this->_canUsePaymentMethod($method, $quote) ){
	    		$payment_methods[] = array(
	    			'name' => 			 'payment[method]',
	        		'id' =>              'p_method_'.$_code,
	                'type' =>            'radio',
	                'value' =>           $_code,
	                'getCode' => 		 $method->getCode(),
	                'labelText' =>       array(
	                	'__html' => 	'<span class="method-title">'.$paymentTitle.'</span>'
	                ),
	                'selected' =>        '',
	                'displayOnSelect' => array(
	                	'__html' =>      $this->getMethodFormBlock($html, $code, $key)
	                )
	    		);
    		}
    	}
    	return $payment_methods;
    }

    /**
     * [_getOutPut description]
     * @return [type] [description]
     */
    private function _getOutPut(){
    	if(!isset($this->_outputHTML)){
	    	$layout = $this->getLayout();
	        $update = $layout->getUpdate();
	        $update->load('checkout_onepage_paymentmethod');
	        $layout->generateXml();
	        $layout->generateBlocks();
	        $output = $layout->getOutput();
	        $this->_outputHTML = $output;
	    }
        return $this->_outputHTML;
    }

    /**
     * Get payment method step html
     *
     * @return string
     */
    public function getMethodFormBlock($html, $code, $key)
    {	
    	
    	
        $blocksArray = explode('</dd>', $html);
		$block = $blocksArray[$key];

		$search = array(
	        '/\>[^\S ]+/s',  // strip whitespaces after tags, except space
	        '/[^\S ]+\</s',  // strip whitespaces before tags, except space
	        '/(\s)+/s',       // shorten multiple whitespace sequences

	    );

	    $replace = array(
	        '>',
	        '<',
	        '\\1'
	    );

		$block = preg_replace($search, $replace, $block);
		//
		$re = '/(^.*<dd>)/i';
		$none = 'display:none;';
		$_html = preg_replace($re, '', ''.$block);
		$_html = str_ireplace($none, '', $_html);
        //$re = '/(\<ul class="form-list"[.*]\<\/ul\>)/im';
        
        //$grep_block = preg_grep($re, $blocksArray);

        return $_html;
    }

    /**
     * [_canUsePaymentMethod description]
     * @param  [type] $method [description]
     * @param  [type] $quote  [description]
     * @return [type]         [description]
     */
    protected function _canUsePaymentMethod($method, $quote)
	{   
	    if (!($method->isGateway() || $method->canUseInternal())) {
	        return false;
	    }

	    if (!$method->canUseForCountry($quote->getBillingAddress()->getCountry())) {
	        return false;
	    }

	    if (!$method->canUseForCurrency(Mage::app()->getStore($quote->getStoreId())->getBaseCurrencyCode())) {
	        return false;
	    }

	    /**
	     * Checking for min/max order total for assigned payment method
	     */
	    $total = $quote->getBaseGrandTotal();
	    $minTotal = $method->getConfigData('min_order_total');
	    $maxTotal = $method->getConfigData('max_order_total');

	    if ((!empty($minTotal) && ($total < $minTotal)) || (!empty($maxTotal) && ($total > $maxTotal))) {
	        return false;
	    }

	    return true;
	}

	/**
     * Save checkout billing address
     */
    public function saveBillingAction()
    {
        if ($this->_expireAjax()) {
            return;
        }
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost('billing', array());
            $customerAddressId = $this->getRequest()->getPost('billing_address_id', false);

            if (isset($data['email'])) {
                $data['email'] = trim($data['email']);
            }
            $result = $this->getOnepage()->saveBilling($data, $customerAddressId);

            if (!isset($result['error'])) {
                if ($this->getOnepage()->getQuote()->isVirtual()) {
                    $result['goto_section'] = 'payment';
                    $result['update_section'] = array(
                        'name' => 'payment-method',
                        'data' => $this->_getPaymentMethodsJSON()
                    );
                } elseif (isset($data['use_for_shipping']) && $data['use_for_shipping'] == 1) {
                    $result['goto_section'] = 'shipping_method';
                    $result['update_section'] = array(
                        'name' => 'shipping-method',
                        'methods' => $this->_getShippingMethodsJSON(),
                    );

                    $result['allow_sections'] = array('shipping');
                    $result['duplicateBillingInfo'] = 'true';
                } else {
                    $result['goto_section'] = 'shipping';
                }
            }

            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
        }
    }

    /**
     * Shipping address save action
     */
    public function saveShippingAction()
    {
        if ($this->_expireAjax()) {
            return;
        }
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost('shipping', array());
            $customerAddressId = $this->getRequest()->getPost('shipping_address_id', false);
            $result = $this->getOnepage()->saveShipping($data, $customerAddressId);

            if (!isset($result['error'])) {
                $result['goto_section'] = 'shipping_method';
                $result['update_section'] = array(
                    'name' => 'shipping-method',
                    'methods' => $this->_getShippingMethodsJSON()
                );
            }
            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
        }
    }

    /**
     * Shipping method save action
     */
    public function saveShippingMethodAction()
    {
        //echo "<pre>";
        //var_dump($this->_getPaymentMethodsJSON());
        if ($this->_expireAjax()) {
            return;
        }
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost('shipping_method', '');
            $result = $this->getOnepage()->saveShippingMethod($data);
            // $result will contain error data if shipping method is empty
            if (!$result) {
                Mage::dispatchEvent(
                    'checkout_controller_onepage_save_shipping_method',
                     array(
                          'request' => $this->getRequest(),
                          'quote'   => $this->getOnepage()->getQuote()));
                $this->getOnepage()->getQuote()->collectTotals();
                $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));

                $result['goto_section'] = 'payment';
                $result['update_section'] = array(
                    'name' => 'payment-method',
                    'methods' => $this->_getPaymentMethodsJSON()
                );
            }

            $this->getOnepage()->getQuote()->collectTotals()->save();
            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
        }
    }
}
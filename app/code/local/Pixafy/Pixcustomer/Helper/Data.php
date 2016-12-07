<?php

class Pixafy_Pixcustomer_Helper_Data extends Mage_Core_Helper_Abstract {

	public function getAllAddressesJSON(){
		if(!isset($this->_customer)){
			$this->_customer = Mage::helper('customer')->getCustomer();
		}

		if(!isset($this->_addressesCollection)){
			$this->_addressesCollection = $this->_customer->getAddresses();
		}

		$jsonData = array(
			'all' => array()
		);

		foreach ($this->_addressesCollection as $key => $address) {
			$jsonData['all'][] = array(
				'value' => $address->getId(),
				'data_id' => $address->getEntityId(),
				'label' => $this->_constructAddress($address)
			);
		}

		return $jsonData;
		//return $this->_addressesCollection;
	}

	private function _constructAddress($address) {
		return $this->_customer->getName() . ', ' . trim($address->getStreet1().' '.$address->getStreet2()) . ', ' . trim($address->getCity()) . ', ' . trim($address->getRegion()) . ' ' . trim($address->getPostcode());
	}

	
}
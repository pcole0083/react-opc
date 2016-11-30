<?php
/**
 * 
 */
class Pixafy_Pixcheckout_Helper_Data extends Mage_Core_Helper_Abstract {
	
	public function getCartJSON(){
		$quote = Mage::getModel('checkout/session')->getQuote();
		$totals = $quote->getTotals();
		$cart = array();
		$items = array();

		foreach ($quote->getAllVisibleItems() as $key => $item) {
			$items[] = array(
				'id' 	=> $item->getId(),
				'name' 	=> $item->getName(),
				'sku' 	=> $item->getSku(),
				'price' => $item->getPrice(),
				'qty' 	=> $item->getQty(),
				'product_subtotal' => ($item->getPrice() * $item->getQty())
			);
		}

		$cart['items'] = $items;
		$cart['totals'] = $totals;
		$cart['subtotal'] = !!$totals['subtotal'] ? $totals['subtotal']->getValue(): 0;
		$cart['tax'] = !!$totals['tax'] ? (float)$totals['tax']->getValue() : 0;
		$cart['shipping'] = !!$totals['shipping'] ? (float)$totals['shipping']->getValue() : 0;
		$cart['grandtotal'] = !!$totals['grand_total'] ? $totals['grand_total']->getValue() : 0;

		$output = array(
			'cart' 			 => $cart,
			'taxes' 		 => $cart['tax'],
			'shipping_price' => $cart['shipping'],
			'subtotal' 		 => $cart['subtotal'],
			'grandtotal'  	 => $cart['grandtotal']
		);

		return $output;
	}
}
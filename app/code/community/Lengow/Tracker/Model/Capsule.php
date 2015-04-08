<?php

/**
 * Lengow tracker model tracker
 *
 * @category    Lengow
 * @package     Lengow_Tracker
 * @author      Romain Le Polh <romain@lengow.com>
 * @copyright   2013 Lengow SAS 
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Lengow_Tracker_Model_Capsule extends Lengow_Tracker_Model_Tracker {
    
    /**
     * Return product quote or order
     * 
     * @param $quote Order or Quote
     * @return string i(n)=(id_product_n)&p(n)=(unit_price_n)&q(n)=(quantity_n)
     */
    public function getProductsCart($quote) {
        if($quote instanceof Mage_Sales_Model_Quote || $quote instanceof Mage_Sales_Model_Order) {
            $quote_items = $quote->getAllVisibleItems();
            $list_products = array();
            $i = 1;
            foreach($quote_items as $item) {
                if($item->hasProduct())
                    $product = $item->getProduct();
                else
                    $product = Mage::getModel('catalog/product')->load($item->getProductId());
                $str = 'i' . $i . '=' . $product->getData($this->_getIdentifier());
                $str .= '&p' . $i . '=' . $item->getPrice();
                $str .= '&q' . $i . '=' . ($item->getQty() > 0 ? $item->getQty() : (integer) $item->getQtyOrdered());
                $list_products[] = $str;
                $i++;
            }
            return implode('&', $list_products);
        }
        return;
    }

}
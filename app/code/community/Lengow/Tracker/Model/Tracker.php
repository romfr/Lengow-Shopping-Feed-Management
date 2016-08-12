<?php

/**
 * Lengow tracker model tracker
 *
 * @category    Lengow
 * @package     Lengow_Export
 * @author      Romain Le Polh <romain@lengow.com>
 * @copyright   2013 Lengow SAS 
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Lengow_Tracker_Model_Tracker extends Varien_Object {

    /**
     * Return list of order's items id
     *
     * @param $order Mage_Sales_Model_Order
     * @return string
     */
    public function getIdsProducts($quote) {
        if($quote instanceof Mage_Sales_Model_Order || $quote instanceof Mage_Sales_Model_Quote) {
            $quote_items = $quote->getAllVisibleItems();
            $ids = array();
            foreach($quote_items as $item) {
                if($item->hasProduct())
                    $product = $item->getProduct();
                else
                    $product = Mage::getModel('catalog/product')->load($item->getProductId());
                $ids[] = $product->getData($this->_getIdentifier());
            }
            return implode('|', $ids);
        }
        return false;
    }

    /**
     * Return list of order's items id
     *
     * @param $order Mage_Sales_Model_Order
     * @return string
     */
    protected function _getIdentifier() {
        $config = Mage::getModel('lentracker/config');
        return $config->get('tag/identifiant');
    }


}
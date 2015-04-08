<?php
/**
 * Lengow export model observer
 *
 * @category    Lengow
 * @package     Lengow_Export
 * @author      Ludovic Drin <ludovic@lengow.com>
 * @copyright   2013 Lengow SAS 
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Lengow_Export_Model_Observer {

    public function cron($observer)	{
        $store_collection = Mage::getResourceModel('core/store_collection')
                               ->addFieldToFilter('is_active', 1);
        $exceptions = array();
        foreach($store_collection as $store) {
            try {
                if(Mage::getStoreConfig('export/performances/active_cron', $store)) {
                    $generate = Mage::getModel('export/generate');
                    $format =Mage::getStoreConfig('export/data/format', $store);
                    $generate->exec($store->getId(), null, $format, null, null, null, null, null, false, false);
                }
            } catch (Exception $e) {
                Mage::log($e->getMessage());
                Mage::log($e->getTraceAsString());
            }
        }
        return $this;
    }

    public function autoExportProduct($observer) {
        $_config = Mage::getSingleton('export/config');
        if($_config->isAutoExportProduct()) {
            $_product = $observer->getEvent()->getProduct();
            $_product->setData('lengow_product', 1);
            $_resource = Mage::getResourceModel('catalog/product');
            $_entity_type_id  = $_resource->getEntityType()->getId();
            try {
                $_product_ressource = new Varien_Object(array('entity_id' => $_product->getId(),
                                                              'id' => $_product->getId(),
                                                              'entity_type_id' => $_entity_type_id,
                                                              'store_id' => Mage::app()->getStore()->getId(),
                                                              'lengow_product' => 1));
                $_resource->saveAttribute($_product_ressource, 'lengow_product');
            } catch (Exception $e) {
                Mage::log($e->getMessage());
                Mage::log($e->getTraceAsString());                
            }
            Mage::log('Auto export product ' . $_product->getId(). ' (SKU ' . $_product->getSku(). ') to Lengow');
        }
    }
	
}
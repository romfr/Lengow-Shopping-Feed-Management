<?php

/**
 * Lengow export model observer
 *
 * @category    Lengow
 * @package     Lengow_Export
 * @author      Ludovic Drin <ludovic@lengow.com> & Benjamin Le NevÃ© <benjamin.le-neve@lengow.com>
 * @copyright   2015 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Lengow_Export_Model_Observer {

    /**
     * Exports products for each store with cron job
     */
    public function export($observer)
    {
        if (Mage::getStoreConfig('lenexport/performances/active_cron')) {
            // clean old log (20 days)
            Mage::helper('lensync/data')->cleanLog();
            Mage::helper('lensync/data')->log('## Start cron export ##');
            set_time_limit(0);
            ini_set('memory_limit', '1G');
            $store_collection = Mage::getResourceModel('core/store_collection')->addFieldToFilter('is_active', 1);
            $exceptions = array();
            foreach($store_collection as $store) {
                try {
                    if (Mage::getStoreConfig('lenexport/global/active_store', $store)) {
                        Mage::helper('lensync/data')->log('Start cron export in Store ' . $store->getName() . '(' . $store->getId() . ')');
                        $_configModel = Mage::getSingleton('lenexport/config');
                        $_configModel->setStore($store->getId());
                        $format =Mage::getStoreConfig('lenexport/data/format', $store);
                        if(Mage::getStoreConfig('lenexport/performances/optimizeexport'))
                            $generate = Mage::getModel('lenexport/generateoptimize');
                        else
                            $generate = Mage::getModel('lenexport/generate');
                        $generate->setCurrentStore($store->getId());
                        $generate->setOriginalCurrency($store->getCurrentCurrencyCode());
                        if(Mage::getStoreConfig('lenexport/performances/optimizeexport'))
                            $generate->exec(
                                $store->getId(), 
                                $format,
                                array(
                                    'stream' => false
                                )
                            );
                        else 
                            $generate->exec($store->getId(), null, $format, null, null, null, null, null, false, false);
                    } else {
                        Mage::helper('lensync/data')->log('Stop cron export - Store ' . $store->getName() . '(' . $store->getId() . ') is disabled');
                    }
                } catch (Exception $e) {
                    Mage::helper('lensync/data')->log('Stop cron export - Store ' . $store->getName() . '(' . $store->getId() . ') - Error: '.$e->getMessage());
                    Mage::log($e->getMessage());
                    Mage::log($e->getTraceAsString());
                }
            }
            Mage::helper('lensync/data')->log('## End cron export ##');
        }
        return $this;
    }

    /**
     * Adds new products to the selection Lengow
     */
    public function autoExportProduct($observer)
    {
        $_config = Mage::getSingleton('lenexport/config');
        if($_config->isAutoExportProduct()) {
            $_product = $observer->getEvent()->getProduct();
            try {
                $_product->setLengowProduct(1);
            } catch (Exception $e) {
                Mage::log($e->getMessage());
                Mage::log($e->getTraceAsString());
            }
            Mage::log('Auto export product ' . $_product->getId(). ' (SKU ' . $_product->getSku(). ') to Lengow');
        }
    }


    public function afterSaveConfiguration($observer)
    {
        $postData = $observer->getEvent()->getData();
        $params = Mage::app()->getRequest()->getParams();

        if (isset($params["groups"]["global"]["fields"]["active_store"])) {
            if (isset($params["groups"]["global"]["fields"]["active_store"]["value"])
                && $params["groups"]["global"]["fields"]["active_store"]["value"] == 1
            ) {
                return true;
            }
        }

        if (is_null($postData['store']) && $postData['website']) //check for website scope
        {
            //do nothing
        }
        elseif($postData['store']) //check for store scope
        {
            $current_store = Mage::getModel('core/store')->load($postData['store']);
            //delete feed
            $this->_deleteStoreFiles($current_store);
        }
        else //for default scope
        {
            //check individual store
            //delete all feeds
            foreach (Mage::app()->getWebsites() as $website) {
                foreach ($website->getGroups() as $group) {
                    $stores = $group->getStores();
                    foreach ($stores as $store) {
                        //check if individual value is on
                        if (Mage::getStoreConfig('lenexport/global/active_store', $store)==0){
                            $this->_deleteStoreFiles($store);
                        }
                    }
                }
            }
        }

    }


    private function _deleteStoreFiles($store)
    {
        $formatFeed = array("csv","xml","yaml","json");
        foreach($formatFeed as $format){
            $filePath = Mage::getBaseDir('media') . DS . 'lengow' . DS . $store->getCode() . DS."lengow_feed.".$format;
            if (file_exists($filePath)){
                unlink($filePath);
                Mage::helper('lensync/data')->log('Store '.$store->getName().' desactived - Feed delete : '.$filePath);
            }
        }
    }

}
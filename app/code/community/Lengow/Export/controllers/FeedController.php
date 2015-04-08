<?php
/**
 * Lengow export controller
 *
 * @category    Lengow
 * @package     Lengow_Export
 * @author      Ludovic Drin <ludovic@lengow.com>
 * @copyright   2013 Lengow SAS 
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Lengow_Export_FeedController extends Mage_Core_Controller_Front_Action {

    public function indexAction()  {
        set_time_limit(0);
        ini_set('memory_limit', '1G'); 
        $mode = $this->getRequest()->getParam('mode');
        $helper = Mage::helper('export/security');
        if($helper->checkIp()) {
            $this->_configModel = Mage::getSingleton('export/config');
            try {
                $this->loadLayout(false);
                $this->renderLayout();
            } catch (Exception $e) {
                Mage::throwException($e);
            }
            $generate = Mage::getSingleton('export/generate');
            $generate->setCurrentStore(Mage::app()->getStore()->getId());
            $_default_store = Mage::getModel('core/store')->load(Mage::app()
                            ->getWebsite(true)
                            ->getDefaultGroup()
                            ->getDefaultStoreId());
            $generate->setOriginalCurrency($_default_store->getCurrentCurrencyCode());
            $id_store = (integer) $this->getRequest()->getParam('store', Mage::app()->getStore()->getId());
            Mage::app()->getStore()->setCurrentStore($id_store);
            $format = (string) $this->getRequest()->getParam('format', 'csv');
            $types = $this->getRequest()->getParam('product_type', null);
            $export_child = $this->getRequest()->getParam('export_child', null);
            $status = $this->getRequest()->getParam('product_status', null);
            $out_of_stock = $this->getRequest()->getParam('product_out_of_stock', null);
            $selected_products = $this->getRequest()->getParam('selected_products', null);
            $stream = $this->getRequest()->getParam('stream', null);
            $limit = $this->getRequest()->getParam('limit', null);
            $offset = $this->getRequest()->getParam('offset', null);
            $ids_product = $this->getRequest()->getParam('ids_product', null);
            if($locale = $this->getRequest()->getParam('locale', null)) {
                // changing locale works! 
                Mage::app()->getLocale()->setLocale($locale);
                // needed to add this
                Mage::app()->getTranslator()->setLocale($locale);
                // translation now works
                Mage::app()->getTranslator()->init('frontend', true);
            }
            if($currency = $this->getRequest()->getParam('currency', null)) {
                $generate->setCurrentCurrencyCode($currency);
            }
            $generate->exec($id_store, $mode, $format, $types, $status, $export_child, $out_of_stock, $selected_products, $stream, $limit, $offset, $ids_product);
        } else {
            echo Mage::helper('export')->__('Unauthorised IP : %s', $_SERVER['REMOTE_ADDR']);
        }
    } 
}

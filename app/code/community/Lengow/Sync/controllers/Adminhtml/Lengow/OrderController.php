<?php
/**
 * Lengow adminhtml sync order controller
 *
 * @category    Lengow
 * @package     Lengow_Sync
 * @author      Ludovic Drin <ludovic@lengow.com>
 * @copyright   2013 Lengow SAS 
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Lengow_Sync_Adminhtml_Lengow_OrderController extends Mage_Adminhtml_Controller_Action {

    /**
     * Checks permissions
     *
     * @return boolean
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('lengow/sync');
    }

    protected function _initAction() {
        $this->loadLayout()
             ->_setActiveMenu('lengow/order')
             ->_addBreadcrumb(Mage::helper('sync')->__('Lengow manage orders'), Mage::helper('sync')->__('Lengow orders'));
        return $this;
    }
    
    
    public function indexAction() {
        $this->_initAction()
             ->renderLayout();      
        return $this;
    }
    
    public function gridAction() {
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('sync/adminhtml_order_grid')->toHtml()
        );
        return $this;
    }
    
    /**
     * Export order grid to CSV format
     */
    public function exportCsvAction() {
        $filename   = 'orders_lengow.csv';
        $grid       = $this->getLayout()->createBlock('sync/adminhtml_order_grid');
        $this->_prepareDownloadResponse($filename, $grid->getCsvFile());
    }

    /**
     *  Export order grid to Excel XML format
     */
    public function exportExcelAction() {
        $filename   = 'orders_lengow.xml';
        $grid       = $this->getLayout()->createBlock('sync/adminhtml_order_grid');
        $this->_prepareDownloadResponse($filename, $grid->getExcelFile($filename));
    }
    
    public function importAction() {
        //if(!Mage::getSingleton('sync/config')->importCanStart()) {
        //    Mage::helper('sync/data')->log('##Error manuel import : import is already started##');
        //    $this->_getSession()->addError(Mage::helper('sync')->__('Import is already started'));
        //    $this->_redirect('*/*/index');
        //} else {
            Mage::helper('sync/data')->log('##Start manual import##');
            $_force_update = $this->getRequest()->getParam('update');
            $_default_store = Mage::getModel('core/store')->load(Mage::app()
                                                                     ->getWebsite(true)
                                                                     ->getDefaultGroup()
                                                                     ->getDefaultStoreId());
            $_current_id_store = $_default_store->getId();
            $_lengow_id_user_global = Mage::getModel('sync/config')->setStore($_current_id_store)->getConfig('tracker/general/login');
            $_lengow_id_group_global = $this->_cleanGroup(Mage::getModel('sync/config')->setStore($_current_id_store)->getConfig('tracker/general/group'));
            $result_new = 0;
            $result_update = 0;
            $result_new = 0;
            $_default_is_imported = false;
            $import = Mage::getModel('sync/import');
            $import->setForceUpdate($_force_update);
            // Import default shop if configured
            if($_lengow_id_user_global && $_lengow_id_group_global) {
                try {
                    $_default_is_imported = true;
                    Mage::helper('sync/data')->log('Start manual import in store ' . $_default_store->getName() . '(' . $_default_store->getId() . ')');
                    $days = Mage::getModel('sync/config')->setStore($_default_store->getId())
                                                         ->getConfig('sync/orders/period');     
                    $date_from = date('Y-m-d', strtotime(date('Y-m-d') . '-' . $days . 'days'));
                    $date_to = date('Y-m-d');
                    $result = $import->exec('orders', array('dateFrom' => $date_from,
                                                            'dateTo' => $date_to,
                                                            'id_store' => $_current_id_store)); 
                    $result_new = $result['new'];
                    $result_update = $result['update'];
                } catch(Exception $e) {
                    $this->_getSession()->addError($e->getMessage());
                    Mage::helper('sync/data')->log('Error ' . $e->getMessage() . '');
                } 
            }
            $store_collection = Mage::getResourceModel('core/store_collection')
                                    ->addFieldToFilter('is_active', 1);
            // Import different view if is different
            foreach($store_collection as $store) {
                try {
                    $_lengow_id_user_current = Mage::getModel('sync/config')->setStore($store->getId())->getConfig('tracker/general/login');
                    $_lengow_id_group_current = $this->_cleanGroup(Mage::getModel('sync/config')->setStore($store->getId())->getConfig('tracker/general/group'));
                    if(($_lengow_id_user_current != $_lengow_id_user_global || $_lengow_id_group_current != $_lengow_id_group_global)
                       && $_lengow_id_user_current && $_lengow_id_group_current) {
                        if($store->getId() != $_current_id_store || !$_default_is_imported) {
                            Mage::helper('sync/data')->log('Start manual import in store ' . $store->getName() . '(' . $store->getId() . ')');
                            $days = Mage::getModel('sync/config')->setStore($store->getId())
                                                                 ->getConfig('sync/orders/period');
                            $date_from = date('Y-m-d', strtotime(date('Y-m-d') . '-' . $days . 'days'));
                            $date_to = date('Y-m-d');
                            $import = Mage::getModel('sync/import');
                            $import->setForceUpdate($_force_update);
                            $result = $import->exec('orders', array('dateFrom' => $date_from,
                                                                    'dateTo' => $date_to,
                                                                    'id_store' => $store->getId())); 
                            $result_new += $result['new'];
                            $result_update += $result['update'];
                        }
                    }
                } catch(Exception $e) {
                    $this->_getSession()->addError($e->getMessage());
                    Mage::helper('sync/data')->log('Error ' . $e->getMessage() . '');
                } 
            }      
            if($result_new > 0)
                $this->_getSession()->addSuccess(Mage::helper('sync')->__('%d orders are imported', $result['new']));
            if($result_update > 0)
                $this->_getSession()->addSuccess(Mage::helper('sync')->__('%d orders are updated', $result['update']));
            if($result_new == 0 && $result_update == 0)
                $this->_getSession()->addSuccess(Mage::helper('sync')->__('No order available to import'));
            Mage::helper('sync/data')->log('##End manual import##');
            $this->_redirect('*/*/index');
        //}
    }

    private function _cleanGroup($data) {
        return trim(str_replace(array("\r\n", ';', '-', '|', ' '), ';', $data), ',');
    }
    
}
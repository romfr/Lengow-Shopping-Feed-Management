<?php
/**
 * Lengow adminhtml sync order controller
 *
 * @category    Lengow
 * @package     Lengow_Sync
 * @author      Ludovic Drin <ludovic@lengow.com> & Benjamin Le Nevé <benjamin.le-neve@lengow.com>
 * @copyright   2015 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Lengow_Sync_Adminhtml_Lengow_OrderController extends Mage_Adminhtml_Controller_Action {

    /**
     *  Init Lengow orders menu
     */
    protected function _initAction()
    {
        $this->loadLayout()
             ->_setActiveMenu('lengow/order')
             ->_addBreadcrumb(Mage::helper('lensync')->__('Lengow manage orders'), Mage::helper('lensync')->__('Lengow orders'));
        return $this;
    }

    /**
     *  Render the layout
     */
    public function indexAction() {
        $this->_initAction()
             ->renderLayout();
        return $this;
    }

    /**
     *  Create the order grid
     */
    public function gridAction() {
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('lensync/adminhtml_order_grid')->toHtml()
        );
        return $this;
    }

    /**
     * Export order grid to CSV format
     */
    public function exportCsvAction()
    {
        $filename   = 'orders_lengow.csv';
        $grid       = $this->getLayout()->createBlock('lensync/adminhtml_order_grid');
        $this->_prepareDownloadResponse($filename, $grid->getCsvFile());
    }

    /**
     *  Export order grid to Excel XML format
     */
    public function exportExcelAction()
    {
        $filename   = 'orders_lengow.xml';
        $grid       = $this->getLayout()->createBlock('lensync/adminhtml_order_grid');
        $this->_prepareDownloadResponse($filename, $grid->getExcelFile($filename));
    }

    /**
     *  Imports orders for each store
     */
    public function importAction()
    {
        // update marketplace.xml file
        Mage::helper('lensync/data')->updateMarketplaceXML();
        // clean old log (20 days)
        Mage::helper('lensync/data')->cleanLog();
        // check if import is not already in process
        if (!Mage::getSingleton('lensync/config')->importCanStart()) {
            Mage::helper('lensync/data')->log('## Error manuel import : import is already started ##');
            $this->_getSession()->addError(Mage::helper('lensync')->__('Import is already started'));
            $this->_redirect('*/*/index');
        } else {
            Mage::helper('lensync/data')->log('## Start manual import ##');

            if(Mage::getStoreConfig('lensync/performances/debug'))
                Mage::helper('lensync/data')->log('WARNING ! Debug mode is activated');

            $store_count = 0;
            $store_disabled = 0;
            $result_new = 0;
            $result_update = 0;
            $lengow_groups = array();

            $store_collection = Mage::getResourceModel('core/store_collection')
                                    ->addFieldToFilter('is_active', 1);
            
            foreach($store_collection as $store) {
                try {
                    if(!$store->getId())
                        continue;

                    $store_count++;
                    Mage::helper('lensync/data')->log('Start manual import in store ' . $store->getName() . ' (' . $store->getId() . ')');

                    $lensync_config = Mage::getModel('lensync/config', array('store' => $store));
                    // if store is enabled -> stop import
                    if(!$lensync_config->get('orders/active_store')) {
                        Mage::helper('lensync/data')->log('Stop manual import - Store ' . $store->getName() . '(' . $store->getId() . ') is disabled');
                        $store_disabled++;
                        continue;
                    }
                    // get login informations
                    $error_import = false;
                    $lentracker_config = Mage::getModel('lentracker/config', array('store' => $store));
                    $id_lengow_customer = $lentracker_config->get('general/login');
                    $id_lengow_group    = $this->_cleanGroup($lentracker_config->get('general/group'));
                    $api_token_lengow   = $lentracker_config->get('general/api_key');
                    // if ID Customer or token API are empty -> stop import
                    if (empty($id_lengow_customer) || !is_numeric($id_lengow_customer) || empty($api_token_lengow)) {
                        $message = 'Please checks your plugin configuration. ID customer or token API is empty';
                        $this->_getSession()->addError(Mage::helper('lensync')->__($message));
                        Mage::helper('lensync/data')->log($message);
                        break;
                    }
                    // if ID group is empty -> stop import for current store
                    if (empty($id_lengow_group)) {
                        $message = 'ID group is empty. Please make sure it is saved in your plugin configuration';
                        Mage::helper('lensync/data')->log('Stop manual import in store ' . $store->getName() . '(' . $store->getId() . ') : ' . $message);
                        $error_import = true;
                    }
                    // check if group was already imported
                    $new_id_lengow_group = false;
                    $id_groups = explode(',', $id_lengow_group);
                    foreach ($id_groups as $id_group) {
                        if (is_numeric($id_group) && !in_array($id_group, $lengow_groups)) {
                            $lengow_groups[] = $id_group;
                            $new_id_lengow_group .= !$new_id_lengow_group ? $id_group : ','.$id_group;
                        }
                    }
                    // start import for current store 
                    if (!$error_import && $new_id_lengow_group) {
                        $days = $lensync_config->get('orders/period');
                        $args = array(
                            'dateFrom'     => date('Y-m-d', strtotime(date('Y-m-d') . '-' . $days . 'days')),
                            'dateTo'       => date('Y-m-d'),
                            'config'       => $lensync_config,
                            'idCustomer'   => $id_lengow_customer,
                            'idGroup'      => $new_id_lengow_group,
                            'apiToken'     => $api_token_lengow,
                        );
                        $import = Mage::getModel('lensync/import', $args);
                        $result = $import->exec();
                        $result_new += $result['new'];
                        $result_update += $result['update'];
                    }
                } catch(Exception $e) {
                    $this->_getSession()->addError($e->getMessage());
                    Mage::helper('lensync/data')->log('Error ' . $e->getMessage() . '');
                }
            }
            if($result_new > 0) {
                $this->_getSession()->addSuccess(Mage::helper('lensync')->__('%d orders are imported', $result_new));
                Mage::helper('lensync/data')->log($result_new . ' orders are imported');
            }
            if($result_update > 0) {
                $this->_getSession()->addSuccess(Mage::helper('lensync')->__('%d orders are updated', $result_update));
                Mage::helper('lensync/data')->log($result_update . ' orders are updated');
            }
            if($result_new == 0 && $result_update == 0) {
                $this->_getSession()->addSuccess(Mage::helper('lensync')->__('No order available to import'));
                Mage::helper('lensync/data')->log('No order available to import');
            }
            if ($store_count == $store_disabled) {
                $this->_getSession()->addError(Mage::helper('lensync')->__('Please checks your plugin configuration. No store enabled to import'));
                Mage::helper('lensync/data')->log('Please checks your plugin configuration. No store enabled to import');
            }
            Mage::helper('lensync/data')->log('## End manual import ##');
            Mage::getSingleton('lensync/config')->importSetEnd();
            $this->_redirect('*/*/index');
        }
    }

    /**
     *  Clean group id
     *
     * @param string $data
     */
    private function _cleanGroup($data)
    {
        return trim(str_replace(array("\r\n", ';', '-', '|', ' '), ',', $data), ',');
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('lengow/sync');
    }

}
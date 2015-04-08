<?php
/**
 * Lengow sync model import
 *
 * @category    Lengow
 * @package     Lengow_Sync
 * @author      Ludovic Drin <ludovic@lengow.com>
 * @copyright   2013 Lengow SAS 
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Lengow_Sync_Model_Import extends Varien_Object {

    /**
     * @var Mage_Sales_Model_Quote
     */
    protected $_quote = null;
    
    /**
     * @var Mage_Customer_Model_Customer
     */
    protected $_customer = null;
    
    /**
    * Config Data of Module Manageorders
    * @var Profileolabs_Lengow_Model_Manageorders_Config
    */
    protected $_config = null;
    
    protected $_ordersIdsImported = array();
    
    protected $_orderIdsAlreadyImported = array();
    
    protected $_result;
    
    protected $_resultSendOrder = "";
    
    protected $_isUnderVersion14 = null;
     /**
     * Product model
     *
     * @var Mage_Catalog_Model_Product
     */
    protected $_productModel;

    protected $store_id = 0;    

    protected $_helper;    

    public static $import_start = false;


    /**
     * Construct the import manager
     *
     * @param $command varchar The command of import
     * @param mixed
     */
    public function exec($command = 'orders', $args = array()) {
        switch ($command) {
            case 'orders':
                return $this->_importOrders($args);
            default:
                return $this->_importOrders($args);
        }
    }

    /**
      * Makes the Orders API Url and imports all orders
      *
      * @param array $args The arguments to request at the API
      */
    protected function _importOrders($args = array()) {
        if(Mage::app()->getStore()->getCode() != 'admin')
            Mage::app()->setCurrentStore('admin');          
        $this->_helper = Mage::helper('sync/data');        
        $this->_config = Mage::getSingleton('sync/config')
                             ->setStore($args['id_store']);
        $this->_checkVersionMarketplaceXML();
        $connector = Mage::getSingleton('sync/connector');    
        self::$import_start = true;
        $count_orders_updated = 0;
        $count_orders_added = 0;
        $connector->init((integer) $this->_config->getConfig('tracker/general/login'), 
                                   $this->_config->getConfig('tracker/general/api_key'));
        $orders = $connector->api('commands', array('dateFrom' => $args['dateFrom'],
                                                    'dateTo' => $args['dateTo'],
                                                    'id_group' => $this->_config->getConfig('tracker/general/group'),
                                                    'state' => 'plugin'));
        if(!is_object($orders)) {
            $error = $this->_helper->__('Error on lengow webservice');
            $this->_helper->log($error);
            return array('new' => 0,
                         'update' => 0,
                         'error' => $error);
        } else {
            $find_count_orders = count($orders->orders->order);
            $this->_helper->log('Find ' . $find_count_orders . ' order' . ($find_count_orders > 1 ? 's' : ''));
        }
        //LengowCore::debug($orders);
        $count_orders = (integer) $orders->orders_count->count_total;
        if(isset($orders->error) && (string) $orders->error == 'No Way') {
            $error = $this->_helper->__('API\'s connection refused with IP %s', (string) $orders->ip);
            $this->_helper->log($error);
            return array('new' => 0,
                         'update' => 0,
                         'error' => $error);
        }
        if($count_orders == 0) {
            $this->_helper->log('No orders to import between ' . $args['dateFrom'] . ' and ' . $args['dateTo'], $this->force_log_output);
            return false;
        }
        $model_order = Mage::getModel('sync/order', $args['id_store']);
        foreach($orders->orders->order as $key => $data) {
            $lengow_order = $data;
            $lengow_order_id = (string) $lengow_order->order_id;
            if($this->_config->isDebugMode() && !$this->getForceUpdate()) {
                $lengow_order_id = $lengow_order_id . time();
            }
            $marketplace = Mage::getModel('sync/marketplace');
            $marketplace->set((string) $lengow_order->marketplace);
            $id_flux = (integer) $lengow_order->idFlux;
            if((string) $lengow_order->order_status->marketplace == '') {
                $this->_helper->log('Order ' . $lengow_order_id . ' : no order\'s status');
                continue;
            }
            if((integer) $lengow_order->tracking_informations->tracking_deliveringByMarketPlace == 1) {
                $this->_helper->log('Order ' . $lengow_order_id . ' : delivry by the marketplace (' . $lengow_order->marketplace . ')');
                continue;
            }
            if($model_order->isAlreadyImported($lengow_order_id, $id_flux)) {
                $this->_helper->log('Order ' . $lengow_order_id . ' : already imported');
                $order_imported = $model_order->getOrderByIdLengow($lengow_order_id, $id_flux);
                $lengow_status = (string) $lengow_order->order_status->lengow;
                $this->_config->getOrderState($lengow_status);
                // Update status' order only if in process or shipped
                if($order_imported->getState() != $this->_config->getOrderState($lengow_status)) {
                    if($order_imported->getState() == $this->_config->getOrderState('new') // Change state process to processing
                       && $lengow_order->order_status->lengow == 'shipped') {
                        $model_order->toInvoice($order_imported);
                        $this->_helper->log('Order ' . $lengow_order_id . ' : update state to processing');
                        $count_orders_updated++;
                    } else if($order_imported->getState() == $this->_config->getOrderState('processing') // Change state process to shipped
                       && $lengow_order->order_status->lengow == 'shipped') {
                        $model_order->toShip($order_imported, 
                                                (string) $lengow_order->tracking_informations->tracking_carrier, 
                                                (string) $lengow_order->tracking_informations->tracking_method, 
                                                (string) $lengow_order->tracking_informations->tracking_number);
                        $this->_helper->log('Order ' . $lengow_order_id . ' : update state to shipped');
                        $count_orders_updated++;
                    } else if(($order_imported->current_state == $this->_config->getOrderState('process') // Change state process or shipped to cancel
                        || $order_imported->current_state == $this->_config->getOrderState('shipped'))
                       && $lengow_order->order_status->lengow == 'canceled') {
                        $model_order->toCancel($order_imported);
                        $this->_helper->log('Order ' . $lengow_order_id . ' : update state to cancel');
                        $count_orders_updated++;
                    }
                }
            } else {
                // Import only process order or shipped order and not imported with previous module
                $lengow_order_state = (string) $lengow_order->order_status->marketplace;
                $id_order_magento = (string) $lengow_order->order_external_id;
                if(($marketplace->getStateLengow($lengow_order_state) == 'processing'
                    || $marketplace->getStateLengow($lengow_order_state) == 'shipped' 
                    || ($marketplace->getStateLengow($lengow_order_state) == 'new' && $this->_config->get('orders/pending'))) && !$id_order_magento) {
                        // Check payment status 
                        $lengow_order_payment_status = (string) $lengow_order->order_payment->payment_status;
                        // Break new order if not in pending payment
                        $_to_order = true;
                        if($marketplace->getStateLengow($lengow_order_state) == 'new' && $this->_config->get('orders/pending')) {
                            $_to_order = false;
                            if(empty($lengow_order_payment_status) || !$marketplace->getStatePaymentLengow($lengow_order_payment_status) == 'pending') {
                                $this->_helper->log('Order ' . $lengow_order_id . ' : order\'s status ( ' . $lengow_order_state . ' - ' . $lengow_order_payment_status . ') not available to import');
                                continue;
                            }
                        }
                        $order = Mage::getModel('sync/order', $args['id_store']);
                        // Create or Update customer with addresses
                        $customer = Mage::getModel('sync/customer_customer');
                        $customer->setFromNode($data, $args['id_store']);
                        // Create order
                        $payment_status = '';
                        if(!$this->_config->get('orders/processing_fee')) {
                            $data->order_amount = new SimpleXMLElement('<order_amount><![CDATA[' . ((float) $data->order_amount - (float) $data->order_processing_fee) . ']]></order_amount>');
                            $data->order_processing_fee = new SimpleXMLElement('<order_processing_fee><![CDATA[ ]]></order_processing_fee>');
                            $this->_helper->log('Order ' . $lengow_order_id . ' : rewrite amount without processing fee');
                        }
                        // Create quote
                        if(!$quote = $order->createQuote($data, $customer)) {
                            $this->_helper->log('Order ' . $lengow_order_id . ' : create order fail');
                            continue;
                        }
                        $order = $order->makeOrder($data, $quote, $_to_order);
                        if($order) {
                            // Change order date
                            if($this->_config->get('orders/date_import')) {
                                $date = (string) $lengow_order->order_purchase_date . ' ' . (string) $lengow_order->order_purchase_heure;
                                $order->setCreatedAt($date);
                                $order->setUpdatedAt($date);
                                $order->save();
                            }
                            // Sync to lengow
                            if(!$this->_config->isDebugMode()) {
                                $orders = $connector->api('getInternalOrderId', array(
                                               'idClient' => (integer) $this->_config->getConfig('tracker/general/login'), 
                                               'idFlux' => $this->_config->getConfig('tracker/general/api_key'),
                                               'Marketplace' => (string) $lengow_order->marketplace,
                                               'idCommandeMP' => $lengow_order_id,
                                               'idCommandeMage' => $order->getId(),
                                               'statutCommandeMP' => (string) $lengow_order->order_status->lengow,
                                               'statutCommandeMage' => $order->getState(),
                                               'idQuoteMage' => $quote->getId(),
                                               'Message' => 'Import depuis: ' . (string) $lengow_order->marketplace . '<br/>idOrder: '.$lengow_order_id, 
                                               'type' => 'Magento'
                                ));
                                $this->_helper->log('Order ' . $lengow_order_id . ' : sync with Lengow (Order ' . $order->getId() . ')');
                            }
                            $count_orders_added++;                        
                            $this->_helper->log('Order ' . $lengow_order_id . ' : import success (' . $order->getId() . ')');
                            if($lengow_order->order_status->lengow == 'shipped') {
                                $id_flux = (integer) $lengow_order->idFlux;
                                $model_order = Mage::getModel('sync/order', $args['id_store']);
                                $order_imported = $model_order->getOrderByIdLengow($lengow_order_id, $id_flux);
                                $model_order->toShip($order, 
                                                (string) $lengow_order->tracking_informations->tracking_carrier, 
                                                (string) $lengow_order->tracking_informations->tracking_method, 
                                                (string) $lengow_order->tracking_informations->tracking_number);
                                $this->_helper->log('Order ' . $lengow_order_id . ' : update state to shipped');
                            }
                            // Clear session
                            Mage::getSingleton('checkout/session')->clear();
                        }
                } else {
                    if($id_order_magento) {
                        $this->_helper->log('Order ' . $lengow_order_id . ' : already imported in Magento with order ID ' . $id_order_magento);
                    } else {
                        $this->_helper->log('Order ' . $lengow_order_id . ' : order\'s status ( ' . $lengow_order_state . ') not available to import');
                    }
                }
            }
        }
        self::$import_start = false;
        return array('new' => $count_orders_added,
                     'update' => $count_orders_updated);
    }

    private function _checkVersionMarketplaceXML() {
        $mp_update = $this->_config->get('hidden/last_synchro');
        if (!$mp_update || !$mp_update == '0000-00-00' ||$mp_update != date('Y-m-d'))
            $this->_config->updateMarketPlaceConfiguration();
    } 

}
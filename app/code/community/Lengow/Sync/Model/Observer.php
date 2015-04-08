<?php
/**
 * Lengow sync model observer
 *
 * @category    Lengow
 * @package     Lengow_Sync
 * @author      Ludovic Drin <ludovic@lengow.com>
 * @copyright   2013 Lengow SAS 
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Lengow_Sync_Model_Observer {

    public function import($observer)   {
        //if(!Mage::getSingelton('sync/config')->importCanStart()) {
        //    Mage::helper('sync/data')->log('##Error cron import : import is already started##');
        //} else {
            Mage::helper('sync/data')->log('##Start cron import##');
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
            // Import default shop if configured
            if($_lengow_id_user_global && $_lengow_id_group_global) {
                try {
                    $_default_is_imported = true;
                    Mage::helper('sync/data')->log('Start cron import in store ' . $_default_store->getName() . '(' . $_default_store->getId() . ')');
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
                    Mage::helper('sync/data')->log('Error : ' . $e->getMessage());
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
                            Mage::helper('sync/data')->log('Start cron import in store ' . $store->getName() . '(' . $store->getId() . ')');
                            $days = Mage::getModel('sync/config')->setStore($store->getId())
                                                                 ->getConfig('sync/orders/period');
                            $date_from = date('Y-m-d', strtotime(date('Y-m-d') . '-' . $days . 'days'));
                            $date_to = date('Y-m-d');
                            $import = Mage::getModel('sync/import');
                            $result = $import->exec('orders', array('dateFrom' => $date_from,
                                                                    'dateTo' => $date_to,
                                                                    'id_store' => $store->getId())); 
                            $result_new += $result['new'];
                            $result_update += $result['update'];
                        }
                    }
                } catch(Exception $e) {
                    Mage::helper('sync/data')->log('Error : ' . $e->getMessage());
                } 
            }      
            Mage::helper('sync/data')->log('##End cron import##');
        //}
     }

	public function salesOrderShipmentSaveAfter(Varien_Event_Observer $observer) {
        $shipment = $observer->getEvent()->getShipment();
        $order = $shipment->getOrder();
        if($order->getData('from_lengow') == 1) {          
	        $marketplace = Mage::getModel('sync/marketplace');
	        $marketplace->set($order->getMarketplaceLengow());
	        if ($order->getState() == Mage::getSingleton('sync/config')->getOrderState('processing')) {
	        	Mage::helper('sync')->log('WDSL : send tracking to ' . $order->getData('marketplace_lengow') . ' - ' . $order->getData('feed_id_lengow') . ' - ' . $order->getData('order_id_lengow'));
	            $marketplace->wsdl('shipped', $order->getData('feed_id_lengow'), $order, $shipment);
	        }
	    }
        return $this;
    }

	public function salesOrderPaymentCancel(Varien_Event_Observer $observer) {
		$payment = $observer->getEvent()->getPayment();
        $order = $payment->getOrder();
        if($order->getData('from_lengow') == 1) {        
	        $marketplace = Mage::getModel('sync/marketplace');
	        $marketplace->set($order->getMarketplaceLengow());
	        if ($order->getState() == Mage::getSingleton('sync/config')->getOrderState('processing')) {
	        	Mage::helper('sync')->log('WDSL : send cancel to ' . $order->getData('marketplace_lengow') . ' - ' . $order->getData('feed_id_lengow') . ' - ' . $order->getData('order_id_lengow'));
	            $marketplace->wsdl('refuse', $order->getData('feed_id_lengow'), $order);
	        }
        }
    }

	public function salesOrderSaveCommitAfter(Varien_Event_Observer $observer) {
		$order = $observer->getEvent();
        if($order->getData('from_lengow') == 1) {  
	        $marketplace = Mage::getModel('sync/marketplace');
	        $marketplace->set($order->getMarketplaceLengow());
	        if ($order->getState() == self::STATE_COMPLETE && $order->getState() == Mage::getSingleton('sync/config')->getOrderState('processing')) {
	        	Mage::helper('sync')->log('WDSL : send cancel to ' . $order->getData('marketplace_lengow') . ' - ' . $order->getData('feed_id_lengow') . ' - ' . $order->getData('order_id_lengow'));
	            $marketplace->wsdl('shipped', $order->getData('feed_id_lengow'), $order);
	        }
        }
	}

    private function _cleanGroup($data) {
        return trim(str_replace(array("\r\n", ';', '-', '|', ' '), ';', $data), ',');
    }

}
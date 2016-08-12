<?php
/**
 * Lengow sync model observer
 *
 * @category    Lengow
 * @package     Lengow_Sync
 * @author      Ludovic Drin <ludovic@lengow.com> & Benjamin Le Nevé <benjamin.le-neve@lengow.com>
 * @copyright   2015 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Lengow_Sync_Model_Observer {

    /**
     * Imports orders for each store with cron job
     */
    public function import($observer)
    {
        if(Mage::getStoreConfig('lensync/performances/active_cron')) {
            // update marketplace file
            Mage::helper('lensync/data')->updateMarketplaceXML();
            // clean old log (20 days)
            Mage::helper('lensync/data')->cleanLog();
            // check if import is not already in process
            if (!Mage::getSingleton('lensync/config')->importCanStart()) {
                Mage::helper('lensync/data')->log('## Error cron import : import is already started ##');
            } else {
                Mage::helper('lensync/data')->log('## Start cron import ##');

                if(Mage::getStoreConfig('lensync/performances/debug'))
                    Mage::helper('lensync/data')->log('WARNING ! Debug mode is activated');

                $result_new = 0;
                $result_update = 0;
                $lengow_groups = array();

                $store_collection = Mage::getResourceModel('core/store_collection')
                                        ->addFieldToFilter('is_active', 1);
                // Import different view if is different
                foreach($store_collection as $store) {
                    try {
                        if(!$store->getId())
                            continue;

                        Mage::helper('lensync/data')->log('Start cron import in store ' . $store->getName() . ' (' . $store->getId() . ')');

                        $lensync_config = Mage::getModel('lensync/config', array('store' => $store));
                        // if store is enabled -> stop import
                        if(!$lensync_config->get('orders/active_store')) {
                            Mage::helper('lensync/data')->log('Stop cron import - Store ' . $store->getName() . '(' . $store->getId() . ') is disabled');
                            continue;
                        }
                        // get login informations
                        $error_import = false;
                        $lentracker_config = Mage::getModel('lentracker/config', array('store' => $store));
                        $id_lengow_customer = $lentracker_config->get('general/login');
                        $id_lengow_group    = $this->_cleanGroup($lentracker_config->get('general/group'));
                        $api_token_lengow   = $lentracker_config->get('general/api_key');
                        // if ID Customer or group are empty -> stop import for this store
                        if (empty($id_lengow_customer) || !is_numeric($id_lengow_customer) || empty($id_lengow_group)) {
                            $message = 'id customer or id group is empty. Please make sure it is saved in your plugin configuration';
                            Mage::helper('lensync/data')->log('Stop cron import in store ' . $store->getName() . '(' . $store->getId() . ') : ' . $message);
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
                        Mage::helper('lensync/data')->log('Error ' . $e->getMessage() . '');
                    }
                }
                if($result_new > 0)
                    Mage::helper('lensync/data')->log(Mage::helper('lensync')->__('%d orders are imported', $result_new));
                if($result_update > 0)
                    Mage::helper('lensync/data')->log(Mage::helper('lensync')->__('%d orders are updated', $result_update));
                if($result_new == 0 && $result_update == 0)
                    Mage::helper('lensync/data')->log(Mage::helper('lensync')->__('No order available to import'));
                Mage::helper('lensync/data')->log('## End cron import ##');
                Mage::getSingleton('lensync/config')->importSetEnd();
            }
        }
    }

	/**
     * Sending a call WSDL for a new order shipment
     */
    public function salesOrderShipmentSaveAfter(Varien_Event_Observer $observer)
    {
        $shipment = $observer->getEvent()->getShipment();
        $order = $shipment->getOrder();
        if($order->getData('from_lengow') == 1) {
	        $marketplace = Mage::getModel('lensync/marketplace');
	        $marketplace->set($order->getMarketplaceLengow());
	        if ($order->getState() == Mage::getSingleton('lensync/order')->getOrderState('processing')) {
	        	Mage::helper('lensync')->log('WSDL : send tracking to ' . $order->getData('marketplace_lengow') . ' - ' . $order->getData('feed_id_lengow'), $order->getData('order_id_lengow'));
	            $marketplace->wsdl('shipped', $order->getData('feed_id_lengow'), $order, $shipment);
	        }
	    }
        return $this;
    }

    // /**
    //  * Sending a call WSDL for a new tracking
    //  */
    // public function salesOrderShipmentTrackSaveAfter(Varien_Event_Observer $observer) {
    //     $track = $observer->getEvent()->getTrack();
    //     $shipment = $track->getShipment();
    //     $order = $shipment->getOrder();
    //     if($order->getData('from_lengow') == 1) {
    //         $marketplace = Mage::getModel('lensync/marketplace');
    //         $marketplace->set($order->getMarketplaceLengow());
    //         if ($order->getState() == Mage::getSingleton('lensync/order')->getOrderState('shipped')) {
    //             Mage::helper('lensync')->log('WSDL : send tracking to ' . $order->getData('marketplace_lengow') . ' - ' . $order->getData('feed_id_lengow'), $order->getData('order_id_lengow'));
    //             $marketplace->wsdl('shipped', $order->getData('feed_id_lengow'), $order, $shipment);
    //         }
    //     }
    //     return $this;
    // }

	/**
     * Sending a call for a cancellation of order
     */
    public function salesOrderPaymentCancel(Varien_Event_Observer $observer)
    {
		$payment = $observer->getEvent()->getPayment();
        $order = $payment->getOrder();
        if($order->getData('from_lengow') == 1) {
	        $marketplace = Mage::getModel('lensync/marketplace');
	        $marketplace->set($order->getMarketplaceLengow());
	        if ($order->getState() == Mage::getSingleton('lensync/order')->getOrderState('processing')) {
	        	Mage::helper('lensync')->log('WSDL : send cancel to ' . $order->getData('marketplace_lengow') . ' - ' . $order->getData('feed_id_lengow'), $order->getData('order_id_lengow'));
	            $marketplace->wsdl('refuse', $order->getData('feed_id_lengow'), $order);
	        }
        }
        return $this;
    }

	public function salesOrderSaveCommitAfter(Varien_Event_Observer $observer)
    {
		$order = $observer->getEvent();
        if($order->getData('from_lengow') == 1) {
	        $marketplace = Mage::getModel('sync/marketplace');
	        $marketplace->set($order->getMarketplaceLengow());
	        if ($order->getState() == self::STATE_COMPLETE && $order->getState() == Mage::getSingleton('lensync/order')->getOrderState('processing')) {
	        	Mage::helper('lensync')->log('WSDL : send cancel to ' . $order->getData('marketplace_lengow') . ' - ' . $order->getData('feed_id_lengow'), $order->getData('order_id_lengow'));
	            $marketplace->wsdl('shipped', $order->getData('feed_id_lengow'), $order);
	        }
        }
        return $this;
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

}
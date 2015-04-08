<?php

/**
 * Lengow_Tracker Tracking Block Simple
 *
 * @category   Lengow
 * @package    Lengow_Tracker
 * @author     Romain Le Polh <romain@lengow.com>
 */

class Lengow_Tracker_Block_Tag_Simple extends Mage_Core_Block_Template {
    
    protected $_config_model;
    
    public function __construct() {
        $this->_config_model = Mage::getSingleton('tracker/config');
        $this->setData('id_client', $this->_config_model->get('general/login'));
        $this->setData('id_group', $this->_config_model->get('general/group'));
    }
    
    protected function _prepareLayout() {
        parent::_prepareLayout();
        $tracker_model = Mage::getSingleton('tracker/tracker');
        if(Mage::app()->getRequest()->getActionName() == 'success') {
            $order_id = Mage::getSingleton('checkout/session')->getLastOrderId();
            $order = Mage::getModel('sales/order')->load($order_id);
            $this->setData('mode_paiement', $order->getPayment()->getMethodInstance()->getCode());
            $this->setData('id_order', $order_id);
            $this->setData('total_paid', $order->getGrandTotal());
            $this->setData('ids_products', $tracker_model->getIdsProducts($order));
            $this->setTemplate('lengow/tracker/simpletag.phtml');
        }
        return $this;
    }
    
}
<?php

/**
 * Lengow_Tracker Tracking Block Capsule
 *
 * @category   Lengow
 * @package    Lengow_Tracker
 * @author     Romain Le Polh <romain@lengow.com>
 */
class Lengow_Dashboard_Block_Adminhtml_Dashboard_Charts extends Mage_Core_Block_Template {

    protected $_data = array();
    
    public function __construct() {
        parent::__construct();
        
        $this->setData('config_model', Mage::getSingleton('dashboard/config'));
        $this->setData('id_client', $this->getData('config_model')->get('general/login'));
        $this->setData('id_group', $this->getData('config_model')->get('general/group'));
        $this->setData('api_key', $this->getData('config_model')->get('general/api_key'));
    }

    protected function _prepareLayout() {
        parent::_prepareLayout();
        $this->setTemplate('lengow/dashboard/charts.phtml');
        return $this;
    }
}
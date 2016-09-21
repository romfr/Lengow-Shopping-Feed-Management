<?php


class Lengow_Sync_Model_System_Config_Source_Shipping_Method extends Mage_Core_Model_Config_Data
{
    public function toOptionArray() {
        $store = Mage::getModel('core/store')
                        ->load(Mage::getSingleton('adminhtml/config_data')->getStore());

        $carrier_code = Mage::getSingleton('lensync/config')
                               ->setStore($store)
                               ->get('orders/default_carrier');
        if (!$carrier_code)
            return array();
        $carrier = Mage::getModel('shipping/shipping')
                       ->getCarrierByCode($carrier_code, $store->getId());
        $methods = $carrier->getAllowedMethods();
        if (count($methods) == 0 )
            return array();

        $select = array();
        $index = 0;
        foreach ($methods as $method) {

            $select[$carrier_code.'--'.Mage::helper('lensync/data')->cleanMethod($method)] = $method;
        }
        return $select;
    }

    public function toSelectArray() {
        $store = Mage::getModel('core/store')
                     ->load(Mage::getSingleton('adminhtml/config_data')->getStore());

        $carrier_code = Mage::getSingleton('lensync/config')
                               ->setStore($store)
                               ->get('orders/default_carrier');
        if (!$carrier_code)
            return array();
        $carrier = Mage::getModel('shipping/shipping')
                       ->getCarrierByCode($carrier_code, $store->getId());
        $methods = $carrier->getAllowedMethods();
        if (count($methods) == 0 )
            return array();

        $select = array();
        $index = 0;
        foreach ($methods as $method) {
            $select[$index] = $method;
        }
        return $select;
    }
}

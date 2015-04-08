<?php

class Lengow_Tracker_Model_System_Config_Source_Identifiant extends Mage_Core_Model_Config_Data {

    public function toOptionArray() {
        return array(
            array('value' => 'sku', 'label' => Mage::helper('adminhtml')->__('Sku')),
            array('value' => 'entity_id', 'label' => Mage::helper('adminhtml')->__('ID product')),
        );
    }

}
<?php

class Lengow_Tracker_Model_System_Config_Source_Tracker extends Mage_Core_Model_Config_Data {

    public function toOptionArray() {
        return array(
            array('value' => 'none', 'label' => Mage::helper('adminhtml')->__('Aucun')),
            array('value' => 'simpletag', 'label' => Mage::helper('adminhtml')->__('SimpleTag')),
            array('value' => 'tagcapsule', 'label' => Mage::helper('adminhtml')->__('TagCapsule')),
        );
    }

}
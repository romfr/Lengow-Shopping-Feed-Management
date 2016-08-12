<?php
/**
 * Lengow export model system config source status
 * Status of product to export
 *
 * @category    Lengow
 * @package     Lengow_Export
 * @author      Ludovic Drin <ludovic@lengow.com>
 * @copyright   2013 Lengow SAS 
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Lengow_Export_Model_System_Config_Source_Status extends Mage_Core_Model_Config_Data {

    public function toOptionArray() {
        return array(
            array('value' => Mage_Catalog_Model_Product_Status::STATUS_ENABLED, 
            	  'label' => Mage::helper('adminhtml')->__('Enabled')),
            array('value' => Mage_Catalog_Model_Product_Status::STATUS_DISABLED, 
            	  'label' => Mage::helper('adminhtml')->__('Disabled')),
            array('value' => Mage_Catalog_Model_Product_Status::STATUS_ENABLED . ',' . Mage_Catalog_Model_Product_Status::STATUS_DISABLED, 
            	  'label' => Mage::helper('adminhtml')->__('Enabled') . ', ' . Mage::helper('adminhtml')->__('Disabled')),
        );
    }
    
    public function toSelectArray() {
        $select = array();
        foreach($this->toOptionArray() as $option) {
            $select[$option['value']] = $option['label'];
        }
        return $select;
    }

}
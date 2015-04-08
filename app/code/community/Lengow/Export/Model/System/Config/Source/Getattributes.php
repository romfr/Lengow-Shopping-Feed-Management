<?php
/**
 * Lengow export model config
 *
 * @category    Lengow
 * @package     Lengow_Export
 * @author      Ludovic Drin <ludovic@lengow.com>
 * @copyright   2013 Lengow SAS 
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Lengow_Export_Model_System_Config_Source_Getattributes extends Mage_Core_Model_Config_Data {

    public function toOptionArray() {
        $attribute = Mage::getResourceModel('eav/entity_attribute_collection')
                           ->setEntityTypeFilter(Mage::getModel('catalog/product')
                           ->getResource()
                           ->getTypeId());
        $attributeArray = array();        
        foreach ($attribute as $option) {
            $attributeArray[] = array(
                'value' => $option->getAttributeCode(),
                'label' => $option->getAttributeCode()
            );
        }    
        return $attributeArray;
    }

}

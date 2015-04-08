<?php
/**
 * Lengow export model convert parser product
 *
 * @category    Lengow
 * @package     Lengow_Export
 * @author      Ludovic Drin <ludovic@lengow.com>
 * @copyright   2013 Lengow SAS 
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Lengow_Export_Model_Convert_Parser_Product extends Mage_Catalog_Model_Convert_Parser_Product {

    /**
     * Retrieve accessible external product attributes
     *
     * @return array
     */
    public function getExternalAttributes() {
    	$productAttributes = array();
    	if(file_exists(Mage::getModuleDir(null,'Mage_Catalog') . 'Model/Resource/Eav/Mysql4/Product/Attribute/Collection')) {
        	$productAttributes  = Mage::getResourceModel('catalog/product_attribute_collection')->load();
        } else {
	        $entityTypeId = Mage::getSingleton('eav/config')->getEntityType('catalog_product')->getId();
	        $productAttributes = Mage::getResourceModel('eav/entity_attribute_collection')
                	                 ->setEntityTypeFilter($entityTypeId)
                	               ->load();
        }
        $attributes = $this->_externalFields;
        foreach ($productAttributes as $attr) {
            $code = $attr->getAttributeCode();
            if (in_array($code, $this->_internalFields) || $attr->getFrontendInput() == 'hidden') {
                continue;
            }
            $attributes[$code] = $code;
        }
        foreach ($this->_inventoryFields as $field) {
            $attributes[$field] = $field;
        }
        return $attributes;
    }

}

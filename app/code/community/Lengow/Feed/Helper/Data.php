<?php
/**
 * Lengow Feed Helper
 * 
 * @category    Lengow
 * @package     Lengow_Feed
 * @author      Romain Le Polh <romain@lengow.com>
 * @copyright   2013 Lengow SAS 
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Lengow_Feed_Helper_Data extends Mage_Core_Helper_Abstract {
    
    /**
     * Get list of available export format
     * 
     * @return array File format
     */
    public function getArrayFormat() {
        $format_array = Mage::getModel('export/system_config_source_format')->toOptionArray();
        $formats = array();
        foreach($format_array as $format) {
            $formats[] = $format['value'];
        }
        return $formats;
    }
    
    /**
     * Get list of product type export
     * 
     * @return array Product type
     */
    public function getProductTypeFormat() {
        $product_type_array = Mage::getModel('export/system_config_source_types')->toOptionArray();
        $types = array();
        foreach($product_type_array as $type) {
            $types[] = $type['value'];
        }
    }
    
    /**
     * Get Sync connector
     * 
     * @return mixed Lengow connector object
     */
    public function getConnector($id_client, $api_key) {
        $connector = Mage::getSingleton('sync/connector');
        $connector->init((integer) $id_client, $api_key);
        return $connector;
    }
    
}
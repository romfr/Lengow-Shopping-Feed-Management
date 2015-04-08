<?php
/**
 * Lengow sync model systems config source customer group 
 * group of customer
 *
 * @category    Lengow
 * @package     Lengow_Sync
 * @author      Ludovic Drin <ludovic@lengow.com>
 * @copyright   2013 Lengow SAS 
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Lengow_Sync_Model_System_Config_Source_Customer_Group extends Mage_Core_Model_Config_Data {

    public function toOptionArray() {
        $collection = Mage::getModel('customer/group')->getCollection();
        $select = array();
        foreach ($collection as $group) {            /* @var $group Mage_Customer_Model_Group */
            $select[$group->getCustomerGroupId()] = $group->getCustomerGroupCode();
        }
        return $select;
    }
    
    public function toSelectArray() {
        $collection = Mage::getModel('customer/group')->getCollection();
        $select = array();
        foreach ($collection as $group) {
            /* @var $group Mage_Customer_Model_Group */
            $select[$group->getCustomerGroupId()] = $group->getCustomerGroupCode();
        }
        return $select;
    }
}
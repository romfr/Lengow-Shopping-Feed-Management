<?php
/**
 * Lengow select products block
 *
 * @category    Lengow
 * @package     Lengow_Sync
 * @author      Romain Le Pohl <romain@lengow.com>
 * @copyright   2013 Lengow SAS 
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Lengow_Sync_Block_Adminhtml_Order_Tab extends Mage_Adminhtml_Block_Sales_Order_Abstract
    implements Mage_Adminhtml_Block_Widget_Tab_Interface {
    
    protected function _construct() {
        $this->setTemplate('lengow/sales/order/tab/info.phtml');
    }

    /**
     * Retrieve order model instance
     *
     * @return Mage_Sales_Model_Order
     */
    public function getOrder() {
        return Mage::registry('current_order');
    }

    /**
     * Retrieve source model instance
     *
     * @return Mage_Sales_Model_Order
     */
    public function getSource() {
        return $this->getOrder();
    }


    public function getTabLabel() {
        return Mage::helper('sales')->__('Lengow');
    }

    public function getTabTitle() {
        return Mage::helper('sales')->__('Lengow');
    }

    public function canShowTab() {
        return true;
    }

    public function isHidden() {
        return false;
    }
    
    /**
     * Get fields array
     * 
     * @return Array
     */
    public function getFields() {
        $fields = array();
        $order = $this->getOrder();
        
        $fields[] = array(
            'label' => Mage::helper('sync')->__('Lengow order ID'),
            'value' => $order->getData('order_id_lengow'),
        );
        $fields[] = array(
            'label' => Mage::helper('sync')->__('Feed ID'),
            'value' => $order->getData('feed_id_lengow'),
        );
        $fields[] = array(
            'label' => Mage::helper('sync')->__('Marketplace'),
            'value' => $order->getData('marketplace_lengow'),
        );
        $fields[] = array(
            'label' => Mage::helper('sync')->__('Total paid'),
            'value' => $order->getData('total_paid_lengow'),
        );
        $fields[] = array(
            'label' => Mage::helper('sync')->__('Carrier'),
            'value' => $order->getData('carrier_lengow'),
        );
        $fields[] = array(
            'label' => Mage::helper('sync')->__('Carrier method'),
            'value' => $order->getData('carrier_method_lengow'),
        );
        $fields[] = array(
            'label' => Mage::helper('sync')->__('Message'),
            'value' => $order->getData('message_lengow'),
        );
        $fields[] = array(
            'label' => Mage::helper('sync')->__('Json Node'),
            'value' => '<textarea disabled="disabled">' . $order->getData('xml_node_lengow') . '</textarea>',
        );
        
        return $fields;
    }
    
    /**
     * Check if is a Lengow order
     * 
     * @return boolean
     */
    public function isLengowOrder() {
        return $this->getOrder()->getData('from_lengow');
    }
}

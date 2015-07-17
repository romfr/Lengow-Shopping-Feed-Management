<?php
/**
 * Lengow adminhtml export controller
 *
 * @category    Lengow
 * @package     Lengow_Feed
 * @author      Romain Le Polh <romain@lengow.com>
 * @copyright   2013 Lengow SAS 
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Lengow_Feed_Adminhtml_Lengow_FeedController extends Mage_Adminhtml_Controller_Action {

    /**
     * Checks permissions
     *
     * @return boolean
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('lengow/feed');
    }

    public function indexAction() {
        $this->loadLayout();
        $this->renderLayout();
        return $this;
    }

    /**
     * Product grid for AJAX request
     */
    public function gridAction() {
        $this->loadLayout();
        $this->getResponse()->setBody(
                $this->getLayout()->createBlock('feed/adminhtml_feed')->toHtml()
        );
    }

    protected function _getSession() {
        return Mage::getSingleton('adminhtml/session');
    }

    /**
     * Migrate feed
     */
    public function migrateAction() {
        
        $feed_ids = $this->getRequest()->getParam('feed_id');
        $selected_products = $this->getRequest()->getParam('selected_products');
        $product_out_stock = $this->getRequest()->getParam('product_out_stock');
        $product_type = $this->getRequest()->getParam('product_type');
        $product_status = $this->getRequest()->getParam('product_status');
        //$product_child = $this->getRequest()->getParam('product_child');
        $format = $this->getRequest()->getParam('format');
        $_lengow_ids_group = array();
        $_store_id = $this->getRequest()->getParam('store');
        Mage::helper('sync/data')->log('Test store : ' . $_store_id);
        $_group = Mage::getModel('sync/config')->setStore($_store_id)->getConfig('tracker/general/group');
        
        if($this->getRequest()->getParam('submit')) {
            $feed_ids = array($this->getRequest()->getParam('submit'));
        }
        
        $error = false;
        $message = '';
        $data_feeds = json_decode(Mage::getSingleton('feed/config')->get('feed/general/json_feed'));
        if(empty($data_feeds))
            $data_feeds = new stdClass;
        if(!empty($feed_ids)) {
            foreach($feed_ids as $feed_id) {
                
                $args = array(
                        'feed_id' => $feed_id,
                        'group_id' => $_group,
                        'store_id' => $_store_id,
                        'selected_products' => $selected_products[$feed_id],
                        'product_out_stock' => $product_out_stock[$feed_id],
                        'product_type' => $product_type[$feed_id],
                        'product_status' => $product_status[$feed_id],
                        //'product_child' => $product_child[$feed_id],
                        'format' => $format[$feed_id]);
                
                $feed = Mage::getModel('feed/feed', $args);
                if(!$feed->update()) {
                    $error = true;
                    $message .= Mage::helper('feed')->__('Error update feed %s', '#' . $feed_id . '<br />');
                } else {
                    $data_feeds->{$feed_id} = $args;
                }
            }
            Mage::getConfig()->saveConfig('feed/general/json_feed', json_encode($data_feeds));
        } else {
            $error = 'true';
            $message = Mage::helper('feed')->__('');
        }
        
        if($error)
            Mage::getSingleton('core/session')->addError($message);
        else
            Mage::getSingleton('core/session')->addSuccess(Mage::helper('feed')->__('Update success'));
        if(!empty($_store_id))
            $this->_redirect('*/*/index/', array('store' => $_store_id));
        else
            $this->_redirect('*/*/index');
    }

}

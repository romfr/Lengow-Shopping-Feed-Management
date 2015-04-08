<?php

/**
 * Lengow grid feed
 *
 * @category    Lengow
 * @package     Lengow_Feed
 * @author      Romain Le Polh <romain@lengow.com>
 * @copyright   2013 Lengow SAS 
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Lengow_Feed_Block_Adminhtml_Feed_Grid extends Mage_Adminhtml_Block_Widget_Grid {

    protected $_data = array();
    protected $_config_model;

    public function __construct() {
        parent::__construct();
        $this->setId('feed');
        $this->setDefaultSort('feed_id[]');
        $this->setDefaultDir('asc');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        $this->_filterVisibility = false;
        $this->_pagerVisibility = false;
        $this->_config_model = Mage::getSingleton('feed/config');

        // Set datas
        $this->setData('id_client', $this->_config_model->get('tracker/general/login'));
        $_lengow_ids_group = array();
        $_store_id = $this->getRequest()->getParam('store');
        /*if(!$_store_id) {
            $store_collection = Mage::getResourceModel('core/store_collection')
                                    ->addFieldToFilter('is_active', 1);
            foreach($store_collection as $store) {
                if(Mage::getModel('sync/config')->setStore($store->getId())->getConfig('tracker/general/group'))
                    $_lengow_ids_group = array_merge($this->_getGroups(Mage::getModel('sync/config')->setStore($store->getId())->getConfig('tracker/general/group')), $_lengow_ids_group);
            }
            $_group = implode(',', $_lengow_ids_group);
        } else {*/
            $_group = Mage::getModel('sync/config')->setStore($_store_id)->getConfig('tracker/general/group');
        //}
        $this->setData('id_group', $_group);
        $this->setData('api_key', $this->_config_model->get('tracker/general/api_key'));
        $args = array();
        if(!empty($_store_id))
            $args['store'] = $_store_id;
        $this->setData('form_url', $this->getUrl('*/*/migrate', $args));
        $this->setData('json_feed', $this->_config_model->get('feed/general/json_feed'));
        
    }

    protected function _getStore() {
        $storeId = (int) $this->getRequest()->getParam('store', 0);
        return Mage::app()->getStore($storeId);
    }

    
    protected function _prepareCollection() {
        $connector = Mage::helper('feed/data')->getConnector($this->getData('id_client'), $this->getData('api_key'));

        $args = array(
            'idClient' => (integer) $this->getData('id_client'),
            'idGroup' => (integer) $this->getData('id_group')
        );
        $feeds = $connector->api('getRootFeed', $args);
        $collection = new Varien_Data_Collection();
        $data_feeds = json_decode($this->getData('json_feed'));

        if(!empty($feeds['feeds'])) {
            foreach ($feeds['feeds'] as $key => $feed) {

                $obj_feed = new Varien_Object();

                $obj_feed->setData('id', $key);
                $obj_feed->setData('name', $feed['name']);
                $obj_feed->setData('url', $feed['url']);

                if(isset($data_feeds->{$key})) {
                    $obj_feed->setData('selected_products', $data_feeds->{$key}->selected_products);
                    $obj_feed->setData('product_out_stock', $data_feeds->{$key}->product_out_stock);
                    $obj_feed->setData('product_type', $data_feeds->{$key}->product_type);
                    $obj_feed->setData('product_status', $data_feeds->{$key}->product_status);
                    $obj_feed->setData('product_child', (!empty($data_feeds->{$key}->product_child) ? $data_feeds->{$key}->product_child : ''));
                    $obj_feed->setData('format', $data_feeds->{$key}->format);
                } else {
                    // Apply default parameters 
                    $obj_feed->setData('selected_products', $this->_config_model->get('export/global/export_only_selected'));
                    $obj_feed->setData('product_out_stock', $this->_config_model->get('export/global/export_soldout'));
                    $obj_feed->setData('product_type', explode(',', $this->_config_model->get('export/global/producttype')));
                    $obj_feed->setData('product_status', $this->_config_model->get('export/global/productstatus'));
                    $obj_feed->setData('product_child', $this->_config_model->get('export/global/productchildren'));
                    $obj_feed->setData('format', $this->_config_model->get('export/data/format'));
                }
                $collection->addItem($obj_feed);
            }
        } else {
            Mage::getSingleton('core/session')->addError(Mage::helper('feed')->__('Error API, please try to refresh the page.'));
        }
        $this->setCollection($collection);
        parent::_prepareCollection();
    }

    protected function _prepareColumns() {
        $this->addColumn('', array(
            'width' => '20px',
            'type' => 'checkbox',
            'index' => 'id',
            'filter' => false,
            'sortable' => false,
            'field_name' => 'feed_id[]',
        ));
        $this->addColumn('id', array(
            'header' => Mage::helper('feed')->__('Feed ID'),
            'width' => '50px',
            'index' => 'id',
            'filter' => false,
            'sortable' => false
        ));
        $this->addColumn('name', array(
            'header' => Mage::helper('feed')->__('Feed Name'),
            'index' => 'name',
            'filter' => false,
            'sortable' => false,
        ));
        $this->addColumn('url', array(
            'header' => Mage::helper('feed')->__('Current Feed'),
            'index' => 'url',
            'width' => '100px',
            'filter' => false,
            'sortable' => false,
            'renderer'  => 'feed/adminhtml_feed_renderer_url',
        ));
        $this->addColumn('selected_products', array(
            'header' => Mage::helper('feed')->__('Export only selected product'),
            'index' => 'selected_products',
            'type' => 'select',
            'width' => '50px',
            'options' => array(
                1 => __('Yes'),
                0 => __('No'),
            ),
            'renderer'  => 'feed/adminhtml_feed_renderer_select',
            'filter' => false,
            'sortable' => false,
        ));
        $this->addColumn('product_out_stock', array(
            'header' => Mage::helper('feed')->__('Export product out of stock'),
            'index' => 'product_out_stock',
            'options' => array(
                1 => __('Yes'),
                0 => __('No'),
            ),
            'renderer'  => 'feed/adminhtml_feed_renderer_select',
            'filter' => false,
            'sortable' => false,
        ));
        $this->addColumn('product_type', array(
            'header' => Mage::helper('feed')->__('Product type to export'),
            'index' => 'product_type',
            'options' => Mage::getModel('export/system_config_source_types')->toSelectArray(),
            'renderer'  => 'feed/adminhtml_feed_renderer_select',
            'filter' => false,
            'sortable' => false,
            'multiple' => true,
        ));
        $this->addColumn('product_status', array(
            'header' => Mage::helper('feed')->__('Status of product to export'),
            'index' => 'product_status',
            'width' => '50px',
            'options' => Mage::getModel('export/system_config_source_status')->toSelectArray(),
            'filter' => false,
            'sortable' => false,
            'renderer'  => 'feed/adminhtml_feed_renderer_select',
        ));
        /*
        $this->addColumn('product_child', array(
            'header' => Mage::helper('feed')->__('Export child product'),
            'index' => 'product_child',
            'width' => '50px',
            'options' => array(
                1 => __('Yes'),
                0 => __('No'),
            ),
            'filter' => false,
            'sortable' => false,
            'renderer'  => 'feed/adminhtml_feed_renderer_select',
        ));*/
        $this->addColumn('format', array(
            'header' => Mage::helper('feed')->__('Format export'),
            'index' => 'format',
            'width' => '50px',
            'options' => Mage::getModel('export/system_config_source_format')->toSelectArray(),
            'filter' => false,
            'sortable' => false,
            'renderer'  => 'feed/adminhtml_feed_renderer_select',
        ));
        $this->addColumn('action', array(
            'header' => Mage::helper('feed')->__('Action'),
            'width' => '120',
            'align' => 'left',
            'filter' => false,
            'sortable' => false,
            'renderer'  => 'feed/adminhtml_feed_renderer_migrate',
        ));
        return parent::_prepareColumns();
    }
    
    protected function _prepareMassaction() {

        return $this;
    }

    public function getGridUrl() {
        return $this->getUrl('*/*/grid', array('_current' => true));
    }

    public function getRowUrl($row) {
        return '';
    }    

    private function _getGroups($data) {
        $_groups = trim(str_replace(array("\r\n", ';', '-', '|', ' '), ';', $data), ',');
        return explode(',', $_groups);
    }
}

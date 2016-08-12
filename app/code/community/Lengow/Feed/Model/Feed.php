<?php

/**
 * Lengow Feed model
 *
 * @category    Lengow
 * @package     Lengow_Feed
 * @author      Romain Le Polh <romain@lengow.com>
 * @copyright   2013 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Lengow_Feed_Model_Feed extends Varien_Object {

    protected $_id;

    /**
     *
     * @var boolean Export only selected product
     */
    protected $_selected_products;

    /**
     *
     * @var boolean Export product out of stock
     */
    protected $_product_out_stock;

    /**
     *
     * @var string Product type to export
     */
    protected $_product_type;

    /**
     *
     * @var string Status of product to export
     */
    protected $_product_status;

    /**
     *
     * @var boolean Export product child
     */
    protected $_product_child;

    /**
     *
     * @var string Export Format
     */
    protected $_format;

    /**
     *
     * @var string Url feed
     */
    protected $_url;

    /**
     *
     * @var integer ID store
     */
    protected $_id_store;

    protected $_idClient;
    protected $_idGroup;
    protected $_api_key;

    /**
     * Buid feed object
     *
     * @param integer $id
     * @param boolean $selected_products
     * @param boolean $product_out_stock
     * @param string $product_type
     * @param string $product_status
     * @param boolean $product_child
     * @param string $format
     */
    public function __construct($args) {
        $this->_id = $args['feed_id'];
        $this->_selected_products = $args['selected_products'];
        $this->_product_out_stock = $args['product_out_stock'];
        $this->_product_type = Mage::helper('core')->jsonEncode($args['product_type']);
        $this->_product_status = $args['product_status'];
        //$this->_product_child = $args['product_child'];
        $this->_format = $args['format'];

        $this->_config_model = Mage::getSingleton('lenfeed/config');

        $this->_idClient = $this->_config_model->get('lentracker/general/login');
        $this->_idGroup = $args['group_id'];
        $this->_id_store = $args['store_id'];
        //$this->_config_model->get('tracker/general/group');
        $this->_api_key = $this->_config_model->get('lentracker/general/api_key');
        parent::__construct();
    }

    /**
     * Build url before update
     */
    private function _buildUrl() {
        $params = 'selected_products/' . $this->_selected_products;
        $params .= '/product_out_stock/' . $this->_product_out_stock;
        $params .= '/product_type/' . join(',', Mage::helper('core')->jsonDecode($this->_product_type));
        $params .= '/product_status/' . $this->_product_status;
        //$params .= '/product_child/' . $this->_product_child;
        $params .= '/format/' . $this->_format;
        if($this->_id_store != 0)
            $params .= '/store/' . $this->_id_store;

        $new_flow = Mage::getUrl('lengow/feed/index') . $params;
        $this->_url = $new_flow;
    }

    /**
     * Update feed on Lengow
     */
    public function update() {
        $this->_buildUrl();
        $connector = Mage::helper('lenfeed/data')->getConnector($this->_idClient, $this->_api_key);
        $args = array(
            'idClient' => $this->_idClient,
            'idGroup' => $this->_idGroup,
            'urlFlux' => $this->_url,
            'idFlux' => $this->_id
        );
        Mage::helper('lensync/data')->log('Test udate group ' . $this->_idGroup . ' : ' . $this->_url);
        return true;
        return $connector->api('updateRootFeed', $args);
    }

    private function _cleanGroup($data) {
        return trim(str_replace(array("\r\n", ';', '-', '|', ' '), ';', $data), ',');
    }

}
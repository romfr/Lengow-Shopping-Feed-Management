<?php

/**
 * Lengow_Tracker Tracking Block Capsule
 *
 * @category   Lengow
 * @package    Lengow_Tracker
 * @author     Romain Le Polh <romain@lengow.com>
 */
class Lengow_Tracker_Block_Tag_Capsule extends Mage_Core_Block_Template {

    protected $_data = array();

    const LENGOW_TRACK_HOMEPAGE = 'homepage';
    const LENGOW_TRACK_PAGE = 'page';
    const LENGOW_TRACK_PAGE_LIST = 'listepage';
    const LENGOW_TRACK_PAGE_PAYMENT = 'payment';
    const LENGOW_TRACK_PAGE_CART = 'basket';
    const LENGOW_TRACK_PAGE_LEAD = 'lead';
    const LENGOW_TRACK_PAGE_CONFIRMATION = 'confirmation';

    static private $_CURRENT_PAGE_TYPE = 'page';
    static private $_USE_SSL = false;
    static private $_ID_ORDER = '';
    static private $_ORDER_TOTAL = '';
    static private $_IDS_PRODUCTS = '';
    static private $_IDS_PRODUCTS_CART = '';
    static private $_ID_CATEGORY = '';

    public function __construct() {
        $this->setData('config_model', Mage::getSingleton('lentracker/config'));
        $this->setData('capsule_model', Mage::getSingleton('lentracker/capsule'));
        $this->setData('id_client', $this->getData('config_model')->get('general/login'));

        // explode group id => force 1 group only
        $id_groups = $this->getData('config_model')->get('general/group');
        $id_groups = explode(',', $id_groups);
        $this->setData('id_group', $id_groups[0]);

        $this->setData('current_view', Mage::app()->getRequest()->getActionName());
    }

    protected function _prepareLayout() {
        parent::_prepareLayout();

        // Page type
        $current_module = Mage::app()->getFrontController()->getRequest()->getModuleName();
        $current_controller = Mage::app()->getFrontController()->getRequest()->getControllerName();
        $current_action = '';

        if(Mage::getBlockSingleton('page/html_header')->getIsHomePage()) {
            self::$_CURRENT_PAGE_TYPE = self::LENGOW_TRACK_HOMEPAGE;
        } else if ($current_module == 'catalog') {
            if ($current_controller == 'category')
                self::$_CURRENT_PAGE_TYPE = self::LENGOW_TRACK_PAGE_LIST;
            else if ($current_controller == 'product')
                self::$_CURRENT_PAGE_TYPE = self::LENGOW_TRACK_PAGE;
        } else if ($current_module == 'checkout') {

            $current_action = Mage::app()->getRequest()->getActionName();

            if ($current_action == 'success')
                self::$_CURRENT_PAGE_TYPE = self::LENGOW_TRACK_PAGE_CONFIRMATION;
            else if ($current_controller == 'cart')
                self::$_CURRENT_PAGE_TYPE = self::LENGOW_TRACK_PAGE_CART;
            else if ($current_controller == 'onepage')
                self::$_CURRENT_PAGE_TYPE = self::LENGOW_TRACK_PAGE;
       }

        // Order total
        if (self::$_CURRENT_PAGE_TYPE == self::LENGOW_TRACK_PAGE_CART ||
                self::$_CURRENT_PAGE_TYPE == self::LENGOW_TRACK_PAGE_PAYMENT ||
                $current_module == 'checkout' && $current_action != 'success') {
            $quote = Mage::getModel('checkout/cart')->getQuote();
            self::$_ORDER_TOTAL = round($quote->getGrandTotal(), 2);
        } else if (self::$_CURRENT_PAGE_TYPE == self::LENGOW_TRACK_PAGE_CONFIRMATION) {
            $order_id = Mage::getSingleton('checkout/session')->getLastOrderId();
            $order = Mage::getModel('sales/order')->load($order_id);
            self::$_ORDER_TOTAL = round($order->getGrandTotal(), 2);
        }

        // Order id - Lead / Payment / Confirmation
        if (self::$_CURRENT_PAGE_TYPE == self::LENGOW_TRACK_PAGE_CONFIRMATION) {
            self::$_ID_ORDER = Mage::getSingleton('checkout/session')->getLastRealOrderId();
        }

        // Ids Products - Page / Listpage / Basket / Payment / Confirmation
        if (self::$_CURRENT_PAGE_TYPE == self::LENGOW_TRACK_PAGE_CART ||
                self::$_CURRENT_PAGE_TYPE == self::LENGOW_TRACK_PAGE_PAYMENT ||
                $current_module == 'checkout'  && $current_action != 'success') {
            // Get current quote
            $quote = Mage::getModel('checkout/cart')->getQuote();
            self::$_IDS_PRODUCTS = $this->getData('capsule_model')->getIdsProducts($quote);
        } else if(self::$_CURRENT_PAGE_TYPE == self::LENGOW_TRACK_PAGE_LIST) {
            self::$_IDS_PRODUCTS =  $this->_getCurrentProductsIds();
        } else if(self::$_CURRENT_PAGE_TYPE == self::LENGOW_TRACK_PAGE) {
            self::$_IDS_PRODUCTS =  $this->_getCurrentProductId();
        } else if (self::$_CURRENT_PAGE_TYPE == self::LENGOW_TRACK_PAGE_CONFIRMATION) {
            // Get last order
            $order_id = Mage::getSingleton('checkout/session')->getLastOrderId();
            $order = Mage::getModel('sales/order')->load($order_id);
            self::$_IDS_PRODUCTS = $this->getData('capsule_model')->getIdsProducts($order);
        }

        // List product in basket - Basket - Confirmation
        if (self::$_CURRENT_PAGE_TYPE == self::LENGOW_TRACK_PAGE_CART ||
                $current_module == 'checkout' && $current_action != 'success') {
            $quote = Mage::getModel('checkout/cart')->getQuote();
            self::$_IDS_PRODUCTS_CART = $this->getData('capsule_model')->getProductsCart($quote);
        } else if (self::$_CURRENT_PAGE_TYPE == self::LENGOW_TRACK_PAGE_CONFIRMATION) {
            $order_id = Mage::getSingleton('checkout/session')->getLastOrderId();
            $order = Mage::getModel('sales/order')->load($order_id);
            self::$_IDS_PRODUCTS_CART = $this->getData('capsule_model')->getProductsCart($order);
        }

        // Id categorie
        if (self::$_CURRENT_PAGE_TYPE == self::LENGOW_TRACK_PAGE_LIST)
            self::$_ID_CATEGORY = Mage::registry('current_category')->getName();

        // Use SSL
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on')
            self::$_USE_SSL = true;

        // Assign data
        $this->setData('type_page', self::$_CURRENT_PAGE_TYPE);
        $this->setData('id_order', self::$_ID_ORDER);
        $this->setData('order_total', self::$_ORDER_TOTAL);
        $this->setData('id_category', self::$_ID_CATEGORY);
        $this->setData('ids_products', self::$_IDS_PRODUCTS);
        $this->setData('list_products', self::$_IDS_PRODUCTS_CART);
        $this->setData('use_ssl', self::$_USE_SSL == true ? 'true' : 'false');
        $this->setData('controller', $current_controller);
        $this->setData('module', $current_module);
        $this->setData('current_action', $current_action);

        $this->setTemplate('lengow/tracker/tagcapsule.phtml');
        return $this;
    }

    protected function _getCurrentProductId() {
        $config = Mage::getModel('lentracker/config');
        if($product = Mage::registry('product'))
            return $product->getData($config->get('tag/identifiant'));
        return '';
    }

    protected function _getCurrentProductsIds($implode = true) {
        $config = Mage::getModel('lentracker/config');
        $ids = array();
        $products = $this->_getProductCollection()->getData();
        if($products) {
            foreach ($products as $product) {
                $ids[] = $product[$config->get('tag/identifiant')];
            }
        }
        return $implode ? implode('|', $ids) : $ids;
    }

    /**
     * Retrieve loaded category collection
     *
     * @return Mage_Eav_Model_Entity_Collection_Abstract
     */
    protected function _getProductCollection() {
        if (is_null($this->_productCollection)) {
            $layer = $this->getLayer();
            /* @var $layer Mage_Catalog_Model_Layer */
            if ($this->getShowRootCategory()) {
                $this->setCategoryId(Mage::app()->getStore()->getRootCategoryId());
            }

            // if this is a product view page
            if (Mage::registry('product')) {
                // get collection of categories this product is associated with
                $categories = Mage::registry('product')->getCategoryCollection()
                    ->setPage(1, 1)
                    ->load();
                // if the product is associated with any category
                if ($categories->count()) {
                    // show products from this category
                    $this->setCategoryId(current($categories->getIterator()));
                }
            }

            $origCategory = null;
            if ($this->getCategoryId()) {
                $category = Mage::getModel('catalog/category')->load($this->getCategoryId());
                if ($category->getId()) {
                    $origCategory = $layer->getCurrentCategory();
                    $layer->setCurrentCategory($category);
                    $this->addModelTags($category);
                }
            }
            $this->_productCollection = $layer->getProductCollection();

            $this->prepareSortableFieldsByCategory($layer->getCurrentCategory());

            if ($origCategory) {
                $layer->setCurrentCategory($origCategory);
            }
        }
        return $this->_productCollection;
    }

    protected function _beforeToHtml() {
        if(self::$_CURRENT_PAGE_TYPE == self::LENGOW_TRACK_PAGE_LIST) {
            $config = Mage::getModel('lentracker/config');
            $_list = $this->getLayout()->getBlock('product_list');
            $_products = $_list->getLoadedProductCollection();
            if($_products) {
                foreach ($_products as $product) {
                    $ids[] = $product[$config->get('tag/identifiant')];
                }
            }
            $this->setData('ids_products', implode('|', $ids));
        }
        parent::_beforeToHtml();
    }

    /**
     * Get catalog layer model
     *
     * @return Mage_Catalog_Model_Layer
     */
    public function getLayer() {
        $layer = Mage::registry('current_layer');
        if ($layer) {
            return $layer;
        }
        return Mage::getSingleton('catalog/layer');
    }

    /**
     * Prepare Sort By fields from Category Data
     *
     * @param Mage_Catalog_Model_Category $category
     * @return Mage_Catalog_Block_Product_List
     */
    public function prepareSortableFieldsByCategory($category) {
        if (!$this->getAvailableOrders()) {
            $this->setAvailableOrders($category->getAvailableSortByOptions());
        }
        $availableOrders = $this->getAvailableOrders();
        if (!$this->getSortBy()) {
            if ($categorySortBy = $category->getDefaultSortBy()) {
                if (!$availableOrders) {
                    $availableOrders = $this->_getConfig()->getAttributeUsedForSortByArray();
                }
                if (isset($availableOrders[$categorySortBy])) {
                    $this->setSortBy($categorySortBy);
                }
            }
        }

        return $this;
    }
}
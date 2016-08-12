<?php
/**
 * Lengow adminhtml export controller
 *
 * @category    Lengow
 * @package     Lengow_Export
 * @author      Ludovic Drin <ludovic@lengow.com> & Benjamin Le NevÃ© <benjamin.le-neve@lengow.com>
 * @copyright   2015 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Lengow_Export_Model_Generateoptimize extends Varien_Object {

    protected $_id_store;
    protected $_websiteId;

    //Export files options
    protected $_file;
    protected $_fileName = 'lengow_feed';
    protected $_stream;
    protected $_fileFormat;
    protected $_fileTimeStamp = null;

    protected $_config_model;
    protected $_product_model;
    protected $_eav_model;
    protected $_helper;

    protected $_clear_parent_cache = 0;

    protected $categoryCache = array();

    var $storeParents = array();

    var $grouped = array();

    //debug mode
    protected $_debug = false;
    //store data
    protected $_listImages = array();
    protected $_listCategories = array();
    protected $_listGroupedProducts = array();
    protected $_listCodeAttributes = array();
    protected $_listTaxes = array();
    protected $_listParentIds = array();
    protected $_listChildrenIds = array();
    protected $_listAttributeValues = array();
    protected $_listAttributeCode = array();
    protected $_listConfigurableVariation = array();
    protected $_listOptionValues = array();
    //start time of script
    protected $_startScript = array();
    protected $_productLimit;

    //store magento table name
    protected $_table = array();
    //option in magento configration
    protected $_config = array();

    protected $_excludes = array('media_gallery',
        'tier_price',
        'short_description',
        'description',
        'quantity');
    /**
     * Default fields.
     */
    public static $DEFAULT_FIELDS = array('sku' => 'sku' ,
        'entity_id' => 'product-id' ,
        'parent-id' => 'parent-id' ,
        'qty' => 'qty' ,
        'name' => 'name' ,
        'description' => 'description' ,
        'short_description' => 'short_description' ,
        'price-ttc' => 'price-ttc' ,
        'shipping-name' => 'shipping-name' ,
        'image-url-1' => 'image-url-1' ,
        'product-url' => 'product-url');

    protected $_attributes = array(
        'sku',
        'product_id',
        'name',
        'short_description',
        'description',
        'image',
        'small_image',
        'thumbnail',
        'manufacturer',
        'ean',
        'url',
        'url_key',
        'special_price',
        'special_from_date',
        'special_to_date',
        'price_type',
        'price',
        'final_price',
        'tax_class_id',
        'is_in_stock',
        'qty',
        'entity_id',
        'created_at',
        'updated_at',
        'visibility',
    );

    protected $_listHeaderCsvFile = array(
        'sku',
        'product_id',
        'qty',
        'status',
        'lengow_categories_header',
        'price_ttc',
        'price_before_discount',
        'discount_amount',
        'discount_percent',
        'start_date_discount',
        'end_date_discount',
        'shipping_name',
        'shipping_price',
        'lengow_images_header',
        'product_url',
        'name',
        'description',
        'short_description',
        'parent_id',
        'product_type',
        'product_variation',
        'image_default',
        'child_name',
    );

    protected $_attributesAdditional = array();

    protected $_listAttributeToShow = array(
        'sku',
        'product_id',
        'qty',
        'status',
        'categories',
        'prices',
        'shipping_informations',
        'images',
        'product_url',
        'name',
        'description',
        'short_description',
        'parent_informations',
        'product_variation',
        'image_default',
    );

    protected $_listForbiddenAttributes = array(
        'group_price'
    );

    /**
     * Construct generator
     * Set models
     */
    public function __construct()
    {
        $this->_config_model = Mage::getSingleton('lenexport/config');
        $this->_product_model = Mage::getModel('lenexport/catalog_product');
        $this->_eav_model = Mage::getResourceModel('eav/entity_attribute_collection');

        $this->_helper = Mage::helper('lenexport/data');

        //get configuration data
        $this->_config['category_max_level'] = $this->_config_model->get('data/levelcategory');
        $this->_config['number_product_by_query'] = 2000;
        $this->_config["query_url_option"] = (version_compare(Mage::getVersion(), '1.6.0', '<')) ?  'options=\'\'' : 'ISNULL(options)';


        //Get Table Definition
        $this->_coreResource = Mage::getSingleton('core/resource');
        $this->_table['catalog_product_link'] = $this->_coreResource->getTableName('catalog_product_link');
        $this->_table['cataloginventory_stock_item'] = $this->_coreResource->getTableName('cataloginventory_stock_item');
        $this->_table['catalog_product_entity_media_gallery'] = $this->_coreResource->getTableName('catalog_product_entity_media_gallery');
        $this->_table['catalog_product_entity_media_gallery_value'] = $this->_coreResource->getTableName('catalog_product_entity_media_gallery_value');
        $this->_table['catalog_product_entity'] = $this->_coreResource->getTableName('catalog_product_entity');
        $this->_table['eav_attribute'] = $this->_coreResource->getTableName('eav_attribute');
        $this->_table['eav_entity_type'] = $this->_coreResource->getTableName('eav_entity_type');
        $this->_table['catalog_product_entity_int'] = $this->_coreResource->getTableName('catalog_product_entity_int');
        $this->_table['catalog_product_entity_varchar'] = $this->_coreResource->getTableName('catalog_product_entity_varchar');
        $this->_table['catalog_product_entity_datetime'] = $this->_coreResource->getTableName('catalog_product_entity_datetime');
        $this->_table['catalog_product_entity_decimal'] = $this->_coreResource->getTableName('catalog_product_entity_decimal');
        $this->_table['catalog_product_entity_text'] = $this->_coreResource->getTableName('catalog_product_entity_text');
        $this->_table['core_url_rewrite'] = $this->_coreResource->getTableName('core_url_rewrite');
        $this->_table['catalog_category_product'] = $this->_coreResource->getTableName('catalog_category_product');
        $this->_table['catalog_category_product_index'] = $this->_coreResource->getTableName('catalog_category_product_index');
        $this->_table['catalog_product_index_price'] = $this->_coreResource->getTableName('catalog_product_index_price');
        $this->_table['eav_attribute_option'] = $this->_coreResource->getTableName('eav_attribute_option');
        $this->_table['eav_attribute_option_value'] = $this->_coreResource->getTableName('eav_attribute_option_value');
        $this->_table['catalog_product_super_attribute'] = $this->_coreResource->getTableName('catalog_product_super_attribute');
        $this->_table['catalog_product_super_link'] = $this->_coreResource->getTableName('catalog_product_super_link');
        $this->_table['tax_class'] = $this->_coreResource->getTableName('tax_class');
        $this->_table['tax_calculation'] = $this->_coreResource->getTableName('tax_calculation');
        $this->_table['tax_calculation_rate'] = $this->_coreResource->getTableName('tax_calculation_rate');
        $this->_table['directory_country_region'] = $this->_coreResource->getTableName('directory_country_region');
        $this->_table['customer_group'] = $this->_coreResource->getTableName('customer_group');

        $connection = $this->_coreResource->getConnection('core_read');

        //get Catalog Product Entity Id
        $query = $connection->select()->from($this->_table['eav_entity_type'])->where('entity_type_code=\'catalog_product\'');
        $row = $connection->fetchAll($query);
        $this->_catalogProductEntityId = $row[0]['entity_type_id'];

        //get status attribute code
        $query = $connection->select()->from($this->_table['eav_attribute'])->where('attribute_code=\'status\'');
        $row = $connection->fetchAll($query);
        $this->_attributeStatusId = $row[0]['attribute_id'];
    }


    public function getTotalProductStore($storeId){

        $this->_id_store = $storeId;
        $this->_websiteId = Mage::getModel('core/store')->load($this->_id_store)->getWebsiteId();

        $productCollection = $this->_getQuery();

        $productCollection = clone $productCollection;
        $productCollection->getSelect()->columns('COUNT(DISTINCT e.entity_id) As total');
        return $productCollection->getFirstItem()->getTotal();
    }

    public function _getQuery(){


        $productCollection = Mage::getModel('lenexport/product_collection')->getCollection()->addStoreFilter($this->_id_store);

        // Filter status
        if ($this->_config['product_status'] !== null){
            $productCollection->addAttributeToFilter('status', array('eq' => $this->_config['product_status']));
        }

        //filter type
        if($this->_config['force_type']) {
            $_types = explode(',', $this->_config['force_type']);
        } else {
            $_types = $this->_config_model->get('global/producttype');
            $_types = explode(',', $_types);
        }
        $productCollection->addAttributeToFilter('type_id', array('in' => $_types));

        if ($this->_config['mode'] != 'size') {
            $productCollection->addAttributeToSelect($this->_attributes, true);
        }

        $this->_joinStock($productCollection);

        if ($this->_config['only_selected_product']){
            $productCollection->addAttributeToFilter('lengow_product', array('eq' => 1));
        }
        if ($this->_config['product_ids']){
            $productCollection->addAttributeToFilter('entity_id', array('in' => $this->_config['product_ids']));
        }

        $productCollection->getSelect()->joinLeft($this->_table['core_url_rewrite'] . ' AS url',
            'url.product_id=e.entity_id AND url.target_path NOT LIKE "category%" AND is_system=1 AND ' . $this->_config["query_url_option"] . ' AND url.store_id=' . $this->_id_store,
            array('request_path' => 'MAX(DISTINCT request_path)'));

        $productCollection->getSelect()->joinLeft($this->_table['catalog_category_product'] . ' AS categories', 'categories.product_id=e.entity_id');
        $productCollection->getSelect()->joinLeft($this->_table['catalog_category_product_index'] . ' AS categories_index',
            '((categories_index.category_id=categories.category_id AND categories_index.product_id=categories.product_id) ) AND categories_index.store_id=' . $this->_id_store,
            array('categories_ids' => 'GROUP_CONCAT(DISTINCT categories_index.category_id)'));
        if (version_compare(Mage::getVersion(), '1.4.0', '>=')) {
            $productCollection->getSelect()->joinLeft($this->_table['catalog_product_index_price'] . ' AS price_index',
                'price_index.entity_id=e.entity_id AND customer_group_id=0 AND price_index.website_id=' . $this->_websiteId,
                array(
                    'index_price' => 'price',
                    'index_min_price' => 'min_price',
                    'index_max_price' => 'max_price',
                    'index_tier_price' => 'tier_price',
                    'index_final_price' => 'final_price'
                ));
        }
        $productCollection->getSelect()->group('e.entity_type_id');
        return $productCollection;
    }

    /**
     * Make the feed
     *
     * @param integer $id_store           ID of store
     * @param varchar $mode               The mode of export
     *                                        size : display only count of products to export
     *                                        full : export simple product + configured product
     *                                        xxx,yyy : export xxx type product + yyy type product
     * @param varchar $format             Format of export
     * @param array $params             List of options
     *
     * @return Mage_Catalog_Model_Product
     */
    public function exec($id_store, $format, $params = array()) {

        $this->_debug = true;

        //store start time export
        $this->_startScript = $this->microtime_float();
        //set Store id / Website id
        $this->_id_store = $id_store;
        $this->_websiteId = Mage::getModel('core/store')->load($this->_id_store)->getWebsiteId();

        $store_code = Mage::app()->getStore($this->_id_store)->getCode();

        $this->_config['include_tax'] = Mage::getStoreConfig(Mage_Tax_Model_Config::CONFIG_XML_PATH_PRICE_INCLUDES_TAX, $this->_id_store);
        $this->_config['directory_path'] = Mage::getBaseDir('media') . DS . 'lengow' . DS . $store_code . DS;
        $this->_config['image_base_url'] = substr(Mage::app()->getStore($this->_id_store)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA, false), 0, -1).'/catalog/product/';
        $this->_config['force_type'] = array_key_exists('forced_type',$params) ? $params['forced_type'] : false;
        $this->_config['product_status'] = array_key_exists('status',$params) ? $params['status'] : null;
        $this->_config['product_ids'] = array_key_exists('product_ids',$params) ? $params['product_ids'] : false;
        $this->_debug = array_key_exists('debug',$params) ? $params["debug"] : false;

        $this->_config['offset'] = array_key_exists('offset',$params) ? $params['offset'] : false;
        $this->_productLimit = array_key_exists('limit',$params) ? $params['limit'] : false;
        if ($this->_config['product_status'] === null){
            $this->_config['product_status'] = (string) $this->_config_model->get('global/productstatus');
            if($this->_config['product_status'] === Mage_Catalog_Model_Product_Status::STATUS_ENABLED
                OR $this->_config['product_status'] === Mage_Catalog_Model_Product_Status::STATUS_DISABLED){
                $this->_config['product_status'] =  $this->_config['product_status'];
            }else{
                $this->_config['product_status'] =  null;
            }
        }

        $attributes = Mage::getStoreConfig('lenexport/data/html_attributes', $this->_id_store);
        $this->_config['attribute_html'] = !empty($attributes) ? explode(',', $attributes) : array();
        $this->_config['shipping_price'] = $this->_config_model->get('data/default_shipping_price', $this->_id_store);

        $outOfStock = array_key_exists('out_of_stock',$params) ? $params['out_of_stock'] : null;
        if ($outOfStock === null){
            $this->_config['out_of_stock'] = (int)$this->_config_model->get('global/export_soldout');
        }

        $selectedProduct = array_key_exists('forced_type',$params) ? $params['selected_products'] : null;
        if ($selectedProduct === null){
            $this->_config['only_selected_product'] = (int)$this->_config_model->onlySelectedProducts();
        }
        if ($format === null){
            $this->_fileFormat = $this->_config_model->get('data/format');
        }else{
            $this->_fileFormat = $format;
        }
        $this->_config['mode']  = array_key_exists('mode',$params) ? $params['mode'] : false;


        $stream = array_key_exists('stream',$params) ? $params['stream'] : null;
        if ($stream === null){
            $this->_stream = $this->_config_model->get('performances/usesavefile') ? false : true;
        }

        if ($this->_isAlreadyLaunch()){
            Mage::helper('lensync/data')->log('Feed already launch');

            if(!$this->_stream) {
                $this->_log('/!\ Feed already Launch');
            }
            exit();
        }

        if(!$this->_stream) {
            header('Content-Type: text/html; charset=utf-8');
            $this->_log('Start Store = ' . Mage::app()->getStore($this->_id_store)->getName() . '(' . $this->_id_store . ')');
        }

        if ($this->_config['mode'] == 'size'){

            $this->_log('Total Products :'.$this->getTotalProductStore($this->_id_store));
            $this->_log('Memory Usage '.(memory_get_usage()/1000000));
            $this->_stop($this->_startScript, 'Execution time ');
            exit();
        }

        // Get products list to export
        $this->_getProductsCollection($format, $params);

        if(!$this->_stream) {
            $this->_log('Memory Usage '.(memory_get_usage()/1000000));
            $this->_stop($this->_startScript, 'Execution time ');
        }

    }

    /**
     * Get Product Collection
     *
     * @param varchar $mode               The mode of export
     *                                        size : display only count of products to export
     *                                        full : export simple product + configured product
     *                                        xxx,yyy : export xxx type product + yyy type product
     * @param varchar $format             Format of export
     * @param array $params               Parameters
     *
     * @return float price
     */


    protected function _getProductsCollection($format, $params = array())
    {

        //$out_of_stock = array_key_exists('out_of_stock',$params) ? $params['out_of_stock'] : false;


        $this->_loadTaxes();
        $this->_loadSelectedAttributes();
        $this->_loadProductAttributes();
        $this->_loadProductAttributeValues();
        $this->_loadConfigurableProducts();
        $this->_loadImages();
        $this->_loadCategories();
        $this->_loadGroupedProducts();
        $this->_buildCsvHeader();


        $productCollection = $this->_getQuery();

        $tempProductCollection = clone $productCollection;
        $tempProductCollection->getSelect()->columns('COUNT(DISTINCT e.entity_id) As total');
        $nbProduct = $tempProductCollection->getFirstItem()->getTotal();
        if ($this->_productLimit && $nbProduct > $this->_productLimit){
            $nbProduct = $this->_productLimit;
        }
        $totalQueryToExecute = ceil($nbProduct / $this->_config['number_product_by_query']);

        $productCollection->getSelect()->group(array('e.entity_id'))->order('e.entity_id');
        $nbQueryExecuted = 0;

        if ($this->_debug){
            $this->_log('Total items calculated ('.$nbProduct.' in '.$totalQueryToExecute.' queries )');
        }
        Mage::helper('lensync/data')->log('Find ' . $nbProduct . ' product' . ($nbProduct > 1 ? 's ' : ' '));

        $formatData = $this->_config_model->get('data/formatdata') == 1 ? true : false;


        $feed = Mage::getModel('Lengow_Export_Model_Feed_' . ucfirst($this->_fileFormat));

        // Get content type if streamed feed
        if($this->_stream)
            header('Content-Type: ' . $feed->getContentType() . '; charset=utf-8');
        $feed->setFields($this->_listHeaderCsvFile);
        $this->_write($feed->makeHeader());

        $pi = 0;

        while ($nbQueryExecuted < $totalQueryToExecute) {
            $currentProductCollection = clone $productCollection;


            if ($this->_config['offset']){
                $offset = (int)$this->_config['offset'];
            }else{
                $offset = ($this->_config['number_product_by_query'] * $nbQueryExecuted);
            }

            if ($this->_config['number_product_by_query']){
                if ($this->_productLimit && $this->_config['number_product_by_query'] > $this->_productLimit){
                    $currentProductCollection->getSelect()->limit($this->_productLimit, $offset);
                }else{
                    $currentProductCollection->getSelect()->limit($this->_config['number_product_by_query'], $offset);
                }
            }


            ++$nbQueryExecuted;

            if ($this->_config['number_product_by_query'] * $nbQueryExecuted > $nbProduct) {
                $totalOffset = $nbProduct;
            } else {
                $totalOffset = $this->_config['number_product_by_query'] * $nbQueryExecuted;
            }
            //if ($this->_debug){
                $this->_log('Fetching products from ' . ($this->_config['number_product_by_query'] * ($nbQueryExecuted - 1) + 1) . ' to ' . $totalOffset);
                //echo $currentProductCollection->getSelect();
            //}

            foreach ($currentProductCollection as $product) {
                ++$pi;
                $data = array();
                $data['type'] = $product->getTypeId();
                foreach($this->_listAttributeToShow as $attributeToShow){
                    switch($attributeToShow){
                        case 'sku':
                            $data['sku'] = $product->getSku();
                            break;
                        case 'product_id':
                            $data['product_id'] = $product->getId();
                            break;
                        case 'qty':
                            //todo : what quantity for configurable / bundle product ???
                            switch($product->getTypeId()){
                                case 'grouped':
                                    $data['qty'] = (int)$this->_listGroupedProducts[$product->getId()]['qty'];
                                    break;
                                default:
                                    $data['qty'] = (int)$product->getQty();
                                    break;
                            }
                            break;
                        case 'status':
                            switch($product->getTypeId()){
                                case 'grouped':
                                    $data['status'] = $this->_listGroupedProducts[$product->getId()]['status'];
                                    break;
                                default:
                                    $data['status'] = $product->getStatus();
                                    break;
                            }
                            $data['status'] = ($data['status'] == Mage_Catalog_Model_Product_Status::STATUS_DISABLED) ? 'Disabled' : 'Enabled';
                            break;
                        case 'categories':
                            $categoryTemp = array();
                            if($product->getVisibility() == Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE && isset($this->_listChildrenIds[$product->getId()])){
                                $currentPathCategory = explode(',', $this->_listChildrenIds[$product->getId()]['categories_id']);
                            }else{
                                $currentPathCategory = explode(',', $product->getCategoriesIds());
                            }
                            $currentCategoryId =  end($currentPathCategory);;
                            $i = 0;
                            if (!$currentCategoryId){
                                $data['category'] = '';
                                $data['category-url'] = '';
                            }else{
                                $level = 0;
                                foreach($currentPathCategory as $category){
                                    if ($this->_listCategories[$category]['level'] > $level){
                                        $level = $this->_listCategories[$category]['level'];
                                        $currentCategoryId = $category;
                                    }
                                }
                                $fullPathCategory = explode('/', $this->_listCategories[$currentCategoryId]['path']);
                                foreach ($fullPathCategory as $categoryId){
                                    if($i == 0) { ++$i; continue; }
                                    $categoryTemp[] = $this->_listCategories[$categoryId]['name'];

                                    if($i == 1) {
                                        $data['category'] = $this->_listCategories[$categoryId]['name'];
                                        $data['category-url'] = $this->_listCategories[$categoryId]['url'];
                                    } elseif($i <= $this->_config['category_max_level']) {
                                        $data['category-sub-'.($i-1)] =  $this->_listCategories[$categoryId]['name'];
                                        $data['category-url-sub-'.($i-1)] = $this->_listCategories[$categoryId]['url'];
                                    }
                                    ++$i;
                                }
                            }
                            for ($j = $i; $j <= $this->_config['category_max_level']; ++$j) {
                                $data['category-sub-'.$j] =  '';
                                $data['category-url-sub-'.$j] = '';
                            }

                            $data['category_breadcrumb'] = join(' > ',$categoryTemp);
                            break;
                        case 'prices':
                            if ($product->getIndexPrice()>0){
                                $product["price"] = $product->getIndexPrice();
                            }
                            if ($product->getIndexFinalPrice()>0){
                                $product["final_price"] = $product->getIndexFinalPrice();
                            }
                            if ($product["final_price"]==0){
                                $product["final_price"] = $product["price"];
                            }
                            switch($product->getTypeId()){
                                case 'grouped':
                                    $data["price_ttc"] = $this->_calculatePrice($this->_listGroupedProducts[$product->getId()]['price'], $product->getTaxClassId());
                                    $data["price_before_discount"]  = $data["price_ttc"];
                                    break;
                                default:
                                    $data["price_ttc"] = $this->_calculatePrice($product["final_price"], $product->getTaxClassId());
                                    $data["price_before_discount"] = $this->_calculatePrice($product["price"], $product->getTaxClassId());
                                    break;
                            }
                            $discountAmount = ((float)$data["price_before_discount"]-(float)$data["price_ttc"]);
                            $data['discount_amount'] = $discountAmount > 0 ? round($discountAmount, 2) : '0';
                            $data['discount_percent'] = $discountAmount > 0 ? round(($discountAmount * 100) / (float)$data['price_before_discount'], 0) : '0';
                            $data['start_date_discount'] = $product->getSpecialFromDate();
                            $data['end_date_discount'] = $product->getSpecialToDate();
                            break;
                        case 'shipping_informations':
                            $data['shipping_name'] = '';
                            $data['shipping_price'] = '';
                            $data['shipping_delay'] = $this->_config_model->get('data/default_shipping_delay');
                            $carrier = $this->_config_model->get('data/default_shipping_method');
                            if ($carrier == 'flatrate_flatrate' || $carrier == ''){
                                $data['shipping_name'] = 'Flatrate';
                                $data['shipping_price'] = $this->_config['shipping_price'];
                            }else{
                                if(!empty($carrier)){
                                    $carrierTab = explode('_',$carrier);
                                    list($carrierCode,$methodCode) = $carrierTab;
                                    //todo : wrong shipping name ?
                                    $data['shipping_name'] = ucfirst($methodCode);
                                    $shippingPrice = 0;
                                    $countryCode = $this->_config_model->get('data/shipping_price_based_on');

                                    $shippingPrice = $product->_getShippingPrice($product, $carrier, $countryCode);
                                    if(!$shippingPrice) {
                                        $shippingPrice = $this->_config['shipping_price'];
                                    }
                                    $data['shipping_price'] = $shippingPrice;
                                }
                            }
                            break;
                        case 'images':
                            $max_image = $this->_config_model->getCountExportImages();
                            for($i = 1; $i <= $max_image; ++$i) {
                                $data['image-url-'.$i] = '';
                            }

                            if (isset($this->_listImages[$product->getId()])){
                                $productImage = $this->_listImages[$product->getId()];
                                $i = 1;
                                foreach($productImage as $image){
                                    if ($image['disabled']==0){
                                        $data['image-url-' . $i] =  $this->_config['image_base_url'].$image['src'];
                                        ++$i;
                                    }
                                }
                                for ($j = $i; $j < $max_image; ++$j) {
                                    $data['image-url-'.$j] =  '';
                                }
                            }

                            if ($data['image-url-1']=='' && isset($this->_listChildrenIds[$product->getId()])){
                                if (isset($this->_listImages[$this->_listChildrenIds[$product->getId()]['id']])){
                                    foreach($this->_listImages[$this->_listChildrenIds[$product->getId()]['id']] as $img){
                                        $data['image-url-1']  = $this->_config['image_base_url'].$img['src'];
                                    }
                                }
                            }
                            $data['image_default'] = $data['image-url-1'];
                            break;
                        case 'product_url':
                            if($product->getVisibility() == Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE && isset($this->_listChildrenIds[$product->getId()])) {
                                $data['product_url'] = $this->_listChildrenIds[$product->getId()]['url'];
                            }else{
                                if ($product->getProductUrl() == "" && isset($this->_listChildrenIds[$product->getId()]['url'])){
                                    $data['product_url'] = $this->_listChildrenIds[$product->getId()]['url'];
                                } else{
                                    $data['product_url'] = $product->getProductUrl();
                                }
                            }
                            break;
                        case 'name':
                            if($product->getVisibility() == Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE && isset($this->_listChildrenIds[$product->getId()])) {
                                if ($this->_listChildrenIds[$product->getId()] !=''){
                                    $data['name'] = $this->_helper->cleanData($this->_listChildrenIds[$product->getId()]['name'], $formatData, in_array('name', $this->_config['attribute_html']));
                                }else{
                                    $data['name'] = $this->_helper->cleanData($product->getName(), $formatData, in_array('name', $this->_config['attribute_html']));
                                }
                            }else{
                                $data['name'] = $this->_helper->cleanData($product->getName(), $formatData, in_array('name', $this->_config['attribute_html']));
                            }
                            break;
                        case 'description':
                            $data['description'] = $this->_helper->cleanData($product->getDescription(), $formatData, in_array('description', $this->_config['attribute_html']));
                            break;
                        case 'short_description':
                            $data['short_description'] = $this->_helper->cleanData($product->getShortDescription(), $formatData, in_array('short_description', $this->_config['attribute_html']));
                            break;
                        case 'parent_informations':
                            if (isset($this->_listChildrenIds[$product->getId()])){
                                $data['parent_id'] = $this->_listChildrenIds[$product->getId()]['id'];
                                $data['product_type'] = 'child';
                                $data['child_name'] = $this->_listChildrenIds[$product->getId()]['name'];
                                if (isset($this->_listConfigurableVariation[$this->_listChildrenIds[$product->getId()]['id']])){
                                    $variation = array();
                                    foreach($this->_listConfigurableVariation[$this->_listChildrenIds[$product->getId()]['id']] as $variationAttributeId){
                                        $variation[] = $this->_listCodeAttributes[$variationAttributeId]['frontend_label'];
                                    }
                                    $data['product_variation'] = join(',', $variation);
                                }else{
                                    $data['product_variation'] = '';
                                }
                            }else{
                                if (isset($this->_listParentIds[$product->getId()])){
                                    $data['parent_id'] = '';
                                    $data['product_type'] = 'parent';
                                    $data['child_name'] = $product->getName();
                                    if (isset($this->_listConfigurableVariation[$product->getId()])){
                                        $variation = array();
                                        foreach($this->_listConfigurableVariation[$product->getId()] as $variationAttributeId){
                                            $variation[] = $this->_listCodeAttributes[$variationAttributeId]['frontend_label'];
                                        }
                                        $data['product_variation'] = join(',', $variation);
                                    }else{
                                        $data['product_variation'] = '';
                                    }
                                }else{
                                    $data['parent_id'] = '';
                                    $data['product_type'] = 'simple';
                                    $data['child_name'] = $product->getName();
                                    $data['product_variation'] = '';
                                }
                            }
                            break;
                    }
                }
                foreach($this->_attributesAdditional as $attributeCode){
                    if (!isset($data[$attributeCode])) {
                        if (!in_array($attributeCode, $this->_listForbiddenAttributes)) {
                            if (in_array($this->_listCodeAttributes[$this->_listAttributeCode[$attributeCode]]['backend_type'], array('text','varchar'))){
                                $data[$attributeCode] = $this->_helper->cleanData($this->_getAttributeValue($product->getId(), $attributeCode), $formatData, in_array($attributeCode, $this->_config['attribute_html']));
                            }else{
                                $data[$attributeCode] = $this->_getAttributeValue($product->getId(), $attributeCode);
                            }
                        } else {
                            $data[$attributeCode] = '';
                        }
                    }
                }
                //print_r($data);

                if ($pi >= $nbProduct)
                    $this->_write($feed->makeData($data, array('last' => true)));
                else
                    $this->_write($feed->makeData($data));
                
                unset($data);
            }
        }

        $this->_write($feed->makeFooter());
        if(!$this->_stream) {
            flush();
            $this->_copyFile();
            $store_code = Mage::app()->getStore($this->_id_store)->getCode();
            $url_file = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'lengow' . DS . $store_code . DS . $this->_fileName . '.' . $this->_fileFormat;
            $this->_log($this->_helper->__('Your feed is available here : %s' , '<a href=\'' . $url_file . '\'>' . $url_file . '</a>'));
            Mage::helper('lensync/data')->log('Export of the store ' . Mage::app()->getStore($this->_id_store)->getName() . '(' . $this->_id_store . ') generated a file here : ' . $url_file);
        }

    }


    protected function _buildCsvHeader(){

        $tmpHeader = $this->_listHeaderCsvFile;
        $this->_listHeaderCsvFile = array();
        foreach($tmpHeader as $header){
            switch($header){
                case 'lengow_categories_header':
                    $this->_listHeaderCsvFile[] = 'category';
                    $this->_listHeaderCsvFile[] = 'category-url';
                    for ($j = 1; $j <= $this->_config['category_max_level']; ++$j) {
                        $this->_listHeaderCsvFile[] = 'category-sub-'.$j;
                        $this->_listHeaderCsvFile[] = 'category-url-sub-'.$j;
                    }
                    $this->_listHeaderCsvFile[] = 'category_breadcrumb';
                    break;
                case 'lengow_images_header':
                    $max_image = $this->_config_model->getCountExportImages();
                    for($i = 1; $i <= $max_image; ++$i) {
                        $this->_listHeaderCsvFile[]= 'image-url-'.$i;
                    }
                    break;
                default:
                    $this->_listHeaderCsvFile[] = $header;
                    break;
            }
        }
        foreach($this->_attributesAdditional as $header){
            $this->_listHeaderCsvFile[] = $header;
        }

    }


    /**
     * Set stock on query
     * @param object $productCollection
     *
     * @return object $productCollection
     */

    protected function _joinStock($productCollection){
        if (!$this->_config['out_of_stock']) {
            $conditions = ' AND ((stock.is_in_stock = 1) '
                . ' OR (IF(stock.use_config_manage_stock = 1,
            ' . (int)Mage::getStoreConfigFlag(Mage_CatalogInventory_Model_Stock_Item::XML_PATH_MANAGE_STOCK) . ',
            stock.manage_stock) = 0))  AND is_in_stock IS NOT NULL';

            $productCollection->getSelect()->join($this->_table['cataloginventory_stock_item'] . ' AS stock', 'stock.product_id=e.entity_id ' . $conditions, array(
                'qty' => 'qty',
                'is_in_stock' => 'is_in_stock',
                'manage_stock' => 'manage_stock',
                'use_config_manage_stock' => 'use_config_manage_stock',
                'backorders' => 'backorders',
                'use_config_backorders' => 'use_config_backorders'
            ));
        }else{
            $productCollection->getSelect()->joinLeft($this->_table['cataloginventory_stock_item'] . ' AS stock', 'stock.product_id=e.entity_id ', array(
                'qty' => 'qty',
                'is_in_stock' => 'is_in_stock',
                'manage_stock' => 'manage_stock',
                'use_config_manage_stock' => 'use_config_manage_stock',
                'backorders' => 'backorders',
                'use_config_backorders' => 'use_config_backorders'
            ));
        }
        return $productCollection;
    }

    /**
     * Load image of all entities
     *
     * @return void
     */

    protected function _loadImages(){

        $connection = $this->_coreResource->getConnection('core_read');

        $query = $connection->select(array('DISTINCT value'));
        $query->from($this->_table['catalog_product_entity_media_gallery']);
        $query->joinleft(array('cpemgv' => $this->_table['catalog_product_entity_media_gallery_value']),
            'cpemgv.value_id = ' . $this->_table['catalog_product_entity_media_gallery'] . '.value_id',
            array('cpemgv.position', 'cpemgv.disabled'));
        $query->where('value<>TRIM(\'\') AND (store_id=' . $this->_id_store . ' OR store_id=0)');
        $query->order(array('position', 'value_id'));
        $query->group(array('value_id'));
        $rows = $connection->fetchAll($query);
        $this->_listImages = array();
        foreach ($rows as $row) {
            if ($row['disabled'] != 1 && $row['value'] != '') {
                $this->_listImages[$row['entity_id']][] = array('src' => $row['value'], 'disabled' => $row['disabled']) ;
            }
        }

        if ($this->_debug){
            $this->_log('Load Images ('.count($this->_listImages).')');
            //print_r($this->_listImages);
        }
    }

    /**
     * Load all categories
     *
     * @return void
     */

    protected function _loadCategories(){
        $categories = Mage::getModel('catalog/category')->getCollection()
            ->setStoreId($this->_id_store)
            ->addAttributeToSelect('name','store_id')
            ->addAttributeToSelect('is_active')
            ->addAttributeToSelect('include_in_menu');
        $this->_listCategories = array();
        foreach ($categories as $category) {
            $this->_listCategories[$category->getId()]['name'] = $category->getName();
            $this->_listCategories[$category->getId()]['path'] = $category->getPath();
            $this->_listCategories[$category->getId()]['level'] = $category->getLevel();
            $this->_listCategories[$category->getId()]['url'] = $category->getUrl();
        }

        if ($this->_debug){
            $this->_log('Load Categories ('.count($this->_listCategories).')');
            //print_r($this->_listCategories);
        }
    }

    /**
     * Load important information of grouped products :
     *  - status
     *  - quantity
     *
     * @return void
     */

    protected function _loadGroupedProducts(){

        $productCollection = Mage::getModel('lenexport/product_collection')->getCollection()->addStoreFilter($this->_id_store);
        $productCollection->addAttributeToFilter('type_id', array('in' => 'grouped'));
        $productCollection->addAttributeToSelect('name','product_url');
        if ($this->_config['product_status'] !== null){
            $productCollection->addAttributeToFilter('status', array('eq' => $this->_config['product_status']));
        }
        $productCollection->getSelect()->joinLeft($this->_table['catalog_product_link'] . ' AS cpl',
            'cpl.product_id=e.entity_id AND cpl.link_type_id=3',
            array('child_ids' => 'GROUP_CONCAT( cpl.linked_product_id)'));
        $productCollection->getSelect()->joinLeft($this->_table['cataloginventory_stock_item'] . ' AS stock',
            'stock.product_id=cpl.linked_product_id',
            array('child_qtys' => 'GROUP_CONCAT( qty)'));
        $productCollection->getSelect()->joinLeft($this->_table['catalog_product_entity_int'] . ' AS entity_int',
            'entity_int.entity_id=cpl.linked_product_id AND entity_int.attribute_id ='.$this->_attributeStatusId ,
            array('child_status' => 'GROUP_CONCAT( entity_int.value)'));
        $productCollection->getSelect()->joinLeft($this->_table['catalog_product_index_price'] . ' AS price_index',
            'price_index.entity_id=cpl.linked_product_id AND customer_group_id=0 AND price_index.website_id=' . $this->_websiteId,
            array('child_prices' => 'GROUP_CONCAT( final_price )'));
        $productCollection->getSelect()->joinLeft($this->_table['catalog_category_product'] . ' AS categories', 'categories.product_id=e.entity_id');
        $productCollection->getSelect()->joinLeft($this->_table['catalog_category_product_index'] . ' AS categories_index',
            '((categories_index.category_id=categories.category_id AND categories_index.product_id=categories.product_id) ) AND categories_index.store_id=' . $this->_id_store,
            array('categories_ids' => 'GROUP_CONCAT(DISTINCT categories_index.category_id)'));
        $productCollection->getSelect()->group(array('cpl.product_id'));


        $this->_listGroupedProducts = array();
        foreach ($productCollection as $product) {
            $quantities = array();
            $status = true;
            $totalPrice = 0;
            $this->_listParentIds[$product->getId()] = true;
            foreach (explode(',', $product->getChildIds()) as $id) {
                $this->_listChildrenIds[$id] = array(
                    'name' => $product->getName(),
                    'id' => $product->getId(),
                    'categories_id' => $product->getCategoriesIds(),
                    'url' => '',//$product->getProductUrl()
                );
            }
            foreach (explode(',', $product->getChildQtys()) as $qty) {
                $quantities[] = $qty >= 0 ? $qty : 0;
            }
            foreach (explode(',', $product->getChildStatus()) as $status) {
                if ($status==0){ $status = false; break; }
            }
            foreach (explode(',', $product->getChildPrices()) as $price) {
                $totalPrice+=$price;
            }
            //keep the minimum quantity of product
            $this->_listGroupedProducts[$product->getId()]['qty'] = min($quantities);
            $this->_listGroupedProducts[$product->getId()]['status'] = $status;
            $this->_listGroupedProducts[$product->getId()]['price'] = $totalPrice;
        }

        if ($this->_debug){
            $this->_log('Load Grouped ('.count($this->_listGroupedProducts).')');
            //print_r($this->_listGroupedProducts);
            //print_r($this->_listParentIds);
        }
    }

    /**
     * Load grouped products
     *  - keep parent and children
     *
     * @return void
     */

    protected function _loadConfigurableProducts(){

        $connection = $this->_coreResource->getConnection('core_read');
        $query = 'SELECT * FROM '.$this->_table['catalog_product_super_attribute'];

        $configurableAttributeCollection = $connection->fetchAll($query);
        foreach($configurableAttributeCollection as $sa){
            $this->_listConfigurableVariation[$sa['product_id']][] = $sa['attribute_id'];
        }

        $productCollection = Mage::getModel('lenexport/product_collection')->getCollection()->addStoreFilter($this->_id_store);
        $productCollection->addAttributeToFilter('type_id', array('in' => 'configurable'));
        $productCollection->addAttributeToSelect('name');
        if ($this->_config['product_status'] !== null){
            $productCollection->addAttributeToFilter('status', array('eq' => $this->_config['product_status']));
        }
        $productCollection->getSelect()->joinLeft($this->_table['catalog_product_super_link'] . ' AS sl',
            'sl.parent_id=e.entity_id',
            array('child_ids' => 'GROUP_CONCAT( sl.product_id)'));
        $productCollection->getSelect()->joinLeft($this->_table['catalog_product_entity_int'] . ' AS entity_int',
            'sl.parent_id=entity_int.entity_id AND entity_int.attribute_id = '.$this->_attributeStatusId,
            array('child_statuses' => 'GROUP_CONCAT( entity_int.value)'));
        $productCollection->getSelect()->joinLeft($this->_table['catalog_category_product'] . ' AS categories', 'categories.product_id=e.entity_id');
        $productCollection->getSelect()->joinLeft($this->_table['catalog_category_product_index'] . ' AS categories_index',
            '((categories_index.category_id=categories.category_id AND categories_index.product_id=categories.product_id) ) AND categories_index.store_id=' . $this->_id_store,
            array('categories_ids' => 'GROUP_CONCAT(DISTINCT categories_index.category_id)'));

        $productCollection->getSelect()->joinLeft($this->_table['core_url_rewrite'] . ' AS url',
            'url.product_id=e.entity_id AND url.target_path NOT LIKE "category%" AND is_system=1 AND ' . $this->_config["query_url_option"] . ' AND url.store_id=' . $this->_id_store,
            array('request_path' => 'MAX(DISTINCT request_path)'));

        $productCollection->getSelect()->group(array('sl.parent_id'));

        foreach ($productCollection as $product) {
            $this->_listParentIds[$product->getId()] = true;
            $name = $product->getName();
            $categoriesId = $product->getCategoriesIds();
            $url = $product->getProductUrl();

            foreach (explode(',', $product->getChildIds()) as $id) {
                $this->_listChildrenIds[$id] = array(
                    'name' => $name,
                    'id' => $product->getId(),
                    'categories_id' => $categoriesId,
                    'url' => $url,
                );
//                foreach (explode(',', $product->getPrice()) as $price) {
//                    $this->_listChildrenIds[$id]['price'] = $price;
//                    break;
//                }
//                foreach (explode(',', $product->getFinalPrice()) as $price) {
//                    $this->_listChildrenIds[$id]['final_price'] = $price;
//                    break;
//                }
            }
        }

        if ($this->_debug){
            $this->_log('Load Configurable');
            //print_r($this->_listParentIds);
            //print_r($this->_listChildrenIds);
        }
    }

    /**
     * Load Taxes
     *
     * build array _listTaxes[tax_class_id][0]['rate'|'code'|'country']
     *
     * @return void
     */

    protected function _loadTaxes(){


        $taxCalculation = Mage::getModel('tax/calculation');
        $request = $taxCalculation->getRateRequest(null, null, null, $this->_id_store);

        $connection = $this->_coreResource->getConnection('core_read');

        $query = $connection->select();
        $query->from($this->_table['tax_class'])->order(array('class_id', 'tax_calculation_rate_id'));
        $query->joinleft(array('tc' => $this->_table['tax_calculation']), 'tc.product_tax_class_id = ' . $this->_table['tax_class']. '.class_id',
            'tc.tax_calculation_rate_id');
        $query->joinleft(array('tcr' => $this->_table['tax_calculation_rate']), 'tcr.tax_calculation_rate_id = tc.tax_calculation_rate_id',
            array('tcr.rate', 'tax_country_id', 'tax_region_id'));
        $query->joinleft(array('dcr' => $this->_table['directory_country_region']), 'dcr.region_id=tcr.tax_region_id', 'code');
        $query->joinInner(array('cg' => $this->_table['customer_group']),
            'cg.tax_class_id=tc.customer_tax_class_id AND cg.customer_group_code=\'NOT LOGGED IN\'');
        $taxCollection = $connection->fetchAll($query);
        $this->_listTaxes = array();
        $tempClassId = '';
        $classValue = 0;
        foreach ($taxCollection as $tax) {
            if ($tempClassId != $tax['class_id']) {
                $classValue = 0;
            } else {
                ++$classValue;
            }
            $tempClassId = $tax['class_id'];
            if ($request['country_id'] == $tax['tax_country_id']){
                $this->_listTaxes[$tax['class_id']] = $tax['rate'];
                //$this->_listTaxes[$tax['class_id']][$classValue]['code'] = $tax['code'];
                //$this->_listTaxes[$tax['class_id']][$classValue]['country'] = $tax['tax_country_id'];
            }
        }
        if ($this->_debug){
            $this->_log('Load Tax Class ('.count($this->_listTaxes).')');
            //print_r($this->_listTaxes);
        }

        if (count($this->_listTaxes)==0){
            Mage::helper('lensync/data')->log('Tax configuration is not correct, please enable country : '.$request['country_id']);
        }
    }

    /**
     * Load additional attributes
     * check if already exist
     *
     * @return void
     */

    protected function _loadSelectedAttributes(){
        $attributeToExport = $this->_config_model->getMappingAllAttributes($this->_id_store);

        foreach($attributeToExport as $key => $value){
            if ($key == 'none') { continue; }
            if (!in_array($key, $this->_attributes)){
                $this->_attributesAdditional[] = $key;
            }
        }

        if ($this->_debug){
            $this->_log('Load New Attributes ('.count($attributeToExport).')');
            //print_r($this->_attributes);
        }

    }

    /**
     * Load attributes information
     * - attribute_id
     * - backend_type
     * - attribute_code
     * - frontend_input
     * - frontend_label
     *
     * build array _listCodeAttributes[attribute_id]['attribute_id'|'backend_type'|'attribute_code'|'frontend_input'|'frontend_label']
     *
     * @return void
     */

    protected function _loadProductAttributes(){

        $attributeCollection = Mage::getResourceModel('eav/entity_attribute_collection')
            ->setEntityTypeFilter($this->_catalogProductEntityId)
            ->addSetInfo()
            ->getData();
        $listAttributes = array();
        foreach ($attributeCollection as $attribute) {
            $listAttributes[] = $attribute['attribute_code'];
            $this->_listAttributeCode[$attribute['attribute_code']] = $attribute['attribute_id'];
            $this->_listCodeAttributes[$attribute['attribute_id']]['attribute_id'] = $attribute['attribute_id'];
            $this->_listCodeAttributes[$attribute['attribute_id']]['backend_type'] = $attribute['backend_type'];
            $this->_listCodeAttributes[$attribute['attribute_id']]['attribute_code'] = $attribute['attribute_code'];
            $this->_listCodeAttributes[$attribute['attribute_id']]['frontend_input'] = $attribute['frontend_input'];
            $this->_listCodeAttributes[$attribute['attribute_id']]['frontend_label'] = $attribute['frontend_label'];
        }

        $findDiff = array_diff($this->_attributes, $listAttributes);
        $this->_attributes = array_diff($this->_attributes, $findDiff);
        if ($this->_debug){
            $this->_log('Load Attributes ('.count($this->_listCodeAttributes).')');
            //print_r($this->_listCodeAttributes);
        }
    }

    /**
     * Load option values
     *
     * build array _listOptionValues[attribute_id][option_id] = value
     *
     * @return void
     */

    protected function _loadOptionValues(){
        $attributeIdToQuery = array();
        foreach($this->_listCodeAttributes as $codeAttribute){
            if ( in_array($codeAttribute['frontend_input'], array('select','multiselect') )){
                $attributeIdToQuery[] = $codeAttribute['attribute_id'];
            }
        }

        if (count($attributeIdToQuery)){
            $connection = $this->_coreResource->getConnection('core_read');
            $query = 'SELECT * FROM '.$this->_table['eav_attribute_option'].' eavo
            LEFT JOIN '.$this->_table['eav_attribute_option_value'].' eavov ON ( eavo.option_id = eavov.option_id )
            WHERE eavo.attribute_id IN ('.join(',',$attributeIdToQuery).') AND eavov.store_id = 0';
            $entityOptionValueCollection = $connection->fetchAll($query);
            foreach($entityOptionValueCollection as $optionValue){
                $this->_listOptionValues[$optionValue['attribute_id']][$optionValue['option_id']] = $optionValue['value'];
            }

            $connection = $this->_coreResource->getConnection('core_read');
            $query = 'SELECT * FROM '.$this->_table['eav_attribute_option'].' eavo
            LEFT JOIN '.$this->_table['eav_attribute_option_value'].' eavov ON ( eavo.option_id = eavov.option_id )
            WHERE eavo.attribute_id IN ('.join(',',$attributeIdToQuery).') AND eavov.store_id = '.$this->_id_store;
            $entityOptionValueCollection = $connection->fetchAll($query);
            foreach($entityOptionValueCollection as $optionValue){
                $this->_listOptionValues[$optionValue['attribute_id']][$optionValue['option_id']] = $optionValue['value'];
            }
        }

        if ($this->_debug){
            $this->_log('Load Option Values ('.count($this->_listOptionValues).')');
            //print_r($this->_listOptionValues);
        }
    }

    /**
     * Load attributes values for all entities
     *
     * (int|varchar|text|decimal|datetime)
     *
     * @return void
     */

    protected function _loadProductAttributeValues(){

        $this->_loadOptionValues();
        $this->_loadAttributeValuesByType('int', $this->_table['catalog_product_entity_int']);
        $this->_loadAttributeValuesByType('varchar', $this->_table['catalog_product_entity_varchar']);
        $this->_loadAttributeValuesByType('datetime', $this->_table['catalog_product_entity_datetime']);
        $this->_loadAttributeValuesByType('text', $this->_table['catalog_product_entity_text']);
        $this->_loadAttributeValuesByType('decimal', $this->_table['catalog_product_entity_decimal']);

        if ($this->_debug){
            $this->_log('Load Attributes Values ('.count($this->_listAttributeValues).')');
            //print_r($this->_listCodeAttributes);
        }

    }

    /**
     * Load attributes values for all entities by type
     *
     * @param string $type           Entity Type (int/varchar/text/decimal/float)
     * @param string $tableName      Table Name by Entity Type
     *
     * @return void
     */

    protected function _loadAttributeValuesByType($type, $tableName){
        $connection = $this->_coreResource->getConnection('core_read');

        $attributeIdToQuery = array();
        foreach($this->_listCodeAttributes as $codeAttribute){
            if ($codeAttribute['backend_type'] == $type){
                //load only selected attributes
                if (in_array($codeAttribute['attribute_code'], $this->_attributesAdditional)){
                    $attributeIdToQuery[] = $codeAttribute['attribute_id'];
                }
            }
        }

        if ($this->_config['product_ids']){
            $sqlWhere = ' AND entity_id IN ('.$this->_config['product_ids'].') ';
        }else{
            $sqlWhere = '';
        }

        if (count($attributeIdToQuery)>0){
            $query = 'SELECT attribute_id, value, entity_id FROM '.$tableName.' WHERE attribute_id IN ('.join(',',$attributeIdToQuery).') AND store_id = 0 '.$sqlWhere;
            $entityIntCollection = $connection->fetchAll($query);
            foreach($entityIntCollection as $int){
                if ($int['value']==''){
                    $this->_listAttributeValues[$int['entity_id']][$this->_listCodeAttributes[$int['attribute_id']]['attribute_code']] = '';
                }else{
                    if ($this->_listCodeAttributes[$int['attribute_id']]['frontend_input'] == 'select' && $this->_listCodeAttributes[$int['attribute_id']]['backend_type'] == 'int'){
                        if (isset($this->_listOptionValues[$int['attribute_id']][$int['value']])){
                            $this->_listAttributeValues[$int['entity_id']][$this->_listCodeAttributes[$int['attribute_id']]['attribute_code']] =  $this->_listOptionValues[$int['attribute_id']][$int['value']] ;
                        }else{
                            $this->_listAttributeValues[$int['entity_id']][$this->_listCodeAttributes[$int['attribute_id']]['attribute_code']] =  $int['value'];
                        }
                    }else{
                        $this->_listAttributeValues[$int['entity_id']][$this->_listCodeAttributes[$int['attribute_id']]['attribute_code']] =  $int['value'];
                    }
                }
            }
            $query = 'SELECT attribute_id, value, entity_id FROM '.$tableName.' WHERE attribute_id IN ('.join(',',$attributeIdToQuery).') AND store_id = '.$this->_id_store.' '.$sqlWhere;
            $entityIntCollection = $connection->fetchAll($query);
            foreach($entityIntCollection as $int){
                if ($int['value']==''){
                    $this->_listAttributeValues[$int['entity_id']][$this->_listCodeAttributes[$int['attribute_id']]['attribute_code']] = '';
                }else{
                    if ($this->_listCodeAttributes[$int['attribute_id']]['frontend_input'] == 'select' && $this->_listCodeAttributes[$int['attribute_id']]['backend_type'] == 'int'){
                        if (isset($this->_listOptionValues[$int['attribute_id']][$int['value']])){
                            $this->_listAttributeValues[$int['entity_id']][$this->_listCodeAttributes[$int['attribute_id']]['attribute_code']] =  $this->_listOptionValues[$int['attribute_id']][$int['value']] ;
                        }else{
                            $this->_listAttributeValues[$int['entity_id']][$this->_listCodeAttributes[$int['attribute_id']]['attribute_code']] =  $int['value'];
                        }
                    }else{
                        $this->_listAttributeValues[$int['entity_id']][$this->_listCodeAttributes[$int['attribute_id']]['attribute_code']] =  $int['value'];
                    }
                }
            }
        }

        if ($this->_debug){
            $this->_log('Load Attributes Values '.$type.'  ('.count($this->_listAttributeValues).')');
            //print_r($this->_listAttributeValues);
        }

    }

    /**
     * Calculate price with Taxes
     *
     * @param float $price             Price
     * @param integer $taxClassId      Tax Class Id
     *
     * @return float price
     */

    protected function _calculatePrice($price, $taxClassId){
        $currentRate = $this->_listTaxes;
        if (!$this->_config['include_tax'] && isset($currentRate[$taxClassId])) {
            if (count($currentRate[$taxClassId]) > 1) {
                return round($price,2);
            } else {
                return round($price * ($currentRate[$taxClassId] / 100 + 1),2);
            }
        } else {
            return round($price,2);
        }
    }

    /**
     * Get Store attribute value
     *
     * @param integer $productId             Id Product
     * @param varchar $attributeCode         Attribute Code
     *
     * @return float price
     */

    protected function _getAttributeValue($productId,$attributeCode){
        if (isset($this->_listAttributeValues[$productId]) && isset($this->_listAttributeValues[$productId][$attributeCode])){
            return $this->_listAttributeValues[$productId][$attributeCode];
        }else{
            return '';
        }
    }

    /**
     * File generation
     *
     * @param array $data
     */
    protected  function _write($data)
    {
        if($this->_stream == false) {
            if(!$this->_file) {
                $this->_initFile();
            }
            $this->_file->streamLock();
            $this->_file->streamWrite($data);
            $this->_file->streamUnlock();
        } else {
            echo $data;
        }
    }

    /**
     * Create File for export
     */
    protected  function _initFile()
    {
        if (!$this->_createDirectory()){ exit(); }

        $this->_fileTimeStamp = time();
        $this->_file = new Varien_Io_File;
        $this->_file->cd($this->_config['directory_path']);
        $this->_file->streamOpen($this->_fileName . '.' . $this->_fileTimeStamp . '.' . $this->_fileFormat, 'w+');
    }

    protected function _createDirectory(){
        try {
            $file = new Varien_Io_File;
            $file->checkAndCreateFolder($this->_config['directory_path']);
        } catch (Exception $e) {
            Mage::helper('lensync/data')->log('can\'t create folder '.$this->_config['directory_path'].'');
            if ($this->_debug){
                $this->_log('can\'t create folder '.$this->_config['directory_path']);
            }
            return false;
        }
        return true;
    }

    /**
     * Copies the file to the correct folder
     */
    protected  function _copyFile()
    {
        $file_path = $this->_config['directory_path'];
        copy($file_path . $this->_fileName . '.' . $this->_fileTimeStamp . '.' . $this->_fileFormat, $file_path . $this->_fileName . '.' . $this->_fileFormat);
        unlink($file_path . $this->_fileName . '.' . $this->_fileTimeStamp . '.' . $this->_fileFormat);
    }

    /**
     * get current microtime float
     *
     * @return float
     */
    protected function microtime_float()
    {
        list($usec, $sec) = explode(' ', microtime());
        return ((float)$usec + (float)$sec);
    }

    /**
     * Display log time + title
     *
     * @param float $timeStart
     * @param string $title
     *
     * @return void
     */
    protected function _stop($timeStart, $title){
        $time_end = $this->microtime_float();
        $time = $time_end - $timeStart;
        if ($time<0.0001){ $time = 0;}
        echo round($time,4).' &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; '.$title.'  secondes <br/>';
    }

    /**
     * Display log time + title
     *
     * @param string $title
     *
     * @return void
     */
    protected function _log($title){
        if($this->_stream){ return;}
        $time_end = $this->microtime_float();
        $time = $time_end - $this->_startScript;

        echo date('Y-m-d h:i:s').'|'.str_pad(sprintf('%0.4f', round($time,4)),10,'0',STR_PAD_LEFT).' '.str_pad($title, 40, '=', STR_PAD_BOTH).'<br/>';
    }

    /**
     * Is Feed Already Launch
     *
     * @return boolean
     */
    protected function _isAlreadyLaunch(){

        $directory = $this->_config['directory_path'];
        if (!$this->_createDirectory()){
            exit();
        }

        try {
            $listFiles = array_diff(scandir($directory), array('..', '.'));
        } catch (Exception $e) {
            Mage::helper('lensync/data')->log('Can\'t access folder '.$this->_config['directory_path']);
            if ($this->_debug){
                $this->_log('Can\'t access folder '.$this->_config['directory_path']);
            }
            exit();
        }
        foreach ($listFiles as $file) {
            if (preg_match('/^' . $this->_fileName . '\.[\d]{10}/', $file)) {
                $fileModified = date('Y-m-d H:i:s', filemtime($directory . $file));
                $fileModifiedDatetime = new DateTime($fileModified);
                $fileModifiedDatetime->add(new DateInterval('P10D'));

                if (date('Y-m-d') > $fileModifiedDatetime->format('Y-m-d')) {
                    unlink($directory . $file);
                }

                $fileModifiedDatetime = new DateTime($fileModified);
                $fileModifiedDatetime->add(new DateInterval('PT20S'));
                if (date('Y-m-d H:i:s') < $fileModifiedDatetime->format('Y-m-d H:i:s')) {
                    return true;
                }
            }
        }
        return false;
    }

}
<?php

/**
 * Lengow_Tracker Tracking Block
 *
 * @category   Lengow
 * @package    Lengow_Tracker
 * @author     Romain Le Polh <romain@lengow.com>
 */

class Lengow_Tracker_Block_Tracker extends Mage_Core_Block_Template {

    const URI_TAG_CAPSULE = 'https://tracking.lengow.com/tagcapsule.js';
    const BLOCK_SIMPLE = 'tracker/tag_simple';
    const BLOCK_CAPSULE = 'tracker/tag_capsule';
    
    protected $_id_client;
    protected $_id_group;
    protected $_tag;

    protected function _construct() {
        $config_model = Mage::getSingleton('tracker/config');
        
        $this->_id_client = $config_model->get('general/login');
        $this->_id_group = $config_model->get('general/group');
        $this->_tag = $config_model->get('tag/type');

    }
    
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if($this->_tag == 'tagcapsule')
            $this->setChild('tracker', $this->getLayout()->createBlock(self::BLOCK_CAPSULE, 'tag_capsule'));
        elseif($this->_tag == 'simpletag')
            $this->setChild('tracker', $this->getLayout()->createBlock(self::BLOCK_SIMPLE, 'simple_tag'));
        
        return $this;
    }
    
    /**
     * Prepare and return block's html output
     *
     * @return string
     */
    protected function _toHtml() {
        return parent::_toHtml();
    }
    
    
    
    
    /**
     * Retrieve Page Type
     *
     * @return string
     */
    public function getPage() {
        if (!$this->hasData('page')) {

            $this->setPage('page'); //by default

            $handles = $this->getLayout()->getUpdate()->getHandles();

            if (in_array('cms_index_index', $handles))
                $this->setPage('homepage');

            if (in_array('checkout_onepage_success', $handles))
                $this->setPage('confirmation');

            if (in_array('catalog_category_view', $handles))
                $this->setPage('listepage');

            if (in_array('checkout_cart_index', $handles))
                $this->setPage('basket');
        }
        return $this->getData('page');
    }

    /**
     * Retrieve if SSL is used for display tag capsule 
     *
     * @return string
     */
    public function useSSL() {
        if (!$this->hasData('use_ssl')) {
            $this->setUseSsl(Mage::getStoreConfig('lengow_export/general/ssl_tagcapsule'));
        }
        return $this->getData('use_ssl') == true ? 'true' : 'false';
    }

}

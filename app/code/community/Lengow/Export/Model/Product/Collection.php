<?php

class Lengow_Export_Model_Product_Collection extends Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection
{

    public function isEnabledFlat()
    {
        return false;
    }
    public function getCollection(){
        return $this;
    }

    /**
     * Initialize resources
     *
     */
    protected function _construct()
    {

        $this->_init('lenexport/catalog_product');
        $this->_initTables();
    }

}
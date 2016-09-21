<?php

/**
 * Lengow dashboard model config
 *
 * @category    Lengow
 * @package     Lengow_Dashboard
 * @author      Romain Le Polh <romain@lengow.com>
 * @copyright   2013 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Lengow_Dashboard_Model_Config extends Varien_Object {

    public function setStore($id_store) {
        $this->_id_store = $id_store;
    }

    public function get($key) {
        return Mage::getStoreConfig('lentracker/' . $key, $this->_id_store);
    }

}
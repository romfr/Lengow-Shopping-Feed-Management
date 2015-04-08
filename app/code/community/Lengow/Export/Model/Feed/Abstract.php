<?php
/**
 * Lengow export feed abstract
 *
 * @category    Lengow
 * @package     Lengow_Export
 * @author      Ludovic Drin <ludovic@lengow.com>
 * @copyright   2013 Lengow SAS 
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
abstract class Lengow_Export_Model_Feed_Abstract {

    /**
     * Version.
     */
    const VERSION = '1.0.0';

    protected $_fields;

    protected $_content_type;

    public function getContentType() {
    }

    public function setFields($array = array()) {   
      $this->_fields = $array;
    }

    public function makeHeader() {   
    }

    public function makeData($array, $args = array()) {
    }

    public function makeFooter()  {
    }
}
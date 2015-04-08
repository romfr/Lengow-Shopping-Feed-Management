<?php
/**
 * Lengow export feed xml
 *
 * @category    Lengow
 * @package     Lengow_Export
 * @author      Ludovic Drin <ludovic@lengow.com>
 * @copyright   2013 Lengow SAS 
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Lengow_Export_Model_Feed_Xml extends Lengow_Export_Model_Feed_Abstract {

    protected $_content_type = 'text/xml';

    public function getContentType() {
        return $this->_content_type;
    }

    public function makeHeader() {    
        return '<?xml version="1.0" ?>' . "\r\n"
             . '<catalog>' . "\r\n";
    }

    public function makeData($array, $args = array()) {
        $line = '<product>' . "\r\n";
        foreach($this->_fields as $name) {
            $line .= '<' . $name . '><![CDATA[' . (isset($array[$name]) ? $array[$name] : '') . ']]></' . $name . '>' . "\r\n";
        }
        $line .= '</product>' . "\r\n";
        return $line;
    }

    public function makeFooter()  {
        return '</catalog>';
    }

}
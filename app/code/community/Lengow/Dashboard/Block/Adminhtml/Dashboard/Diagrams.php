<?php
/**
 * Lengow dashboard Block Dashboard diagrams
 *
 * @category    Lengow
 * @package     Lengow_Dashboard
 * @author      Romain Le Polh <romain@lengow.com>
 * @copyright   2013 Lengow SAS 
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Lengow_Dashboard_Block_Adminhtml_Dashboard_Diagrams extends Mage_Adminhtml_Block_Dashboard_Diagrams {
    
    protected $_data = array();
    
    public function __construct() {
        parent::__construct();
    }
    
    protected function _prepareLayout(){
        parent::_prepareLayout();
        $this->addTab('lengow', array(
            'label'     => Mage::helper('adminhtml')->__('Lengow'),
            'content'   => $this->getLayout()->createBlock('dashboard/adminhtml_dashboard_charts')->toHtml(),
            'active'   => false
        ));
    }
    
}
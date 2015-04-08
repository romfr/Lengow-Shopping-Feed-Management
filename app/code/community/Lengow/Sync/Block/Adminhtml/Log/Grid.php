<?php
/**
 * Lengow adminhtml log grid
 *
 * @category    Lengow
 * @package     Lengow_Sync
 * @author      Ludovic Drin <ludovic@lengow.com>
 * @copyright   2013 Lengow SAS 
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Lengow_Sync_Block_Adminhtml_Log_Grid extends Mage_Adminhtml_Block_Widget_Grid {	

    public function __construct() {
        parent::__construct();
        $this->setId('sales_sync_log_grid');
        $this->setUseAjax(true);
        $this->setDefaultSort('id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection() {
        $collection = Mage::getModel('sync/log')->getCollection();        
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns() {
        $this->addColumn('id', array(
            'header'=> Mage::helper('sync')->__('ID'),
            'width' => '80px',
            'type'  => 'text',
            'index' => 'id',
        ));
        $this->addColumn('date', array(
            'header' => Mage::helper('sync')->__('Created at'),
            'index' => 'date',
            'type' => 'datetime',
            'width' => '100px',
        ));
        $this->addColumn('message', array(
            'header' => Mage::helper('sync')->__('Message'),
            'index' => 'message',
        ));
        return parent::_prepareColumns();
    }

    public function getGridUrl() {
        return $this->getUrl('*/*/grid', array('_current'=>true));
    }

}

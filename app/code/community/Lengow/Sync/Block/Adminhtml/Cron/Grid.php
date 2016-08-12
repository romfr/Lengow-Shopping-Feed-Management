<?php
/**
 * Lengow adminhtml cron grid
 *
 * @category    Lengow
 * @package     Lengow_Cron
 * @author      Pierre Basile <pierre.basile@lengow.com>
 * @copyright   2015 Lengow SAS
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Lengow_Sync_Block_Adminhtml_Cron_Grid extends Mage_Adminhtml_Block_Widget_Grid {

    public function __construct()
    {
        parent::__construct();
        $this->setId('sales_sync_cron_grid');
        $this->setUseAjax(true);
        $this->setDefaultSort('scheduled_at');
        $this->setDefaultDir('ASC');
        $this->setDefaultFilter(array('job_code' => 'lengow'));
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('cron/schedule')->getCollection();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }


    public function getTestButtonHtml()
    {
        return $this->getChildHtml('test_button');
    }

    protected function _prepareColumns()
    {


       $viewHelper = $this->helper('lensync/cron');

        $this->addColumn('id', array(
            'header'=> Mage::helper('lensync')->__('ID'),
            'index' => 'schedule_id',
            'width' => '80px',
            'type'  => 'text',
        ));
        $this->addColumn('job_code', array(
            'header' => Mage::helper('lensync')->__('Code'),
            'index' => 'job_code',
            'width' => '100px',
        ));
        $this->addColumn('message', array(
            'header' => Mage::helper('lensync')->__('Message'),
            'index' => 'messages',
        ));
        $this->addColumn('created_at', array(
            'header' => Mage::helper('lensync')->__('Created at'),
            'index' => 'created_at',
            'type' => 'datetime',
        ));
        $this->addColumn('scheduled_at', array(
            'header' => Mage::helper('lensync')->__('Scheduled at'),
            'index' => 'scheduled_at',
            'type' => 'datetime',
        ));
        $this->addColumn('executed_at', array(
            'header' => Mage::helper('lensync')->__('Executed at'),
            'index' => 'executed_at',
            'type' => 'datetime',
        ));
        $this->addColumn('finished_at', array(
            'header' => Mage::helper('lensync')->__('Finished at'),
            'index' => 'finished_at',
            'type' => 'datetime',
        ));
        $this->addColumn(
            'status',
            array(
                'header'         => Mage::helper('lensync')->__('Status'),
                'index'          => 'status',
                'frame_callback' => array($viewHelper, 'decorateStatus'),
            )
        );
        return parent::_prepareColumns();
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current'=>true));
    }

}
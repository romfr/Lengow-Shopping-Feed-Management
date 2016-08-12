<?php

/**
 * @category    Lengow
 * @package     Lengow_Sync
 * @author      Romain Le Polh <romain@lengow.com>
 * @copyright   2013 Lengow SAS
 */

$installer = $this;
$installer->startSetup();

/*
 * Add customer infos
 *  - from_lengow @boolean
 */
$customer_entity_id = $installer->getEntityTypeId('customer');
$from_lengow = $installer->getAttribute($customer_entity_id, 'from_lengow');
if(!$from_lengow) {
    $installer->addAttribute('customer', 'from_lengow', array(
        'type' => 'int',
        'label' => 'From Lengow ',
        'visible' => true,
        'required' => false,
        'unique' => false,
        'sort_order' => 700,
        'default' => 0,
        'input' => 'select',
        'source' => 'eav/entity_attribute_source_boolean',
    ));
    $usedInForms = array(
        'adminhtml_customer',
    );
    $attribute = Mage::getSingleton('eav/config')->getAttribute('customer', 'from_lengow');
    $attribute->setData('used_in_forms', $usedInForms);
    $attribute->setData('sort_order', 700);
    $attribute->save();
}

/*
 * Add order infos
 *  - lengow_Sync_id - string
 *  - feed_id_lengow - integer
 *  - marketplace_lengow - string
 *  - total_paid_lengow - float
 *  - carrier - string
 *  - message - string
 *  - xml_node - string
 *  - from_lengow - boolean
 */
$order_entity_id = $installer->getEntityTypeId('order');

$list_attribute = array();
$list_attribute[] = array(
    'name' => 'from_lengow',
    'label' => 'From Lengow',
    'type' => 'int',
    'input' => 'select',
    'source' => 'eav/entity_attribute_source_boolean',
    'default' => 0,
    'grid' => true,
);
$list_attribute[] = array(
    'name' => 'order_id_lengow',
    'label' => 'Lengow order ID',
    'type' => 'text',
    'input' => 'text',
    'source' => '',
    'default' => '',
    'grid' => true,
);
$list_attribute[] = array(
    'name' => 'fees_lengow',
    'label' => 'Fees',
    'type' => 'float',
    'input' => 'text',
    'source' => '',
    'default' => 0,
    'grid' => true,
);
$list_attribute[] = array(
    'name' => 'xml_node_lengow',
    'label' => 'XML Node',
    'type' => 'text',
    'input' => 'text',
    'source' => '',
    'default' => '',
    'grid' => false,
);
$list_attribute[] = array(
    'name' => 'feed_id_lengow',
    'label' => 'Feed ID',
    'type' => 'float',
    'input' => 'text',
    'source' => '',
    'default' => 0,
    'grid' => false,
);
$list_attribute[] = array(
    'name' => 'message_lengow',
    'label' => 'Message',
    'type' => 'text',
    'input' => 'text',
    'source' => '',
    'default' => '',
    'grid' => false,
);
$list_attribute[] = array(
    'name' => 'marketplace_lengow',
    'label' => 'marketplace',
    'type' => 'text',
    'input' => 'text',
    'source' => '',
    'default' => '',
    'grid' => true,
);
$list_attribute[] = array(
    'name' => 'total_paid_lengow',
    'label' => 'Total Paid',
    'type' => 'text',
    'input' => 'text',
    'source' => '',
    'default' => '',
    'grid' => false,
);

foreach($list_attribute as $attr) {
    $order_attribute = $installer->getAttribute($order_entity_id, $attr['name']);
    if(!$order_attribute) {
        $installer->addAttribute('order', $attr['name'], array(
            'name' => $attr['name'],
            'label' => $attr['label'],
            'type' => $attr['type'],
            'visible' => true,
            'required' => false,
            'unique' => false,
            'filterable' => 1,
            'sort_order' => 700,
            'default' => $attr['default'],
            'input' => $attr['input'],
            'source' => $attr['source'],
            'grid'   => $attr['grid'],
        ));
    }
    $usedInForms = array(
        'adminhtml_order',
    );
}

$installer->endSetup();

$installer = new Mage_Catalog_Model_Resource_Eav_Mysql4_Setup('core_setup');

$entity_id = $installer->getEntityTypeId('catalog_product');
$attribute = $installer->getAttribute($entity_id,'lengow_product');

if(!$attribute){
    $installer->addAttribute('catalog_product', 'lengow_product', array(
        'type'              => 'int',
        'backend'           => '',
        'frontend'          => '',
        'label'             => 'Publish on Lengow',
        'input'             => 'boolean',
        'global'            => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
        'visible'           => 1,
        'required'          => 0,
        'user_defined'      => 1,
        'default'           => 0,
        'searchable'        => 0,
        'filterable'        => 0,
        'comparable'        => 0,
        'visible_on_front'  => 1,
        'unique'            => 0,
        'used_in_product_listing' => 1
    ));
}else{
    $installer->updateAttribute('catalog_product','lengow_product','default_value', 0);
    $installer->updateAttribute('catalog_product','lengow_product','is_global', Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE);
}

// TODO use Mage ORM
$installer->run(
            "CREATE TABLE IF NOT EXISTS `{$this->getTable('lengow_log')}` (
            `id` int(11) NOT NULL auto_increment,
            `date` timestamp NOT NULL default CURRENT_TIMESTAMP,
            `message` text NOT NULL,
            PRIMARY KEY  (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

$installer->endSetup();
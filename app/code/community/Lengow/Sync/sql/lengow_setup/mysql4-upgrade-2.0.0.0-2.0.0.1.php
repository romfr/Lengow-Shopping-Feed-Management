<?php

/**
 * @category    Lengow
 * @package     Lengow_Sync
 * @author      Ludovic DRIN <romain@lengow.com>
 * @copyright   2013 Lengow SAS
 */

$installer = $this;
$installer->startSetup();

/*
 * Add order infos
 *  - carrier - string
 */
$order_entity_id = $installer->getEntityTypeId('order');

$list_attribute[] = array(
    'name' => 'carrier_lengow',
    'label' => 'Carrier',
    'type' => 'text',
    'input' => 'text',
    'source' => '',
    'default' => '',
    'grid' => false,
);

$list_attribute[] = array(
    'name' => 'carrier_method_lengow',
    'label' => 'Carrier method',
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
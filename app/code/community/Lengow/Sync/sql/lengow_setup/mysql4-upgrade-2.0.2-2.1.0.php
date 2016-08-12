<?php

/**
 * @category    Lengow
 * @package     Lengow_Sync
 * @author      Benjamin Le Nevé <benjamin.le-neve@lengow.com>
 * @copyright   2015 Lengow SAS
 */

$installer = $this;
$installer->startSetup();

/*
 * Add order infos
 *  - Carrier tracking - text
 *  - Carrier id relay - text
 */
$order_entity_id = $installer->getEntityTypeId('order');

$list_attribute[] = array(
    'name' => 'carrier_tracking_lengow',
    'label' => 'Carrier tracking',
    'type' => 'text',
    'input' => 'text',
    'source' => '',
    'default' => '',
    'grid' => false,
);

$list_attribute[] = array(
    'name' => 'carrier_id_relay_lengow',
    'label' => 'Carrier id relay',
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

/*
 * fix product selection by store
 */

$installer->updateAttribute('catalog_product','lengow_product','is_global', Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE);

$order_entity_id = $installer->getEntityTypeId('order');

$new_attributes = array("lengow_product");

// Add new Attribute group
$groupName = 'Lengow';

$entityTypeId = $installer->getEntityTypeId('catalog_product');

//Add group Lengow in all Attribute Set
$attributeSetCollection = Mage::getResourceModel('eav/entity_attribute_set_collection')->load();
foreach ($attributeSetCollection as $id => $attributeSet) {

    // Add group lengow in attribute set
    $installer->addAttributeGroup($entityTypeId, $attributeSet->getId(), $groupName, 100);
    $attributeGroupId = $installer->getAttributeGroupId($entityTypeId, $attributeSet->getId(), $groupName);

    // Add new attribute (lengow_product) on Group (Lengow)
    foreach($new_attributes as $attribute_code) {
        $attributeId = $installer->getAttributeId('catalog_product', $attribute_code);
        $entityTypeId = $attributeSet->getEntityTypeId();
        $installer->addAttributeToGroup($entityTypeId, $attributeSet->getId(), $attributeGroupId, $attributeId, null);
    }
}

$installer->endSetup();
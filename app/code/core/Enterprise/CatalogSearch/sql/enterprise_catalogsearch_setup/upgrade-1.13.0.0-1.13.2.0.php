<?php
/**
 * Magento Enterprise Edition
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Magento Enterprise Edition License
 * that is bundled with this package in the file LICENSE_EE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.magentocommerce.com/license/enterprise-edition
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Enterprise
 * @package     Enterprise_CatalogSearch
 * @copyright   Copyright (c) 2014 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */

/* @var $this Mage_Core_Model_Resource_Setup */

/** @var $client Enterprise_Mview_Model_Client */
$client = Mage::getModel('enterprise_mview/client');
$client->init('catalogsearch_fulltext');

// Add subscription to catalog_category_product, catalog_product_website and cataloginventory_stock_item
$subscriptions = array(
    $this->getTable('catalog/category_product')   => 'product_id',
    $this->getTable('catalog/product_website')    => 'product_id',
    $this->getTable('cataloginventory/stock_item')      => 'product_id',
);

foreach ($subscriptions as $targetTable => $targetColumn) {
    $arguments = array(
        'target_table'  => $targetTable,
        'target_column' => $targetColumn,
    );
    $client->execute('enterprise_mview/action_changelog_subscription_create', $arguments);
}

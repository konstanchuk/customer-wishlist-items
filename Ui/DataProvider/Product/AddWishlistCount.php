<?php

/**
 * Customer Wishlist Items Extension for Magento 2
 *
 * @author     Volodymyr Konstanchuk http://konstanchuk.com
 * @copyright  Copyright (c) 2017 The authors
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 */

namespace Konstanchuk\CustomerWishlistItems\Ui\DataProvider\Product;

use Magento\Ui\DataProvider\AddFieldToCollectionInterface;
use Magento\Framework\Data\Collection;


class AddWishlistCount implements AddFieldToCollectionInterface
{
    public function addField(Collection $collection, $field, $alias = null)
    {
        $wishlistItemTable = $collection->getConnection()->getTablename('wishlist_item');
        $subQuery = new \Zend_Db_Expr(sprintf('(SELECT SUM(qty) AS `wishlist_product_count`, product_id FROM %s GROUP BY product_id)', $wishlistItemTable));
        $collection->getSelect()->joinLeft(
            $subQuery,
            'e.entity_id = t.product_id',
            ['wishlist_product_count' => new \Zend_Db_Expr('IFNULL(t.wishlist_product_count, 0)')]
        );
    }
}
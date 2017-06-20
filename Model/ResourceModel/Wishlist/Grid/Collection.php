<?php

/**
 * Customer Wishlist Items Extension for Magento 2
 *
 * @author     Volodymyr Konstanchuk http://konstanchuk.com
 * @copyright  Copyright (c) 2017 The authors
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 */

namespace Konstanchuk\CustomerWishlistItems\Model\ResourceModel\Wishlist\Grid;

use Magento\Customer\Ui\Component\DataProvider\Document;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Psr\Log\LoggerInterface as Logger;
use Magento\Eav\Model\ResourceModel\Entity\Attribute as EavResourceModel;
use Magento\Eav\Model\Entity\Attribute as EavAttribute;


class Collection extends \Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult
{
    /**
     * @inheritdoc
     */
    protected $document = Document::class;

    /**
     * @var \Magento\Eav\Model\ResourceModel\Entity\Attribute
     */
    protected $_eavResourceModel;

    /**
     * @var \Magento\Eav\Model\Entity\Attribute
     */
    protected $_eavAttribute;

    /**
     * Initialize dependencies.
     *
     * @param EntityFactory $entityFactory
     * @param Logger $logger
     * @param FetchStrategy $fetchStrategy
     * @param EventManager $eventManager
     * @param EavResourceModel $eavResourceModel
     * @param EavAttribute $eavAttribute
     * @param string $mainTable
     * @param string $resourceModel
     */
    public function __construct(
        EntityFactory $entityFactory,
        Logger $logger,
        FetchStrategy $fetchStrategy,
        EventManager $eventManager,
        EavResourceModel $eavResourceModel,
        EavAttribute $eavAttribute,
        $mainTable = 'wishlist_item',
        $resourceModel = '\Magento\Wishlist\Model\ResourceModel\Item'
    )
    {
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $mainTable, $resourceModel);
        $this->_eavResourceModel = $eavResourceModel;
        $this->_eavAttribute = $eavAttribute;
    }

    protected function _construct()
    {
        parent::_construct();
        $fieldsMap = [
            'updated_at' => 'wishlist.updated_at',
            'firstname' => 'customer_entity.firstname',
            'lastname' => 'customer_entity.lastname',
            'product_id' => 'main_table.product_id',
            'store_id' => 'main_table.store_id',
            'description' => 'main_table.description',
            'qty' => 'main_table.qty',
            'added_at' => 'main_table.added_at',
            /* product */
            'sku' => 'catalog_product_entity.sku',
            'name' => 'name_table.value',
        ];
        foreach ($fieldsMap as $key => $value) {
            $this->addFilterToMap($key, $value);
        }
    }

    protected function _renderFiltersBefore()
    {
        $this->getSelect()
            ->join(
                ['wishlist' => $this->getTable('wishlist')],
                'main_table.wishlist_id=wishlist.wishlist_id',
                ['customer_id']
            )
            ->join(
                ['customer_entity' => $this->getTable('customer_entity')],
                'customer_entity.entity_id=wishlist.customer_id',
                ['firstname', 'lastname']
            );
        $this->addProductData(['name']);
        parent::_renderFiltersBefore();
    }

    protected function addProductData($productAttributes)
    {
        /** add particular attribute code to this array */
        foreach ($productAttributes as $attributeCode) {
            $alias = $attributeCode . '_table';
            $attribute = $this->_eavAttribute->loadByCode(\Magento\Catalog\Model\Product::ENTITY, $attributeCode);
            /** Adding eav attribute value */
            $this->getSelect()->join(
                [$alias => $attribute->getBackendTable()],
                "main_table.product_id = $alias.entity_id AND $alias.attribute_id={$attribute->getId()}",
                //"main_table.product_id = $alias.entity_id AND $alias.attribute_id={$attribute->getId()}",
                [$attributeCode => 'value']
            );
            $this->_map['fields'][$attributeCode] = 'value';
        }
        /** adding catalog_product_entity table fields */
        $this->join(
            ['catalog_product_entity' => $this->getTable('catalog_product_entity')],
            'product_id=catalog_product_entity.entity_id',
            ['sku' => 'sku', 'type_id' => 'type_id']
        );
        $this->_map['fields']['sku'] = 'sku';
        $this->_map['fields']['type_id'] = 'type_id';
        return $this;
    }
}

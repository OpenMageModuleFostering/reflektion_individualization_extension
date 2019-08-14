<?php

/**
 * @category     Reflektion
 * @package      Reflektion_Catalogexport
 * @website      http://www.reflektion.com/ <http://www.reflektion.com/>
 * @createdOn    02 Mar 2016
 * @license      https://opensource.org/licenses/OSL-3.0
 * @description  Product attributes operations 
 */
class Reflektion_Catalogexport_Model_Feed_Product extends Reflektion_Catalogexport_Model_Feed_Base {

    // Magento product fields to Reflektion Product Feed

    private $fieldMap = array(
        'sku' => 'id',
        'name' => 'name',
        'description' => 'description',
        'product_url' => 'product_url',
        'image_url' => 'image_url',
        'thumbnail' => 'thumbnail',
        'small_image' => 'small_image',
        'category_ids' => 'breadcrumbs - Category IDs',
        'price' => 'price',
        'final_price' => 'special_price',
        'status' => 'status',
        'adj_qty' => 'Inventory Quantity',
        'is_in_stock' => 'Inventory Status',
        'type_id' => 'Product Type',
    );

    public function getFieldMap() {
        return $this->fieldMap;
    }

    // File name key
    public function getFileNameKey() {
        return 'product';
    }

    /**
     * Build collection to do query
     *
     * @param $websiteId Which website to query for collection
     */
    public function getFeedCollection($websiteId) {

        $collection = Mage::getResourceModel('catalog/product_collection');

        $collection
                ->addAttributeToSelect('name');

        $collection
                ->addAttributeToSelect('description');

        // Filter feed for given website
        $collection
                ->addWebsiteFilter($websiteId);

        $collection
                ->addPriceData(null, $websiteId); //Have to use this version so you can set the website id
        // Add stock level fields
        $collection->joinTable(
                array('at_qty' => 'cataloginventory/stock_item'), 'product_id=entity_id', array('qty' => 'qty', 'is_in_stock' => 'is_in_stock'), '{{table}}.stock_id=1', 'left');

        $prodCatTable = $collection->getTable('catalog/category_product');
        $collection->getSelect()
                ->columns(array(
                    'category_ids' =>
                    '  (select ' .
                    "      group_concat(distinct "
                    . "pc.category_id"
                    . " separator ' | ') " .
                    '  from ' . $prodCatTable . ' pc ' .
                    '  where ' .
                    '      pc.product_id = e.entity_id) '
                        ,));

        // Add full product page URL
        $baseUrl = Mage::app()->getWebsite($websiteId)->getDefaultStore()->getBaseUrl();
        $coreRewriteTable = $collection->getTable('core/url_rewrite');
        $collection->getSelect()
                ->columns(array(
                    'product_url' =>
                    "  (select " .
                    "    concat('{$baseUrl}', url.request_path) " .
                    "  from " .
                    "    {$coreRewriteTable} url " .
                    "  where  " .
                    "    id_path = concat('product/', e.entity_id) " .
                    "  limit 1) "
                        ,));

        // Add product image URL
        $imageBaseURL = Mage::app()->getWebsite($websiteId)->getDefaultStore()->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . "catalog/product";
        $collection
                ->addExpressionAttributeToSelect(
                        'image_url', "if({{image}} <> 'no_selection', " .
                        "  concat('{$imageBaseURL}', {{image}}), " .
                        "  '')", 'image'
        );
        
        $collection
                ->addExpressionAttributeToSelect(
                        'small_image', "if({{small_image}} <> 'no_selection', " .
                        "  concat('{$imageBaseURL}', {{small_image}}), " .
                        "  '')", 'small_image'
        );

        $collection
                ->addExpressionAttributeToSelect(
                        'thumbnail', "if({{thumbnail}} <> 'no_selection', " .
                        "  concat('{$imageBaseURL}', {{thumbnail}}), " .
                        "  '')", 'thumbnail'
        );
                        
        // Check Status, visibility and is_in_stock
        $collection
                ->addExpressionAttributeToSelect('cur_status', "{{status}}", 'status');

        $collection
                ->addExpressionAttributeToSelect(
                        'adj_qty', "if({{type_id}} = 'simple', " .
                        "at_qty.qty, " .
                        "if (at_qty.is_in_stock=1, " .
                        "if (at_qty.qty>0, " .
                        "at_qty.qty, at_qty.is_in_stock)," .
                        "at_qty.is_in_stock))", 'type_id'
        );

        // Custom attributes to feed
        $customAttribs = Mage::app()->getWebsite($websiteId)->getConfig('reflektion_datafeeds/feedsenabled/product_attributes');
        $collection = $this->addCustomAttributes($collection, $customAttribs, $this->fieldMap);

        return $collection;
    }

    /**
     * Add filter to collection to make it only include records necessary for automatic daily feed (instead of one-time baseline feed).
     *
     * @param Varien_Data_Collection_Db $collection Collection of data which will be spit out as feed
     */
    protected function addIncrementalFilter($collection, $incrementalDate = NULL) {
        Mage::helper('reflektion')->log('Adding incremental filters to product feed', Zend_Log::INFO, Reflektion_Catalogexport_Helper_Data::LOG_FILE);
        // Stock items Filter
        $collection->
                addAttributeToFilter('is_in_stock', 1);

        return $collection;
    }

}

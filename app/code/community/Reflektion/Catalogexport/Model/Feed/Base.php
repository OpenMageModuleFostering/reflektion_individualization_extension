<?php

/**
 * @category     Reflektion
 * @package      Reflektion_Catalogexport
 * @website      http://www.reflektion.com/ <http://www.reflektion.com/>
 * @createdOn    02 Mar 2016
 * @license      https://opensource.org/licenses/OSL-3.0
 * @description  Base file to manage attributes while exporting 
 */
class Reflektion_Catalogexport_Model_Feed_Base extends Mage_Core_Model_Abstract {

    protected $_optionValueMap = array();
    protected $_attrSetIdToName = array();

    protected function _initAttributeSets($storeId = 0) {
        $optionValueTable = Mage::getSingleton('core/resource')->getTableName('eav/attribute_option_value');
        $sql = "select option_id, value from $optionValueTable where store_id=$storeId";
        $attributeValues = Mage::getSingleton('core/resource')
                ->getConnection('default_read')
                ->fetchAll($sql);

        //create an array
        foreach ($attributeValues as $values) {
            $this->_attrSetIdToName[$values['option_id']] = $values['value'];
        }

        return $this;
    }

    /**
     * Add custom attributes selected by magento admin to query
     *
     * @param $collection Collection of data which will be spit out as feed
     * @param $customAttribs Comma separated list of attribute codes
     * @param $fieldMap Reference to fieldmap where attribute codes should also be added
     */
    protected function addCustomAttributes($collection, $customAttribs, &$fieldMap) {
        Mage::helper('reflektion')->log("Adding custom attributes include in query: {$customAttribs}", Zend_Log::INFO, Reflektion_Catalogexport_Helper_Data::LOG_FILE);
        //Check if we have any custom attributes
        if (strlen(trim($customAttribs)) > 0) {
            foreach (explode(',', $customAttribs) as $curAttrib) {
                $curAttrib = trim($curAttrib);

                $_attribute = $collection->getAttribute($curAttrib);
                if ($_attribute === false) {
                    Mage::throwException("Attribte not found: {$curAttrib}");
                }
                Mage::helper('reflektion')->log("Adding attribute to query: {$curAttrib}", Zend_Log::DEBUG, Reflektion_Catalogexport_Helper_Data::LOG_FILE);

                if ($_attribute->getFrontendInput() == "select" || $_attribute->getFrontendInput() == "multiselect") {
                    Mage::helper('reflektion')->log("Note - Attribute needs translation", Zend_Log::DEBUG, Reflektion_Catalogexport_Helper_Data::LOG_FILE);
                    $this->_optionValueMap['custom_' . $curAttrib] = true;
                }
                // Attribute to select
                $collection
                        ->addExpressionAttributeToSelect('custom_' . $curAttrib, "{{" . $curAttrib . "}}", $curAttrib)
                        ->addAttributeToSelect($curAttrib);
                // Attribute to map
                $fieldMap['custom_' . $curAttrib] = 'custom_' . $curAttrib;
            }
        }

        return $collection;
    }

    /**
     * Generate one feed for this website and store feed file at the specified path
     *
     * @param $websiteId Id of the website for which to generate data feed file
     * @param $exportPath Path to the folder where data feed files should be stored
     * @param $bBaselineFile Should this file be a baseline file or an automated daily file
     * @param $minEntityId Number representing minimum value for entity Id to export - This acts as a placeholder for where the feed export left off
     * @param $bDone Indicates when the feed generation is done
     */
    public function generate($websiteId, $exportPath, $bBaselineFile, &$minEntityId, &$bDone) {
        Mage::helper('reflektion')->log('Generating ' . $this->getFileNameKey() . ' data feed for website with Id: ' . $websiteId, Zend_Log::INFO, Reflektion_Catalogexport_Helper_Data::LOG_FILE);
        Mage::helper('reflektion')->log("Export path: {$exportPath}", Zend_Log::INFO, Reflektion_Catalogexport_Helper_Data::LOG_FILE);
        Mage::helper('reflektion')->log("Baseline feed: {$bBaselineFile}", Zend_Log::INFO, Reflektion_Catalogexport_Helper_Data::LOG_FILE);
        Mage::helper('reflektion')->log("Min entity_id: {$minEntityId}", Zend_Log::INFO, Reflektion_Catalogexport_Helper_Data::LOG_FILE);

        $bDone = false;

        $websitecode = Mage::app()->getWebsite($websiteId)->getCode();

        $incrementalDate = date("Y-m-d-H-m-s", time());
        
        $websiteName = parse_url(Mage::app()->getWebsite($websiteId)->getDefaultStore()->getBaseUrl(), PHP_URL_HOST);

        // file generate 
        $filename = $exportPath . DS . $websiteName . '_' . $websiteId . '_product_feed.csv';

        Mage::helper('reflektion')->log("Output Filename: {$filename}", Zend_Log::INFO, Reflektion_Catalogexport_Helper_Data::LOG_FILE);

        $collection = $this->getFeedCollection($websiteId);


        if (!$bBaselineFile) {
            $collection = $this->addIncrementalFilter($collection, $incrementalDate);
        }
        $headerColumns = array_values($this->getFieldMap());
        // Create output file
        $file = Mage::helper('reflektion/csvfile');
        // New file with headers
        $bSuccess = $file->open($filename, $headerColumns);

        if (!$bSuccess) {
            Mage::helper('reflektion')->log('Failed to open data feed file:' . $filename, Zend_Log::ERR, Reflektion_Catalogexport_Helper_Data::LOG_FILE);
            return false;
        }
        //get all the website attribute
        Mage::helper('reflektion')->log('Initializing attribute values', Zend_Log::INFO, Reflektion_Catalogexport_Helper_Data::LOG_FILE);
        $this->_initAttributeSets();
        $rootCatId = Mage::app()->getWebsite($websiteId)->getDefaultStore()->getRootCategoryId();
        $catlistHtml = Mage::helper('reflektion')->getTreeCategories($rootCatId);
        foreach ($collection as $curRow) {
            $curRowData = $curRow->getData();
            $rowValues = array();

            foreach ($this->getFieldMap() as $mapKey => $mapValue) {
                // If the attribute is a select or multiselect then we need to translate the
                // option id value into the display value
                if (array_key_exists($mapKey, $this->_optionValueMap)) {
                    $items = explode(",", $curRowData[$mapKey]);
                    $attrList = array();
                    foreach ($items as $item) {
                        if (array_key_exists($item, $this->_attrSetIdToName)) {
                            $attrList[] = $this->_attrSetIdToName[$item];
                        } else {
                            $attrList[] = "";
                        }
                    }
                    $rowValues[$mapValue] = implode(",", $attrList);
                } else {
                    if ($mapKey == "category_ids") {
                        $arrayTempCat = array();
                        $arrayCats = explode(" | ", $curRowData[$mapKey]);
                        foreach ($arrayCats as $arrayCat) {

                            $arrayTempCat[] = $catlistHtml[$arrayCat];
                        }
                        $curRowData[$mapKey] = implode(" | ", $arrayTempCat);
                    }
                    if (array_key_exists($mapKey, $curRowData)) {
                        $rowValues[$mapValue] = $curRowData[$mapKey];
                    } else {
                        $rowValues[$mapValue] = "";
                    }
                }
            }

            $bSuccess = $file->writeRow($rowValues);
            if (!$bSuccess) {
                Mage::helper('reflektion')->log('Failed to write to data feed file: ' . $filename, Zend_Log::ERR, Reflektion_Catalogexport_Helper_Data::LOG_FILE);
                $file->close();
                return false;
            }

            // Collect last entity Id and generate new minEntityId param
            $minEntityId = $curRow->getEntityId() + 1;
        }
        // Check if export is done
        if (count($collection) > 0) {
            $bDone = true;
        }

        $bSuccess = $file->close();
        if (!$bSuccess) {
            Mage::helper('reflektion')->log('Failed to close data feed file: ' . $filename, Zend_Log::ERR, Reflektion_Catalogexport_Helper_Data::LOG_FILE);
            return false;
        }
    }

}

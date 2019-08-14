<?php

/**
 * @category     Reflektion
 * @package      Reflektion_Catalogexport
 * @website      http://www.reflektion.com/ <http://www.reflektion.com/>
 * @createdOn    02 Mar 2016
 * @license      https://opensource.org/licenses/OSL-3.0
 * @description  Generate catalog product feeds output(CSV) in queue 
 */
class Reflektion_Catalogexport_Model_Generatefeeds {

    const REFLEKTION_FEED_PATH = 'reflektion/feeds';

    protected static $feedTypes = array(
        'product'
    );

    protected function _construct() {
        
    }

    /**
     * Return a list of possible feed types
     *
     * @returns array Array of all known feeds types
     */
    public static function getFeedTypes() {
        return self::$feedTypes;
    }

    /**
     * Generate data feeds for this specific website
     *
     * @param $websiteId Id of the website for which to generate data feeds
     * @param $bBaselineFile Should this file be a baseline file or an automated daily file
     * @param $feedType Type of feed to generate, null = generate all feeds
     * @param $minEntityId Number representing minimum value for entity Id to export - This acts as a placeholder for where the feed export left off
     */
    public function generateForWebsite($websiteId, $bBaselineFile, $feedType, &$minEntityId, &$bDone) {
        Mage::helper('reflektion')->log('Memory usage: ' . memory_get_usage(), Zend_Log::DEBUG, Reflektion_Catalogexport_Helper_Data::LOG_FILE);

        if (Mage::app()->getWebsite($websiteId)->getConfig('reflektion_datafeeds/general/allfeedsenabled') != 'enabled' ||
                Mage::app()->getWebsite($websiteId)->getConfig('reflektion_datafeeds/feedsenabled/' . $feedType) != 'enabled') {
            Mage::throwException('Data feeds or feedtype ' . $feedType . ' not enabled for website: ' . $websiteId);
        }

        $feedExportPath = Mage::getConfig()->getVarDir() . DS . Reflektion_Catalogexport_Model_Generatefeeds::REFLEKTION_FEED_PATH;
        $oIo = new Varien_Io_File();
        $oIo->checkAndCreateFolder($feedExportPath);

        $modelFeed = Mage::getModel('reflektion/feed_' . $feedType);
        $modelFeed->generate($websiteId, $feedExportPath, $bBaselineFile, $minEntityId, $bDone);
    }

}

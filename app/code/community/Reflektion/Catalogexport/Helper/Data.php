<?php

/**
 * @category     Reflektion
 * @package      Reflektion_Catalogexport
 * @website      http://www.reflektion.com/ <http://www.reflektion.com/>
 * @createdOn    02 Mar 2016
 * @license      https://opensource.org/licenses/OSL-3.0
 * 
 */
class Reflektion_Catalogexport_Helper_Data extends Mage_Core_Helper_Abstract {

    const LOG_FILE = 'reflektion.log';

    /*
     * Example of how logging should be done in this extension :
     * Mage::helper('reflektion')->log($message, Zend_Log::ERR, Reflektion_Catalogexport_Helper_Data::LOG_FILE);
     */

    public function log($message, $level = null, $file = null, $force = false) {
        $force = $force ||
                (Mage::getStoreConfig('reflektion_datafeeds/advanced/force_logging') !== "disabled");
        Mage::log($message, $level, $file, $force);
    }

    /**
     * Validate feed configuration settings for one website or all websites
     */
    public function validateFeedConfiguration($websiteId = null) {
        $websites = array();
        if ($websiteId) {
            $websites[] = $websiteId;
        } else {
            $websiteModels = Mage::app()->getWebsites(false, true);
            foreach ($websiteModels as $curWebsite) {
                $websites[] = $curWebsite->getId();
            }
        }

        //Track if feeds enabled for any website
        $bFeedsEnabled = false;
        foreach ($websites as $curWebsiteId) {
            // If config is enabled
            if (Mage::app()->getWebsite($curWebsiteId)->getConfig('reflektion_datafeeds/general/allfeedsenabled') == 'enabled') {
                $bFeedsEnabled = true;

                try {
                    // Get hostname, port & credentials
                    $sftpHost = Mage::app()->getWebsite($curWebsiteId)->getConfig('reflektion_datafeeds/connect/hostname');
                    $sftpPort = Mage::app()->getWebsite($curWebsiteId)->getConfig('reflektion_datafeeds/connect/port');
                    $sftpUser = Mage::app()->getWebsite($curWebsiteId)->getConfig('reflektion_datafeeds/connect/username');
                    $sftpPassword = Mage::app()->getWebsite($curWebsiteId)->getConfig('reflektion_datafeeds/connect/password');
                } catch (Exception $e) {
                    Mage::logException($e);
                    Mage::helper('reflektion')->log($e->getMessage(), Zend_Log::ERR, Reflektion_Catalogexport_Helper_Data::LOG_FILE);
                    Mage::throwException('Error looking up feed transfer connectivity parameters for website id: ' . $curWebsiteId);
                }
                // Check SFTP credentials
                if (strlen($sftpHost) <= 0) {
                    Mage::throwException('SFTP host (' . $sftpHost . ') is invalid for website id: ' . $curWebsiteId);
                }
                if (strlen($sftpPort) <= 0 || $sftpPort < 1 || $sftpPort > 65535) {
                    Mage::throwException('SFTP port (' . $sftpPort . ') is invalid for website id: ' . $curWebsiteId);
                }
                if (strlen($sftpUser) <= 0) {
                    Mage::throwException('SFTP user (' . $sftpUser . ') is invalid for website id: ' . $curWebsiteId);
                }
                if (strlen($sftpPassword) <= 0) {
                    Mage::throwException('SFTP password is invalid for website id: ' . $curWebsiteId);
                }
            }
        }

        // Send error message
        if (!$bFeedsEnabled) {
            Mage::throwException('Data feeds not enabled');
        }
    }

    /**
     *  Description  To get the Categories list with breadcrum
     */
    function getTreeCategories($parentId) {

        $allCats = Mage::getModel('catalog/category')->getCollection()
                ->addAttributeToSelect('*')
                ->addAttributeToFilter('is_active', '1')
                ->addAttributeToFilter('include_in_menu', '1')
                ->addAttributeToFilter('parent_id', array('eq' => $parentId));

        foreach ($allCats as $category) {
            $this->str .= $category->getName();
            $this->allCat[$category->getId()] = $this->str;
            $subcats = $category->getChildren();
            if ($subcats != '') {
                $this->str .= " > ";
                $this->getTreeCategories($category->getId());
            } else {
                $this->str = '';
            }
        }
        return $this->allCat;
    }

}

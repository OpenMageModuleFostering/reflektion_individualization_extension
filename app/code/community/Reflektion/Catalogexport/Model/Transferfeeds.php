<?php

/**
 * @category     Reflektion
 * @package      Reflektion_Catalogexport
 * @website      http://www.reflektion.com/ <http://www.reflektion.com/>
 * @createdOn    02 Mar 2016
 * @license      https://opensource.org/licenses/OSL-3.0
 * @description  Manage to transfer feed from magento to other server through SFTP 
 */
class Reflektion_Catalogexport_Model_Transferfeeds {

    /**
     * Transfer data feeds to Reflektion, triggered by cron
     *
     * @param $websiteId Id of the website for which to generate data feeds
     */
    public function transfer($websiteId) {
        try {
            Mage::helper('reflektion')->log('Transferring data feeds for website with Id: ' . $websiteId, Zend_Log::INFO, Reflektion_Catalogexport_Helper_Data::LOG_FILE);
            Mage::helper('reflektion')->log('Memory usage: ' . memory_get_usage(), Zend_Log::DEBUG, Reflektion_Catalogexport_Helper_Data::LOG_FILE);

            if (Mage::app()->getWebsite($websiteId)->getConfig('reflektion_datafeeds/general/allfeedsenabled') != 'enabled') {
                Mage::throwException('Data feeds not enabled for website: ' . $websiteId);
            }

            $fileList = $this->buildFileList($websiteId);
            $bSuccess = $this->transferFileList($websiteId, $fileList);
            if (!$bSuccess) {
                Mage::throwException('Transfer file list failed!');
            }

            Mage::helper('reflektion')->log('Sucessfully transferred data feeds for website.', Zend_Log::INFO, Reflektion_Catalogexport_Helper_Data::LOG_FILE);
        } catch (Exception $e) {
            Mage::helper('reflektion')->log('Failed to transfer data feeds for website.', Zend_Log::ERR, Reflektion_Catalogexport_Helper_Data::LOG_FILE);
            Mage::helper('reflektion')->log($e->getMessage(), Zend_Log::ERR, Reflektion_Catalogexport_Helper_Data::LOG_FILE);
            throw $e;
        }
    }

    /**
     * Build list of files to transfer, based on enabled website
     *
     * @param $websiteId Id of the website for which to generate data feeds
     * @return array List of files to transfer for this website (full path & filename specified for each)
     */
    protected function buildFileList($websiteId) {
        $feedPath = Mage::getConfig()->getVarDir() . DS . Reflektion_Catalogexport_Model_Generatefeeds::REFLEKTION_FEED_PATH;

        Mage::helper('reflektion')->log('Searching for feed files for website id: ' . $websiteId . ' here: ' . $feedPath, Zend_Log::INFO, Reflektion_Catalogexport_Helper_Data::LOG_FILE);

        // Websiteid match string
        $websiteIdMatchString = '_product_feed';

        $fileList = array();

        // Open directory
        $dh = opendir($feedPath);
        if ($dh === FALSE) {
            Mage::throwException('Failed to open feed directory: ' . $feedPath);
        }

        // Files in directory
        while (($entry = readdir($dh)) !== FALSE) {
            $fullpath = $feedPath . DS . $entry;
            // Check if have a file
            if (is_file($fullpath)) {
                // Check if our file is for the correct websiteId
                if (strpos($fullpath, $websiteIdMatchString) !== FALSE) {
                    $fileList[] = $fullpath;
                }
            }
        }
        closedir($dh);
        Mage::helper('reflektion')->log('Found ' . count($fileList) . ' feed files for website id: ' . $websiteId, Zend_Log::INFO, Reflektion_Catalogexport_Helper_Data::LOG_FILE);

        return $fileList;
    }

    /**
     * Transfer this list of files to the SFTP site for Reflektion
     *
     * @param $websiteId Id of the website for which to generate data feeds
     * @param $fileList List of file names (full path) to transfer
     * @return bool Indicates if files successfully transfered or not
     */
    protected function transferFileList($websiteId, array $fileList) {
        Mage::helper('reflektion')->log('Transferring ' . count($fileList) . ' files for website id: ' . $websiteId, Zend_Log::INFO, Reflektion_Catalogexport_Helper_Data::LOG_FILE);

        try {
            // Get hostname, port & credentials
            $sftpHost = Mage::app()->getWebsite($websiteId)->getConfig('reflektion_datafeeds/connect/hostname');
            $sftpPort = Mage::app()->getWebsite($websiteId)->getConfig('reflektion_datafeeds/connect/port');
            $sftpUser = Mage::app()->getWebsite($websiteId)->getConfig('reflektion_datafeeds/connect/username');
            $sftpPassword = Mage::app()->getWebsite($websiteId)->getConfig('reflektion_datafeeds/connect/password');
        } catch (Exception $e) {
            Mage::logException($e);
            Mage::helper('reflektion')->log($e->getMessage(), Zend_Log::ERR, Reflektion_Catalogexport_Helper_Data::LOG_FILE);
            return false;
        }

        // Connect to server
        $connection = Mage::helper('reflektion/sftpConnection');
        $bSuccess = $connection->connect($sftpHost, $sftpPort, $sftpUser, $sftpPassword);
        if (!$bSuccess) {
            Mage::helper('reflektion')->log('Failed to connect to Reflektion!', Zend_Log::ERR, Reflektion_Catalogexport_Helper_Data::LOG_FILE);
            return false;
        }
        $sftpFolder = Mage::app()->getWebsite($websiteId)->getConfig('reflektion_datafeeds/connect/path');
        $bSuccess = $connection->changeDir($sftpFolder);
        if (!$bSuccess) {
            Mage::helper('reflektion')->log('Failed to change folders to: ' . $sftpFolder, Zend_Log::ERR, Reflektion_Catalogexport_Helper_Data::LOG_FILE);
            return false;
        }

        //Iterate file list and put each file
        $bTransferSucceeded = true;
        foreach ($fileList as $curFile) {
            Mage::helper('reflektion')->log('Transferring file: ' . $curFile, Zend_Log::INFO, Reflektion_Catalogexport_Helper_Data::LOG_FILE);
            Mage::helper('reflektion')->log('Memory usage: ' . memory_get_usage(), Zend_Log::DEBUG, Reflektion_Catalogexport_Helper_Data::LOG_FILE);
            $bSuccess = $connection->putAndDeleteFile($curFile);
            if (!$bSuccess) {
                Mage::helper('reflektion')->log('Failed to transfer and delete file: ' . $curFile, Zend_Log::ERR, Reflektion_Catalogexport_Helper_Data::LOG_FILE);
                $bTransferSucceeded = false;
            }
        }

        $connection->close();

        // Check results
        if (!$bTransferSucceeded) {
            Mage::helper('reflektion')->log('Some file transfers failed!', Zend_Log::ERR, Reflektion_Catalogexport_Helper_Data::LOG_FILE);
            return false;
        } else {
            Mage::helper('reflektion')->log('Successfully transferred all files.', Zend_Log::INFO, Reflektion_Catalogexport_Helper_Data::LOG_FILE);
            return true;
        }
    }

}

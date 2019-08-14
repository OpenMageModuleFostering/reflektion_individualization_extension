<?php

/**
 * @category     Reflektion
 * @package      Reflektion_Catalogexport
 * @website      http://www.reflektion.com/ <http://www.reflektion.com/>
 * @createdOn    02 Mar 2016
 * @license      https://opensource.org/licenses/OSL-3.0
 * 
 */
class Reflektion_Catalogexport_Model_Observer {

    /**
     * Generate and send new datafeed files
     *
     * @param Mage_Cron_Model_Schedule $schedule
     * @return  Reflektion_Catalogexport_Model_Observer
     */
    public function processDailyFeeds($schedule) {

        try {
            Mage::helper('reflektion')->log('**********************************************************', Zend_Log::INFO, Reflektion_Catalogexport_Helper_Data::LOG_FILE);
            Mage::helper('reflektion')->log('Data feeds cron process started...', Zend_Log::INFO, Reflektion_Catalogexport_Helper_Data::LOG_FILE);
            Mage::helper('reflektion')->log('Is Single Store Mode: ' . Mage::app()->isSingleStoreMode(), Zend_Log::INFO, Reflektion_Catalogexport_Helper_Data::LOG_FILE);
            Mage::helper('reflektion')->log('Memory usage: ' . memory_get_usage(), Zend_Log::DEBUG, Reflektion_Catalogexport_Helper_Data::LOG_FILE);
            Mage::helper('reflektion')->log('**********************************************************', Zend_Log::INFO, Reflektion_Catalogexport_Helper_Data::LOG_FILE);
            Mage::helper('reflektion')->log('Memory usage: ' . memory_get_usage(), Zend_Log::DEBUG, Reflektion_Catalogexport_Helper_Data::LOG_FILE);

            // schedule daily feed jobs for all websites
            $this->scheduleJobs();

            // Log mem usage
            Mage::helper('reflektion')->log('Memory usage: ' . memory_get_usage(), Zend_Log::DEBUG, Reflektion_Catalogexport_Helper_Data::LOG_FILE);
            $collectionJobs = Mage::getModel('reflektion/job')
                            ->getCollection()->addFieldToFilter('status', array('eq' => Reflektion_Catalogexport_Model_Job::STATUS_SCHEDULED));
            $countScheduled = $collectionJobs->count();

            for ($k = 0; $k < $countScheduled; $k++) {
                $this->runJob(); //products
                $this->runJob(); //file transfer
            }
            Mage::helper('reflektion')->log('**********************************************************', Zend_Log::INFO, Reflektion_Catalogexport_Helper_Data::LOG_FILE);
            Mage::helper('reflektion')->log('Daily feeds cron process completed successfully.', Zend_Log::INFO, Reflektion_Catalogexport_Helper_Data::LOG_FILE);
            Mage::helper('reflektion')->log('Memory usage: ' . memory_get_usage(), Zend_Log::DEBUG, Reflektion_Catalogexport_Helper_Data::LOG_FILE);
            Mage::helper('reflektion')->log('**********************************************************', Zend_Log::INFO, Reflektion_Catalogexport_Helper_Data::LOG_FILE);
        } catch (Exception $e) {
            Mage::logException($e);
            Mage::helper('reflektion')->log('**********************************************************', Zend_Log::ERR, Reflektion_Catalogexport_Helper_Data::LOG_FILE);
            Mage::helper('reflektion')->log('Data feeds cron process failed with error:', Zend_Log::ERR, Reflektion_Catalogexport_Helper_Data::LOG_FILE);
            Mage::helper('reflektion')->log($e->getMessage(), Zend_Log::ERR, Reflektion_Catalogexport_Helper_Data::LOG_FILE);
            Mage::helper('reflektion')->log('Memory usage: ' . memory_get_usage(), Zend_Log::DEBUG, Reflektion_Catalogexport_Helper_Data::LOG_FILE);
            Mage::helper('reflektion')->log('**********************************************************', Zend_Log::ERR, Reflektion_Catalogexport_Helper_Data::LOG_FILE);
        }

        return $this;
    }

    /**
     * Schedule any daily feed jobs which are necessary when we hit the daily trigger time
     *
     *
     */
    protected function scheduleJobs() {
        try {
            Reflektion_Catalogexport_Model_Job::scheduleAllDailyJobs();
        } catch (Exception $e) {
            Mage::logException($e);
            Mage::helper('reflektion')->log('Failed to schedule daily jobs, error:', Zend_Log::ERR, Reflektion_Catalogexport_Helper_Data::LOG_FILE);
            Mage::helper('reflektion')->log($e->getMessage(), Zend_Log::ERR, Reflektion_Catalogexport_Helper_Data::LOG_FILE);
            throw $e;
        }
    }

    /**
     * Grab the next job and run it, if it exists
     */
    protected function runJob() {
        try {
            $job = Reflektion_Catalogexport_Model_Job::getNextJobFromQueue();
            if ($job !== false) {
                $job->run();
            }
        } catch (Exception $e) {
            Mage::logException($e);
            Mage::helper('reflektion')->log('Failed to run job, error:', Zend_Log::ERR, Reflektion_Catalogexport_Helper_Data::LOG_FILE);
            Mage::helper('reflektion')->log($e->getMessage(), Zend_Log::ERR, Reflektion_Catalogexport_Helper_Data::LOG_FILE);
            throw $e;
        }
    }

}

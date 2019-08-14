<?php

/**
 * @category     Reflektion
 * @package      Reflektion_Catalogexport
 * @website      http://www.reflektion.com/ <http://www.reflektion.com/>
 * @createdOn    02 Mar 2016
 * @license      https://opensource.org/licenses/OSL-3.0
 * @description  Generate Jobs in queue - manual or cron jobs 
 */
class Reflektion_Catalogexport_Model_Job extends Mage_Core_Model_Abstract {

    /**
     *  Job Types
     */
    const TYPE_GENERATE_BASELINE = 1;
    const TYPE_GENERATE_DAILY = 2;
    const TYPE_TRANSFER = 3;
    const TYPE_TRANSFER_MANUAL = 4;

    /**
     *  Statuses
     */
    const STATUS_SCHEDULED = 1;
    const STATUS_RUNNING = 2;
    const STATUS_COMPLETED = 3;
    const STATUS_ERROR = 4;
    const STATUS_MANUAL = 5;

    public function _construct() {
        parent::_construct();
        $this->_init('reflektion/job');
    }

    /**
     * Pull the next job to run from the queue and set status to running
     */
    public static function getNextJobFromQueue() {
        Mage::helper('reflektion')->log('Getting next job from the queue.', Zend_Log::INFO, Reflektion_Catalogexport_Helper_Data::LOG_FILE);
        $collection = Mage::getResourceModel('reflektion/job_collection');

        $table = $collection->getTable('reflektion/job');

        $collection->getSelect()
                ->where('status = ' . Reflektion_Catalogexport_Model_Job::STATUS_SCHEDULED . ' or status = ' . Reflektion_Catalogexport_Model_Job::STATUS_RUNNING)
                ->where(Reflektion_Catalogexport_Model_Job::STATUS_SCHEDULED . " not in (select status from {$table} mbj2 where mbj2.job_id = main_table.dependent_on_job_id) ")
                ->order('job_id')
                ->limit(1);

        foreach ($collection as $job) {
            Mage::helper('reflektion')->log('Found job id: ' . $job->getJobId(), Zend_Log::INFO, Reflektion_Catalogexport_Helper_Data::LOG_FILE);
            $job->setStatus(Reflektion_Catalogexport_Model_Job::STATUS_RUNNING);
            $job->setStartedAt(Mage::getSingleton('core/date')->gmtDate());
            $job->save();
            return $job;
        }
        Mage::helper('reflektion')->log('No jobs found.', Zend_Log::INFO, Reflektion_Catalogexport_Helper_Data::LOG_FILE);
        return false;
    }

    /**
     * Create a new job object
     */
    public static function createJob($dependentOnJobId, $websiteId, $type, $feedType, $isBaseTran = 0) {
        if (Reflektion_Catalogexport_Model_Job::TYPE_GENERATE_BASELINE == $type) {
            $status = Reflektion_Catalogexport_Model_Job::STATUS_MANUAL;
        } elseif ($isBaseTran == 1) {
            $status = Reflektion_Catalogexport_Model_Job::STATUS_MANUAL;
            $type = Reflektion_Catalogexport_Model_Job::TYPE_TRANSFER_MANUAL;
        } else {
            $status = Reflektion_Catalogexport_Model_Job::STATUS_SCHEDULED;
        }
        Mage::helper('reflektion')->log('Scheduling new job.', Zend_Log::INFO, Reflektion_Catalogexport_Helper_Data::LOG_FILE);
        $newJob = Mage::getModel('reflektion/job');
        $newJob->setDependentOnJobId($dependentOnJobId);
        $newJob->setMinEntityId(0);
        $newJob->setWebsiteId($websiteId);
        $newJob->setType($type);
        $newJob->setFeedType($feedType);
        $newJob->setScheduledAt(Mage::getSingleton('core/date')->gmtDate());
        $newJob->setStatus($status);
        $newJob->save();
        return $newJob;
    }

    /**
     * Schedule all the necessary daily jobs for today
     */
    public static function scheduleAllDailyJobs() {
        $websites = Mage::app()->getWebsites(false, true);
        foreach ($websites as $website) {
            Reflektion_Catalogexport_Model_Job::scheduleJobs($website->getId(), false);
        }
    }

    /**
     * Schedule all daily or baseline jobs for all websites to run immediately
     */
    public static function scheduleJobsAllWebsites($bBaselineFile) {
        $websites = Mage::app()->getWebsites(false, true);
        foreach ($websites as $website) {
            $websiteId = $website->getId();
            Reflektion_Catalogexport_Model_Job::scheduleJobs($websiteId, $bBaselineFile);
        }
    }

    /**
     * Schedule baseline or incremental daily jobs to run immediately
     */
    public static function scheduleJobs($websiteId, $bBaselineFile) {
        Mage::helper('reflektion')->log('Scheduling jobs for website: ' . $websiteId, Zend_Log::INFO, Reflektion_Catalogexport_Helper_Data::LOG_FILE);
        Mage::helper('reflektion')->log('All feeds for website set to: ' . Mage::app()->getWebsite($websiteId)->getConfig('reflektion_datafeeds/general/allfeedsenabled'), Zend_Log::INFO, Reflektion_Catalogexport_Helper_Data::LOG_FILE);
        $lastJobId = null;
        if (Mage::app()->getWebsite($websiteId)->getConfig('reflektion_datafeeds/general/allfeedsenabled') != 'enabled') {
            return;
        }
        // Generate jobs - enabled feeds
        foreach (Reflektion_Catalogexport_Model_Generatefeeds::getFeedTypes() as $curType) {
            // Create feed job
            if (Mage::app()->getWebsite($websiteId)->getConfig('reflektion_datafeeds/feedsenabled/' . $curType) == 'enabled') {
                // Check manual or daily
                $jobType = 0;
                if ($bBaselineFile) {
                    $jobType = Reflektion_Catalogexport_Model_Job::TYPE_GENERATE_BASELINE;
                    $isBaseTran = 1;
                } else {
                    $jobType = Reflektion_Catalogexport_Model_Job::TYPE_GENERATE_DAILY;
                    $isBaseTran = 0;
                }

                $job = Reflektion_Catalogexport_Model_Job::createJob($lastJobId, $websiteId, $jobType, $curType);
                $job->save();
                $lastJobId = $job->getJobId();
            }
        }

        // Transfer feeds job
        $job = Reflektion_Catalogexport_Model_Job::createJob($lastJobId, $websiteId, Reflektion_Catalogexport_Model_Job::TYPE_TRANSFER, NULL, $isBaseTran);
        $job->save();
    }

    /**
     * Run job
     */
    public function run() {
        try {
            Mage::helper('reflektion')->log('Running job: ' . $this->getJobId(), Zend_Log::INFO, Reflektion_Catalogexport_Helper_Data::LOG_FILE);
            Mage::helper('reflektion')->log('Website Id: ' . $this->getWebsiteId(), Zend_Log::INFO, Reflektion_Catalogexport_Helper_Data::LOG_FILE);
            Mage::helper('reflektion')->log('Dependent On Job Id: ' . $this->getDependentOnJobId(), Zend_Log::INFO, Reflektion_Catalogexport_Helper_Data::LOG_FILE);
            Mage::helper('reflektion')->log('Min Entity Id: ' . $this->getMinEntityId(), Zend_Log::INFO, Reflektion_Catalogexport_Helper_Data::LOG_FILE);
            Mage::helper('reflektion')->log('Type: ' . $this->getType(), Zend_Log::INFO, Reflektion_Catalogexport_Helper_Data::LOG_FILE);
            Mage::helper('reflektion')->log('Feed Type: ' . $this->getFeedType(), Zend_Log::INFO, Reflektion_Catalogexport_Helper_Data::LOG_FILE);
            Mage::helper('reflektion')->log('Memory usage: ' . memory_get_usage(), Zend_Log::DEBUG, Reflektion_Catalogexport_Helper_Data::LOG_FILE);

            // Execute the job
            $this->executeJob();

            Mage::helper('reflektion')->log('Job completed successfully.', Zend_Log::INFO, Reflektion_Catalogexport_Helper_Data::LOG_FILE);
            Mage::helper('reflektion')->log('Memory usage: ' . memory_get_usage(), Zend_Log::DEBUG, Reflektion_Catalogexport_Helper_Data::LOG_FILE);
        } catch (Exception $e) {
            // Fail this job
            $this->setStatus(Reflektion_Catalogexport_Model_Job::STATUS_ERROR);
            $this->setEndedAt(Mage::getSingleton('core/date')->gmtDate());
            $this->setErrorMessage($e->getMessage());
            $this->save();
            // Log exception
            Mage::logException($e);
            Mage::helper('reflektion')->log('Job failed with error:', Zend_Log::ERR, Reflektion_Catalogexport_Helper_Data::LOG_FILE);
            Mage::helper('reflektion')->log($e->getMessage(), Zend_Log::ERR, Reflektion_Catalogexport_Helper_Data::LOG_FILE);
            Mage::helper('reflektion')->log('Memory usage: ' . memory_get_usage(), Zend_Log::DEBUG, Reflektion_Catalogexport_Helper_Data::LOG_FILE);
        }

        return $this;
    }

    /**
     * Execute this job
     */
    protected function executeJob() {
        if (Mage::app()->getWebsite($this->getWebsiteId())->getConfig('reflektion_datafeeds/general/allfeedsenabled') != 'enabled') {
            Mage::throwException('Data feeds not enabled for website: ' . $this->getWebsiteId());
        }
        $bDone = false;

        // Switch on job type
        switch ($this->getType()) {
            case Reflektion_Catalogexport_Model_Job::TYPE_GENERATE_BASELINE:
                // Call - Reflektion_Catalogexport_Model_Generatefeeds
                $genModel = Mage::getModel('reflektion/generatefeeds');
                $minEntityId = $this->getMinEntityId();
                $genModel->generateForWebsite($this->getWebsiteId(), true, $this->getFeedType(), $minEntityId, $bDone);
                $this->setMinEntityId($minEntityId);
                break;
            case Reflektion_Catalogexport_Model_Job::TYPE_GENERATE_DAILY:
                // Call - Reflektion_Catalogexport_Model_Generatefeeds
                $genModel = Mage::getModel('reflektion/generatefeeds');
                $minEntityId = $this->getMinEntityId();
                $genModel->generateForWebsite($this->getWebsiteId(), false, $this->getFeedType(), $minEntityId, $bDone);
                $this->setMinEntityId($minEntityId);
                break;
            case Reflektion_Catalogexport_Model_Job::TYPE_TRANSFER:
                // Call - Reflektion_Catalogexport_Model_Transferfeeds
                $tranModel = Mage::getModel('reflektion/transferfeeds');
                $tranModel->transfer($this->getWebsiteId());
                $bDone = true;
                break;
            case Reflektion_Catalogexport_Model_Job::TYPE_TRANSFER_MANUAL:
                // Call - Reflektion_Catalogexport_Model_Transferfeeds
                $tranModel = Mage::getModel('reflektion/transferfeeds');
                $tranModel->transfer($this->getWebsiteId());
                $bDone = true;
                break;
        }

        // Job as succeeded
        if ($bDone) {
            $this->setStatus(Reflektion_Catalogexport_Model_Job::STATUS_COMPLETED);
            $this->setEndedAt(Mage::getSingleton('core/date')->gmtDate());
        }
        $this->save();
    }

}

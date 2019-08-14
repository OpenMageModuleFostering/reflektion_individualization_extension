<?php

/**
 * @category     Reflektion
 * @package      Reflektion_Catalogexport
 * @website      http://www.reflektion.com/ <http://www.reflektion.com/>
 * @createdOn    02 Mar 2016
 * @license      https://opensource.org/licenses/OSL-3.0
 * @description  Actions to process jobs in queue
 */
class Reflektion_Catalogexport_Adminhtml_JobController extends Mage_Adminhtml_Controller_Action
{


    public function indexAction()
    {
        $this->loadLayout()->_setActiveMenu("reflektion/job");
        $this->renderLayout();

    }//end indexAction()


    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('reflektion/job');

    }//end _isAllowed()


    /**
     * Export all baseline data feeds
     */
    public function exportallAction()
    {
        try {
            Mage::helper('reflektion')->validateFeedConfiguration();
        } catch (Exception $e) {
            $this->_getSession()->addError($this->__($e->getMessage()));
            $this->_redirect('*/*/index');

            return;
        }

        try {
            Mage::log(
                'Scheduling immediate baseline data feeds for all websites.',
                Zend_Log::INFO,
                Reflektion_Catalogexport_Helper_Data::LOG_FILE
            );
            // Schedule all feeds for site
            Reflektion_Catalogexport_Model_Job::scheduleJobsAllWebsites(true);
            Mage::log('Successfully scheduled feeds.', Zend_Log::INFO, Reflektion_Catalogexport_Helper_Data::LOG_FILE);
        } catch (Exception $e) {
            Mage::logException($e);
            Mage::log('Failed to schedule feeds.', Zend_Log::ERR, Reflektion_Catalogexport_Helper_Data::LOG_FILE);
            Mage::log($e->getMessage(), Zend_Log::ERR, Reflektion_Catalogexport_Helper_Data::LOG_FILE);
        }

        $this->_getSession()->addSuccess($this->__('Feed generation and transfer has been scheduled all websites.'));
        $this->_redirect('*/*/index');

    }//end exportallAction()


    public function massDeleteAction()
    {
        $jobIds = $this->getRequest()->getParam('job_id');
        if (!is_array($jobIds)) {
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('reflektion')->__('Please select jobs(s) to delete.')
            );
        } else {
            try {
                $jobModel = Mage::getModel('reflektion/job')
                        ->getCollection()
                        ->addFieldToFilter('job_id', array('in' => $jobIds));
                foreach ($jobModel as $jobs) {
                    Mage::log(
                        'Mass delete - Deleting job id '.$jobs->getJobId(),
                        Zend_Log::INFO,
                        Reflektion_Catalogexport_Helper_Data::LOG_FILE
                    );
                    $jobs->delete();
                }
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }//end if

        Mage::getSingleton('adminhtml/session')->addSuccess(
            Mage::helper('reflektion')->__(
                'Total of %d record(s) were deleted.',
                count($jobIds)
            )
        );

        Mage::log(
            'Mass delete - '.count($jobIds).' jobs deleted.',
            Zend_Log::INFO,
            Reflektion_Catalogexport_Helper_Data::LOG_FILE
        );

        $this->_redirect('*/*/index');

    }//end massDeleteAction()


    public function massRunAction()
    {
        $jobIds = $this->getRequest()->getParam('job_id');
        if (!is_array($jobIds)) {
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('reflektion')->__('Please select jobs(s) to execute.')
            );
        } else {
            try {
                $jobModel = Mage::getModel('reflektion/job')
                        ->getCollection()
                        ->addFieldToFilter('job_id', array('in' => $jobIds));
                foreach ($jobModel as $jobs) {
                    Mage::log(
                        'Mass execute - Execute job id '.$jobs->getJobId(),
                        Zend_Log::INFO,
                        Reflektion_Catalogexport_Helper_Data::LOG_FILE
                    );
                    $jobs->setStartedAt(Mage::getSingleton('core/date')->gmtDate())->save()->run();
                }
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }//end if

        Mage::getSingleton('adminhtml/session')->addSuccess(
            Mage::helper('reflektion')->__(
                'Total of %d jobs(s) were executed',
                count($jobIds)
            )
        );

        Mage::log(
            'Mass execute - '.count($jobIds).' jobs executed.',
            Zend_Log::INFO,
            Reflektion_Catalogexport_Helper_Data::LOG_FILE
        );

        $this->_redirect('*/*/index');

    }//end massRunAction()


}//end class

<?php

/**
 * @category     Reflektion
 * @package      Reflektion_Catalogexport
 * @website      http://www.reflektion.com/ <http://www.reflektion.com/>
 * @createdOn    02 Mar 2016
 * @license      https://opensource.org/licenses/OSL-3.0
 * @description  Actions to generate jobs in queue
 */
class Reflektion_Catalogexport_Adminhtml_ExportController extends Mage_Adminhtml_Controller_Action {

    public function indexAction() {
        $this->loadLayout()->_setActiveMenu("reflektion/export");
        $this->renderLayout();
    }

    /**
     * Export baseline data feed
     */
    public function exportoneAction() {
        try {
            $id = $this->getRequest()->getParam('id');
            Mage::helper('reflektion')->validateFeedConfiguration($id);
        } catch (Exception $e) {
            $this->_getSession()->addError($this->__($e->getMessage()));
            // Redirect
            $this->_redirect('*/*/index');

            return;
        }

        try {
            $id = $this->getRequest()->getParam('id');
            Mage::log('Scheduling immediate baseline data feeds for website Id: ' . $id, Zend_Log::INFO, Reflektion_Catalogexport_Helper_Data::LOG_FILE);

            // Schedule all feeds for site
            Reflektion_Catalogexport_Model_Job::scheduleJobs($id, true);
            Mage::log('Successfully scheduled feeds.', Zend_Log::INFO, Reflektion_Catalogexport_Helper_Data::LOG_FILE);
        } catch (Exception $e) {
            Mage::logException($e);
            Mage::log('Failed to schedule feeds.', Zend_Log::ERR, Reflektion_Catalogexport_Helper_Data::LOG_FILE);
            Mage::log($e->getMessage(), Zend_Log::ERR, Reflektion_Catalogexport_Helper_Data::LOG_FILE);
        }
        $this->_getSession()->addSuccess($this->__('Feed generation and transfer has been scheduled for website ID ' . $id . '.'));
        $this->_redirect('*/*/index');
    }

    /**
     * Export all baseline data feeds
     */
    public function exportallAction() {
        try {
            Mage::helper('reflektion')->validateFeedConfiguration();
        } catch (Exception $e) {
            $this->_getSession()->addError($this->__($e->getMessage()));
            $this->_redirect('*/*/index');

            return;
        }

        try {
            $id = $this->getRequest()->getParam('id');
            Mage::log('Scheduling immediate baseline data feeds for all websites.', Zend_Log::INFO, Reflektion_Catalogexport_Helper_Data::LOG_FILE);

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
    }

}

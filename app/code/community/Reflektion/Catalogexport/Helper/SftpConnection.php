<?php

/**
 * @category     Reflektion
 * @package      Reflektion_Catalogexport
 * @website      http://www.reflektion.com/ <http://www.reflektion.com/>
 * @createdOn    02 Mar 2016
 * @license      https://opensource.org/licenses/OSL-3.0
 * @description  SFTP connect, transfer and close functions
 *               - Using magento library phpseclib
 */
require_once 'phpseclib/Net/SFTP.php';

class Reflektion_Catalogexport_Helper_SftpConnection extends Mage_Core_Helper_Abstract
{

    const SFTP_TIMEOUT = 20;

    private $_oConnection = null;


    /**
     * Connect
     *
     * @return boolean
     */
    public function connect($host, $port, $user, $pw)
    {

        try {
            if (isset($this->_oConnection)) {
                $this->close();
            }

            // Config values
            $sServer   = $host;
            $sServer   = ($sServer ? trim($sServer) : '');
            $sPort     = $port;
            $sPort     = ($sPort ? trim($sPort) : '');
            $sUsername = $user;
            $sUsername = ($sUsername ? trim($sUsername) : '');
            $sPassword = $pw;
            $sPassword = ($sPassword ? trim($sPassword) : '');

            // Check credentials
            if (!strlen($sServer)) {
                Mage::throwException('Invalid SFTP host: '.$sServer);
            }

            if (!strlen($sPort) || !ctype_digit($sPort)) {
                Mage::throwException('Invalid SFTP port: '.$sPort);
            }

            if (!strlen($sUsername)) {
                Mage::throwException('Invalid SFTP user: '.$sUsername);
            }

            if (!strlen($sPassword)) {
                Mage::throwException('Invalid SFTP password: '.$sPassword);
            }

            $this->_oConnection = new Net_SFTP($sServer, $sPort, self::SFTP_TIMEOUT);
            if (!$this->_oConnection->login($sUsername, $sPassword)) {
                Mage::throwException(
                    sprintf(__("Unable to open SFTP connection as %s@%s %s", $sUsername, $sServer, $sPassword))
                );
            }

            return true;
        } catch (Exception $e) {
            Mage::logException($e);
            Mage::helper('reflektion')->log(
                $e->getMessage(),
                Zend_Log::ERR,
                Reflektion_Catalogexport_Helper_Data::LOG_FILE
            );
            Mage::helper('reflektion')->log(
                "SFTP reported error is ".$e,
                Zend_Log::INFO,
                Reflektion_Catalogexport_Helper_Data::LOG_FILE
            );
        }//end try
        return false;

    }//end connect()


    /**
     * Close
     *
     * @return boolean
     */
    public function close()
    {
        try {
            // Close connection
            if (isset($this->_oConnection)) {
                $bRes = $this->_oConnection->disconnect();
                unset($this->_oConnection);
                return $bRes;
            } else {
                Mage::throwException('Connection not open!');
            }
        } catch (Exception $e) {
            Mage::logException($e);
            Mage::helper('reflektion')->log(
                $e->getMessage(),
                Zend_Log::ERR,
                Reflektion_Catalogexport_Helper_Data::LOG_FILE
            );
        }

        return false;

    }//end close()


    /**
     * Is connected
     *
     * @return boolean
     */
    public function isConnected()
    {
        return (isset($this->_oConnection));

    }//end isConnected()


    /**
     * Change directory
     *
     * @param  string directory
     * @return boolean
     */
    public function changeDir($sDir)
    {
        try {
            if (!$this->isConnected()) {
                return false;
            }

            return $this->_oConnection->chdir($sDir);
        } catch (Exception $e) {
            Mage::logException($e);
            Mage::helper('reflektion')->log(
                $e->getMessage(),
                Zend_Log::ERR,
                Reflektion_Catalogexport_Helper_Data::LOG_FILE
            );
        }

        return false;

    }//end changeDir()


    /**
     * Make directory
     *
     * @param  string directory
     * @return boolean
     */
    public function makeDir($sDir)
    {
        try {
            // Close connection
            if (!$this->isConnected()) {
                return false;
            }

            return $this->_oConnection->mkdir($sDir);
        } catch (Exception $e) {
            Mage::logException($e);
            Mage::helper('reflektion')->log(
                $e->getMessage(),
                Zend_Log::ERR,
                Reflektion_Catalogexport_Helper_Data::LOG_FILE
            );
        }

        return false;

    }//end makeDir()


    /**
     * List files
     *
     * @param  string directory
     * @return array
     */
    public function listFiles($sDir = '.')
    {
        try {
            // Close connection
            if (!$this->isConnected()) {
                return false;
            }

            return $this->_oConnection->nlist($sDir);
        } catch (Exception $e) {
            Mage::logException($e);
            Mage::helper('reflektion')->log(
                $e->getMessage(),
                Zend_Log::ERR,
                Reflektion_Catalogexport_Helper_Data::LOG_FILE
            );
        }

        return false;

    }//end listFiles()


    /**
     * Transfer file
     *
     * @param  string Local file path
     * @return boolean
     */
    public function putFile($sLocalFilePath)
    {
        try {
            // Close connection
            if (!$this->isConnected()) {
                return false;
            }

            $sFilename = basename($sLocalFilePath);
            // Get filename
            // Transfer
            $bSuccess = $this->_oConnection->put($sFilename, $sLocalFilePath, NET_SFTP_LOCAL_FILE);
            if (!$bSuccess) {
                Mage::helper('reflektion')->log(
                    'SFTP Error: '.$this->_oConnection->getLastSFTPError(),
                    Zend_Log::ERR,
                    Reflektion_Catalogexport_Helper_Data::LOG_FILE
                );
            }

            return $bSuccess;
        } catch (Exception $e) {
            Mage::logException($e);
            Mage::helper('reflektion')->log(
                $e->getMessage(),
                Zend_Log::ERR,
                Reflektion_Catalogexport_Helper_Data::LOG_FILE
            );
        }//end try

        return false;

    }//end putFile()


    /**
     * Transfer file and delete when successful as one atomic operation
     *
     * @param  string Local file path
     * @return boolean
     */
    public function putAndDeleteFile($sLocalFilePath)
    {
        try {
            $bSuccess = $this->putFile($sLocalFilePath);
            if ($bSuccess) {
                $oIo = new Varien_Io_File();
                $oIo->rm($sLocalFilePath);
            }

            return $bSuccess;
        } catch (Exception $e) {
            Mage::logException($e);
            Mage::helper('reflektion')->log(
                $e->getMessage(),
                Zend_Log::ERR,
                Reflektion_Catalogexport_Helper_Data::LOG_FILE
            );
        }

        return false;

    }//end putAndDeleteFile()


}//end class

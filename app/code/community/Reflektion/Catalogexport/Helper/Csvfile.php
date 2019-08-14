<?php

/**
 * @category     Reflektion
 * @package      Reflektion_Catalogexport
 * @website      http://www.reflektion.com/ <http://www.reflektion.com/>
 * @createdOn    02 Mar 2016
 * @license      https://opensource.org/licenses/OSL-3.0
 * @description  CSV files operations - create, open, reopen, close and write
 */
class Reflektion_Catalogexport_Helper_Csvfile extends Mage_Core_Helper_Abstract
{

    private $_filename;
    private $_handle;
    private $_path;
    private $_errorMessage;
    private $_columnHeaders;


    public function __construct()
    {
        $this->_filename     = null;
        $this->_handle       = null;
        $this->_path         = null;
        $this->_errorMessage = null;

    }//end __construct()


    /**
     * Open file
     *
     * @param  array  $columnHeaders An array of column header names, one for each column
     * @param  string $filename      fully qualified filename + path. (directory must be writable)
     * @return boolean
     */
    public function open($filename, array $columnHeaders)
    {
        $this->_columnHeaders = $columnHeaders;
        $this->_filename      = $filename;

        try {
            // Open file
            $this->_handle = fopen($this->_filename, 'w');
            // Build header row string
            $rowString = implode(",", $this->encodeFields($columnHeaders))."\r\n";
            // Write row to file
            $result = fwrite($this->_handle, $rowString);
        } catch (Exception $e) {
            Mage::logException($e);
            return false;
        }

        return true;

    }//end open()


    /**
     * Re Open existing file
     *
     * @param  string $filename fully qualified filename + path. (directory must be writable)
     * @return boolean
     */
    public function reopen($filename, array $columnHeaders)
    {
        $this->_columnHeaders = $columnHeaders;
        $this->_filename      = $filename;

        try {
            // Reopen file
            $this->_handle = fopen($this->_filename, 'a');
        } catch (Exception $e) {
            Mage::logException($e);
            return false;
        }

        return true;

    }//end reopen()


    /**
     * Close file
     */
    public function close()
    {
        try {
            fclose($this->_handle);
        } catch (Exception $e) {
            Mage::logException($e);
            return false;
        }

        return true;

    }//end close()


    /**
     * Write row to file
     *
     * @param  array $rowValues An associative array of columns => values, cells for columns not included in this
     *         row are left empty
     * @return boolean
     */
    public function writeRow(array $rowValues)
    {
        try {
            // Filter
            $selectedRowValues = array();
            foreach ($this->_columnHeaders as $columnHeader) {
                if (array_key_exists($columnHeader, $rowValues)) {
                    $selectedRowValues[] = $rowValues[$columnHeader];
                } else {
                    $selectedRowValues[] = "";
                }
            }

            // Convert to utf8
            $convertedRowValues = $this->encodeFields($selectedRowValues);
            // Build row string
            $rowString = implode(",", $convertedRowValues)."\r\n";
            // Write row to file
            $result = fwrite($this->_handle, $rowString);
            // Check result
            if ($result != strlen($rowString)) {
                return false;
            }
        } catch (Exception $e) {
            Mage::logException($e);
            return false;
        }//end try

        return true;

    }//end writeRow()


    /**
     * Convert strings in array to Utf8 and encode for CSV file usage
     *
     * @param  array $values
     * @return array $converted
     */
    private function encodeFields(array $values)
    {
        $converted = array();
        foreach ($values as $value) {
            // Encode in utf8
            $newVal = utf8_encode($value);
            $newVal = str_replace('"', '""', $newVal);
            // Delimiter
            $newVal = '"'.$newVal.'"';
            // Converted array
            array_push($converted, $newVal);
        }

        return $converted;

    }//end encodeFields()


}//end class

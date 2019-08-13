<?php
/**
 * Extension status block
 *
 * @category    Emv
 * @package     Emv_Core
 * @copyright   Copyright (c) 2014 SmartFocus (http://www.smartfocus.com)
 * @author Minh Quang VO <minhquang.vo@smartfocus.com>
 */
class Emv_Core_Block_Adminhtml_Config_ExtensionStatus
    extends Mage_Adminhtml_Block_Abstract
    implements Varien_Data_Form_Element_Renderer_Interface
{
    const BASE_EXTENSION_DIR = 'Emv';
    const BASE_CODE_POOL     = 'community';

    /**
     * Custom template
     *
     * @var string
     */
    protected $_template = 'smartfocus/config/extension_status.phtml';

    /**
     * Help Link Url
     * @var string
     */
    protected $_helpLink = '';

    /**
     * PHP minimum version
     *
     * @var string
     */
    protected $_minPhpVersion = '5.2.6';

    /**
     * List of call back functions to test
     * @var array
     */
    protected $_callbackList = array();

    /**
     * By default, we require soap, openssl, and curl extensions
     *
     * @var array
     */
    protected $_defaultRequired = array('soap', 'openssl', 'curl');

    /**
     * You can include more PHP extensions by modifying this array
     *
     * @var array
     */
    protected $_required = array();

    /**
     * Minimum of memory size in MB
     *
     * @var int
     */
    protected $_minMemory = 512;

    /**
     * Allowed time difference in Hours
     *
     * @var int
     */
    protected $_allowedTimeDiffJob = 1;

    /**
     * Render fieldset html
     *
     * @param Varien_Data_Form_Element_Abstract $fieldset
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $fieldset)
    {
        return $this->toHtml();
    }

    /**
     * @return string
     */
    protected function _getTickImageLink() {
        return sprintf('<img src="%s" width="11" height="11" />',$this->getSkinUrl('images/smartfocus/tick.png'));
    }

    /**
     * @return string
     */
    protected function _getUnTickImageLink() {
        return sprintf('<img src="%s" width="11" height="11" />',$this->getSkinUrl('images/smartfocus/untick.gif'));
    }

    /**
     * @return string
     */
    protected function _getWarningTickImageLink() {
        return sprintf('<img src="%s" width="18" height="18" />',$this->getSkinUrl('images/smartfocus/warning.png'));
    }

    /**
     * Get status of all installed Emv Modules
     * @return string
     */
    public function getEmvModulesVersionStatus()
    {
        $namespacePath = mage::getBaseDir('base') . DS . 'app' . DS . 'code'
            . DS . self::BASE_CODE_POOL . DS . self::BASE_EXTENSION_DIR . DS;

        $message = '';
        $namespaceDir = @opendir($namespacePath);
        $moduleVersion = array();
        while ($subModule = readdir($namespaceDir)) {
            if ($this->_directoryIsValid($subModule)) {
                //parse modules within namespace
                $modulePath = $namespacePath . $subModule . DS;
                if (is_dir($modulePath)) {
                    $configXmlPath = $modulePath . 'etc/config.xml';
                    if (file_exists($configXmlPath)) {
                        $config = new Varien_Simplexml_Config();
                        $config->loadFile($configXmlPath);
                        $path = $config->getNode('modules');
                        foreach ($path->asArray() as $subModuleName => $version) {
                            $moduleVersion[] = $subModuleName . ' : ' . $version['version'];
                        }
                    }
                }
            }
        }
        closedir($namespaceDir);

        $message = Mage::helper('emvcore')->__(
            '<span class="icon-status">%s</span> List of installed modules <strong>%s</strong>.',
            $this->_getWarningTickImageLink(),
            implode(', ', $moduleVersion)
        );

        return $message;
    }

    /**
     * Check if directory name is valid
     *
     * @param string $dirName
     * @return boolean
     */
    private function _directoryIsValid($dirName) {
        switch ($dirName) {
            case '.':
            case '..':
            case '.DS_Store':
            case '':
                return false;
                break;
            default:
                return true;
                break;
        }
    }


    /**
     * Get directory permission status
     *
     * @return string
     */
    public function getDirectoryPermissionStatus()
    {
        $ok = true;
        try {
            $mageFile = new Varien_Io_File();
            $mageFile->checkAndCreateFolder(Mage::getBaseDir(Emv_Core_Helper_Data::BASE_CONTAINER));
            $mageFile->checkAndCreateFolder(
                Mage::getBaseDir(Emv_Core_Helper_Data::BASE_CONTAINER)
                . DS . Emv_Core_Helper_Data::BASE_WORKING_DIR
            );
        } catch (Exception $e) {
            $ok = false;
        }

        $message = '';
        if ($ok) {
            $image = $this->_getTickImageLink();
            $message = Mage::helper('emvcore')->__(
                '<span class="icon-status">%s</span> Write permission is correctly set to <strong>%s</strong>.',
                $image,
                Mage::getBaseDir(Emv_Core_Helper_Data::BASE_CONTAINER)
            );
        } else {
            $image = $this->_getUnTickImageLink();
            $message = Mage::helper('emvcore')->__(
                '<span class="icon-status">%s</span> Write permission is not correctly set to <strong>%s</strong>.',
                $image,
                Mage::getBaseDir(Emv_Core_Helper_Data::BASE_CONTAINER)
            );
        }
        return $message;
    }

    /**
     * Get other test list (callable functions)
     *
     * @return array
     */
    public function getOtherTestList()
    {
        return $this->_callbackList;
    }

    /**
     * Get status for a given test
     *
     * @param $testToHandle
     * @return string / boolean - false
     */
    public function getStatusForTest($testToHandle)
    {
        $status = '';
        if (method_exists($this, $testToHandle)) {
            $status = call_user_func(array($this,$testToHandle));
        }
        return $status;
    }

    /**
     * @return string
     */
    public function getHelpLink()
    {
        return $this->_helpLink;
    }

    /**
     * Get extension version
     *
     * @return string
     */
    public function getExtensionVersion()
    {
        return Mage::helper('emvcore')->__("SmartFocus Connector Status (Version %s)", Emv_Core_Helper_Data::getVersion());
    }

    /**
     * @return string
     */
    public function getMagentoVersionStatus()
    {
        $magentoLabel = '';
        if (method_exists('Mage', "getEdition")) {
            $magentoLabel = Mage::getEdition() . ' ';
        }
        $magentoLabel .= Mage::getVersion();
        return Mage::helper('emvcore')->__(
            '<span class="icon-status">%s</span> Your Magento version is <strong>%s</strong>.',
            $this->_getTickImageLink(),
            $magentoLabel
        );
    }

    /**
     * Check and get PHP environment status
     *
     * @return string
     */
    public function getPhpEnvironmentStatus()
    {
        $ok = true;

        if (!version_compare(PHP_VERSION, $this->_minPhpVersion, ">=")) {
            $phpCheck = Mage::helper('emvcore')->__(
                'Required PHP version is <strong>%s</strong> - current version <strong>%s</strong>.',
                $this->_minPhpVersion,
                PHP_VERSION
            );
            $ok = false;
        } else {
            $phpCheck = Mage::helper('emvcore')->__('Your PHP version (<strong>%s</strong>) is satisfied.', PHP_VERSION);
        }

        $required = array_merge($this->_defaultRequired, $this->_required);
        $missing = array();
        $loaded  = array();
        /*
         * Run through PHP extensions to see if they are loaded
         * if no, add them to the list of missing
         */
        foreach ($required as $extName) {
            if (!extension_loaded($extName)) {
                $missing[] = $extName;
            }
        }

        if (count($missing)) {
            $ok = false;
            $extensionCheck = Mage::helper('emvcore')->__(
                    'Required Php Extensions are <strong>%s</strong>.',
                    implode(', ', $required)
                );
            $extensionCheck .= ' ' . Mage::helper('emvcore')->__(
                    'The followings are missing : <strong>%s</strong>.',
                    implode(', ', $missing)
                );
        } else {
            $extensionCheck = Mage::helper('emvcore')->__(
                'All the required Php Extensions (<strong>%s</strong>) are correctly installed.',
                implode(', ', $required)
            );
        }

        if ($ok) {
            $image = $this->_getTickImageLink();
        } else {
            $image = $this->_getUnTickImageLink();
        }

        return Mage::helper('emvcore')->__(
            '<span class="icon-status">%s</span> %s',
            $image,
            $phpCheck . ' ' . $extensionCheck
        );
    }

    /**
     * Check and get Cron status
     *
     * @return string
     */
    public function getCronStatus()
    {
        // get the last job with status pending or running
        $lastJob = Mage::getModel('cron/schedule')->getCollection()
            ->addFieldToFilter('status', array(
                    'in' => array(
                        Mage_Cron_Model_Schedule::STATUS_PENDING,
                        Mage_Cron_Model_Schedule::STATUS_RUNNING,
                    )
                )
            )
            ->setOrder('scheduled_at', Varien_Data_Collection_Db::SORT_ORDER_DESC)
            ->setPageSize(1)
            ->getFirstItem();

        // check if last job has been scheduled within allowed interval
        $ok = true;
        if ($lastJob && $lastJob->getId()) {
            $locale =  Mage::app()->getLocale();

            $scheduledAt = $locale->date($lastJob->getScheduledAt(), Varien_Date::DATETIME_INTERNAL_FORMAT);
            $scheduledAt = $locale->utcDate(null, $scheduledAt);

            // now
            $now = $locale->date(null, Varien_Date::DATETIME_INTERNAL_FORMAT);
            $now = $locale->utcDate(null, $now);

            // if last job was scheduled before the current time
            if ($now->isLater($scheduledAt)) {
                $allowedTime = $now->subHour($this->_allowedTimeDiffJob);
                if ($allowedTime->isLater($scheduledAt)) {
                    $ok = false;
                }
            }
        } else {
            $ok = false;
        }

        $message = '';
        if ($ok) {
            $image = $this->_getTickImageLink();
            $message = Mage::helper('emvcore')->__(
                '<span class="icon-status">%s</span> Magento Cron Process has been correctly configured!',
                $image
            );
        } else {
            $image = $this->_getUnTickImageLink();
            $message = Mage::helper('emvcore')->__(
                '<span class="icon-status">%s</span> Magento Cron Process has not been correctly activated!',
                $image
            );
        }

        return $message;
    }

    /**
     * Check and get memory status
     *
     * @return string
     */
    public function getMemoryStatus()
    {
        $memoryLimit = trim(strtoupper(ini_get('memory_limit')));

        $ok = true;
        if ($memoryLimit != -1) {
            $memoryLimitInBytes = $memoryLimit;
            if (substr($memoryLimit, -1) == 'K') {
                $memoryLimitInBytes = substr($memoryLimit, 0, -1) * 1024;
            }
            if (substr($memoryLimit, -1) == 'M') {
                $memoryLimitInBytes = substr($memoryLimit, 0, -1) * 1024 * 1024;
            }
            if (substr($memoryLimit, -1) == 'G') {
                $memoryLimitInBytes = substr($memoryLimit, 0, -1) * 1024 * 1024 * 1024;
            }

            $allowedLimit = $this->_minMemory * 1024 * 1024;
            if ($memoryLimitInBytes < $allowedLimit) {
                $ok = false;
            }
        }

        $message = '';
        if ($ok) {
            $image = $this->_getTickImageLink();
            $message = Mage::helper('emvcore')->__(
                '<span class="icon-status">%s</span> memory_limit is set to <strong>%s</strong>.',
                $image,
                $memoryLimit
            );
        } else {
            $image = $this->_getWarningTickImageLink();
            $message = Mage::helper('emvcore')->__(
                '<span class="icon-status">%s</span> memory_limit should be set to at least <strong>%s M</strong> (currently %s).',
                $image,
                $this->_minMemory,
                $memoryLimit
            );
        }
        return $message;
    }

}
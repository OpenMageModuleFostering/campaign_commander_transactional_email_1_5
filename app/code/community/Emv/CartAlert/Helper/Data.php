<?php
/**
 * Cart alert helper
 *
 * @category    Emv
 * @package     Emv_CartAlert
 * @copyright   Copyright (c) 2013 SmartFocus (http://www.smartfocus.com)
 */
class Emv_CartAlert_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * @var Image size
     */
    const IMAGE_SIZE = 265;

    /**
     * XML PATH to get image size
     */
    const XML_PATH_IMAGE_SIZE = 'abandonment/general/image_size';

    /**
     * @var array
     */
    protected $_loadedProducts = array();

    /**
     * @var array
     */
    protected $_sendingModeForTemplate = array();

    /**
     * Add dash after every x characters
     */
    const DASH_EVERY_X_CHARACTERS = 0;

    /**
     * Coupon length without prefix and suffix
     */
    const COUPON_LENTH = 6;
    const XML_PATH_SHOPING_CART_RULE_ID = 'abandonment/general/cart_rule';

    /**
     * Shopping cart rules
     *
     * @var array
     */
    protected $_rules = array();

    /**
     * Detect whether the quote is associated with a registered customer
     *
     * @param Mage_Sales_Model_Quote $quote Quote
     * @return bool TRUE is quote associated with a registered customer, FALSE otherwise
     */
    protected function _isFromRegisteredCustomer(Mage_Sales_Model_Quote $quote)
    {
        return !is_null($quote->getCustomerId());
    }

    /**
     * Get the link used to unsubscribe from the reminders.
     * The quote is used to determine whether the link is for a registered customer or a guest.
     *
     * @param Mage_Sales_Model_Quote $quote Quote
     * @return string the unsubscribe link
     */
    public function getUnsubscribeLink(Mage_Sales_Model_Quote $quote)
    {
        if ($this->_isFromRegisteredCustomer($quote)) {
            return Mage::getUrl('abandonment/customer/unsubscribe');
        } else {
            $hash = Mage::helper('core')->getHash($quote->getCustomerEmail());
            return Mage::getUrl('abandonment/guest/unsubscribe', array('quote' => $quote->getId(), 'key' => $hash));
        }
    }

    /**
     * Get the link used to view the cart.
     * The quote is used to determine whether the link is for a registered customer or a guest.
     *
     * @param Mage_Sales_Model_Quote $quote Quote
     * @param string $reminderFlag
     * @return string the view cart/checkout link
     */
    public function getCartLink(Mage_Sales_Model_Quote $quote, $reminderFlag)
    {
        $reminderId = $this->getReminderIdFromFlag($reminderFlag);

        if ($this->_isFromRegisteredCustomer($quote)) {
            return Mage::getUrl('abandonment/customer/cart', array('reminder' => $reminderId));
        } else {
            $hash = Mage::helper('core')->getHash($quote->getCustomerEmail());
            return Mage::getUrl(
                'abandonment/guest/cart',
                array('quote' => $quote->getId(), 'key' => $hash, 'reminder' => $reminderId)
            );
        }
    }

    /**
     * Get the link used to view the store homepage.
     * The quote is used to determine whether the link is for a registered customer or a guest.
     *
     * @param Mage_Sales_Model_Quote $quote Quote
     * @return string the view store link
     */
    public function getStoreLink(Mage_Sales_Model_Quote $quote, $reminderFlag)
    {
        $reminderId = $this->getReminderIdFromFlag($reminderFlag);

        if ($this->_isFromRegisteredCustomer($quote)) {
            return Mage::getUrl('abandonment/customer/store', array('reminder' => $reminderId));
        } else {
            $hash = Mage::helper('core')->getHash($quote->getCustomerEmail());
            return Mage::getUrl(
                    'abandonment/guest/store',
                    array('quote' => $quote->getId(), 'key' => $hash, 'reminder' => $reminderId)
                );
        }
    }

    /**
     * Get the link used to view the a product page.
     * The quote is used to determine whether the link is for a    registered customer or a guest.
     *
     * @param Mage_Sales_Model_Quote $quote Quote
     * @return string the view product link
     */
    public function getItemLink(Mage_Sales_Model_Quote $quote, $reminderFlag, $productId)
    {
        $reminderId = $this->getReminderIdFromFlag($reminderFlag);

        if ($this->_isFromRegisteredCustomer($quote)) {
            return Mage::getUrl('abandonment/customer/product',
                array(
                    'reminder'     => $reminderId,
                    'productid'    => $productId
                )
             );
        } else {
            $hash = Mage::helper('core')->getHash($quote->getCustomerEmail());
            return Mage::getUrl('abandonment/guest/product',
                    array(
                        'quote'       => $quote->getId(),
                        'key'         => $hash,
                        'reminder'    => $reminderId,
                        'productid'   => $productId,
                    )
                );
        }
    }

    /**
     * Retrieves an image URL for the product, if it exists.
     * The priority is: thumbnail -> small image -> image -> Magento's placeholder.
     *
     * @param $productId
     * @return string the image's URL
     */
    public function getProductImageUrl ($productId)
    {
        $product = $this->getProduct($productId);

        $imageSize = Mage::getStoreConfig(self::XML_PATH_IMAGE_SIZE);
        if (!$imageSize) {
            $imageSize = self::IMAGE_SIZE;
        }

        if (($product->getThumbnail() != 'no_selection')) {
            return Mage::helper('catalog/image')->init($product, 'thumbnail')
                ->resize($imageSize)
                ->__toString();
        } elseif (($product->getSmallImage() != 'no_selection')) {
            return Mage::helper('catalog/image')->init($product, 'small_image')
                ->resize($imageSize)
                ->__toString();
        } else {
            return Mage::helper('catalog/image')->init($product, 'image')
                ->resize($imageSize)
                ->__toString();
        }
    }

    /**
     * Get product object
     * @param string $productId
     * @return multitype:
     */
    public function getProduct($productId)
    {
        if (!isset($this->_loadedProducts[$productId])) {
            $this->_loadedProducts[$productId] = Mage::getModel('catalog/product')->load($productId);
        }

        return $this->_loadedProducts[$productId];
    }

    /**
     * @param string $templateName
     * @param string $storeId
     * @return NULL
     */
    public function getTemplateSendMode($templateName, $storeId)
    {
        $key = $templateName . $storeId;
        if (!isset($this->_sendingModeForTemplate[$key])) {
            $mode = null;

            $accountId = Mage::getStoreConfig(Emv_Emt_Model_Mage_Core_Email_Template::XML_PATH_CONFIG_ACCOUNT, $storeId);
            if ($accountId) {
                $emvemt = Mage::helper('emvemt/emvtemplate')->getEmvEmt($templateName, $accountId);
                $mode = $emvemt->getData('emv_send_mail_mode_id');
            }
            $this->_sendingModeForTemplate[$key] = $mode;
        }

        return $this->_sendingModeForTemplate[$key];
    }

    /**
     * Send a reminder
     *
     * @param Mage_Sales_Model_Quote $abandonedCart
     * @param $storeId
     * @param Emv_CartAlert_Model_Abandonment $abandonment
     * @param Mage_Core_Model_Email_Template
     */
    public function sendReminder(
        Mage_Sales_Model_Quote $abandonedCart,
        $storeId,
        Emv_CartAlert_Model_Abandonment $abandonment
    ) {
        // determine which template should be used
        $reminderId = false;
        $templatePath = false;
        switch ($abandonedCart->getReminderTemplate()) {
            case Emv_CartAlert_Constants::FIRST_ALERT_FLAG :
                $reminderId = $this->getReminderIdFromFlag(Emv_CartAlert_Constants::FIRST_ALERT_FLAG);
                $templatePath = Emv_CartAlert_Constants::XML_PATH_FIRST_ALERT_TEMPLATE;
                break;
            case Emv_CartAlert_Constants::SECOND_ALERT_FLAG :
                $reminderId = $this->getReminderIdFromFlag(Emv_CartAlert_Constants::SECOND_ALERT_FLAG);
                $templatePath = Emv_CartAlert_Constants::XML_PATH_SECOND_ALERT_TEMPLATE;
                break;
            case Emv_CartAlert_Constants::THIRD_ALERT_FLAG :
                $reminderId = $this->getReminderIdFromFlag(Emv_CartAlert_Constants::THIRD_ALERT_FLAG);
                $templatePath = Emv_CartAlert_Constants::XML_PATH_THIRD_ALERT_TEMPLATE;
                break;
        }

        if ($templatePath) {
            // get template name
            $templateName = Mage::getStoreConfig($templatePath, $storeId);

            $mode = $this->getTemplateSendMode($templateName, $storeId);

            /* @var $template Emv_Emt_Model_Mage_Core_Email_Template */
            $template = Mage::getModel('core/email_template');
            // Force emv send mode
            if ($mode == null || $mode == Emv_Emt_Model_Mailmode::CLASSIC_MODE) {
                $template->setEmvSend(true);
            }

            // set design config to frontend
            $template->setDesignConfig(array('area' => 'frontend', 'store' => $storeId));

            $template->sendTransactional(
                $templateName,
                Mage::getStoreConfig(Emv_CartAlert_Constants::XML_PATH_ALERT_SENDER_IDENTITY, $storeId),
                $abandonedCart->getCustomerEmail(),
                $abandonedCart->getPreparedCustomerName(),
                Array('cart' => $abandonedCart, 'promo_code' => $abandonment->getCouponCode()),
                $storeId
            );

            // update reminder statistic
            $this->updateStats($reminderId, $abandonedCart->getId(), $storeId);
        }
    }

    /**
     * Update statistic information about the reminder sending
     * @param int $reminderId
     * @param int $quoteId
     * @param int $storeId
     */
    public function updateStats($reminderId, $quoteId, $storeId)
    {
        $stats = Mage::getModel('abandonment/stats');
        $stats->setQuoteId($quoteId);
        $stats->setSendDate(Zend_Date::now()->toString(Varien_Date::DATETIME_INTERNAL_FORMAT));
        $stats->setReminderId($reminderId);
        $stats->save();
    }

    /**
     * @param int $reminderId
     * @return string
     */
    public function getReminderFlagFromId($reminderId)
    {
        $reminderFlag = null;

        switch ($reminderId) {
            case Emv_CartAlert_Constants::SECOND_ALERT_REMINDER_ID:
                $reminderFlag = Emv_CartAlert_Constants::SECOND_ALERT_FLAG;
                break;

            case Emv_CartAlert_Constants::THIRD_ALERT_REMINDER_ID:
                $reminderFlag = Emv_CartAlert_Constants::THIRD_ALERT_FLAG;
                break;

            case Emv_CartAlert_Constants::FIRST_ALERT_REMINDER_ID:
            default:
                $reminderFlag = Emv_CartAlert_Constants::FIRST_ALERT_FLAG;
                break;
        }

        return $reminderFlag;
    }

    /**
     * @param string $reminderFlag
     * @return int
     */
    public function getReminderIdFromFlag($reminderFlag)
    {
        $reminderId = null;
        switch ($reminderFlag) {
            case Emv_CartAlert_Constants::SECOND_ALERT_FLAG:
                $reminderId = Emv_CartAlert_Constants::SECOND_ALERT_REMINDER_ID;
                break;

            case Emv_CartAlert_Constants::THIRD_ALERT_FLAG:
                $reminderId = Emv_CartAlert_Constants::THIRD_ALERT_REMINDER_ID;
                break;

            case Emv_CartAlert_Constants::FIRST_ALERT_FLAG:
            default:
                $reminderId = Emv_CartAlert_Constants::FIRST_ALERT_REMINDER_ID;
                break;
        }

        return $reminderId;
    }

    /**
     * Get shopping cart price rule for a given id.
     * If id is null, get from store config
     *
     * @param string $id
     * @param string $storeId
     * @return Mage_SalesRule_Model_Rule
     */
    public function getShoppingCartPriceRule($id = null, $storeId = null)
    {
        if ($id == null) {
            $id = Mage::getStoreConfig(self::XML_PATH_SHOPING_CART_RULE_ID, $storeId);
        }

        $key = 'rule_' . $id;
        if (!isset($this->_rules[$key])) {
            $rule = Mage::getModel('salesrule/rule');
            if ($id) {
                // Get the rule you want to make a code from (this should be made in admin panel)
                $rule->load($id); // ID of coupon
            }
            $this->_rules[$key] = $rule;
        }

        return $this->_rules[$key];
    }

    /**
     * Get promo code according to a given shopping cart price rule
     *
     * @param string $id
     * @param string $storeId
     * @return boolean | string
     */
    public function getCouponCode($id = null, $storeId = null)
    {
        $rule = $this->getShoppingCartPriceRule($id, $storeId);

        $code = false;
        if ($rule->getId()) {
            if (
                $rule->getCouponType() == Mage_SalesRule_Model_Rule::COUPON_TYPE_SPECIFIC
                && !$rule->getUseAutoGeneration()
            ) {
                $code = $rule->getCouponCode();
            } else {
                // Define the coupon generator model instance
                // Look at Mage_SalesRule_Model_Coupon_Massgenerator for options
                $generator = Mage::getModel('salesrule/coupon_massgenerator');

                 //This sets custom Prefix-GeneratedValues-Suffix
                $parameters = array(
                    'format'                  => Mage::getStoreConfig(Emv_CartAlert_Constants::XML_PATH_COUPON_CODE_FORMAT),
                    'dash_every_x_characters' => Mage::getStoreConfig(Emv_CartAlert_Constants::XML_PATH_COUPON_CODE_DASH),
                    'prefix'                  => Mage::getStoreConfig(Emv_CartAlert_Constants::XML_PATH_COUPON_CODE_PREFIX),
                    'suffix'                  => Mage::getStoreConfig(Emv_CartAlert_Constants::XML_PATH_COUPON_CODE_SUFFIX),
                    'length'                  => Mage::getStoreConfig(Emv_CartAlert_Constants::XML_PATH_COUPON_CODE_LENGTH)
                );

                // Fallback if no params
                if (!empty($parameters['format'])){
                   $generator->setFormat(strtolower($parameters['format']));
                }

                $generator->setDash(!empty($parameters['dash_every_x_characters'])
                    ? (int) $parameters['dash_every_x_characters'] : self::DASH_EVERY_X_CHARACTERS
                );
                $generator->setLength(!empty($parameters['length'])? (int) $parameters['length'] : self::COUPON_LENTH);
                $generator->setPrefix(!empty($parameters['prefix'])? $parameters['prefix'] : '');
                $generator->setSuffix(!empty($parameters['suffix'])? $parameters['suffix'] : '');

                // Set the generator, and coupon type so it's able to generate in Mage
                $rule->setCouponCodeGenerator($generator);
                $coupon = $rule->acquireCoupon();
                if ($coupon) {
                    $type = 0;
                    if (
                        $rule->getCouponType() == Mage_SalesRule_Model_Rule::COUPON_TYPE_SPECIFIC
                        && $rule->getUseAutoGeneration()
                    ) {
                        $type = Mage_SalesRule_Helper_Coupon::COUPON_TYPE_SPECIFIC_AUTOGENERATED;
                    }

                    $coupon
                       ->setType($type)
                       ->save();

                    //This will return something like this Email-ZC6V-ZJWD-Vision if you have configured suffix/prefix parameters
                    $code = $coupon->getCode();
                }
            }
        }

        return $code;
    }

    /**
     * Support functions - Used for debugging purposes
     */

    /**
     * Delete all records from quote table (resource)
     */
    private static function deleteQuoteData() {

        // @var $quotes Mage_Sales_Model_Mysql4_Quote_Collection
        $quotes = Mage::getModel('sales/quote')->getCollection();
        $quotes
            ->addFieldToFilter('items_count', array('gt' => 0))
            ->addFieldToFilter('customer_email', array('notnull' => 1))
            ->addFieldToFilter('is_active', 1)
            ->setOrder('updated_at')
            ->getSelect()->joinLeft(
                    array('a' => $quotes->getTable('abandonment/abandonment')),
                    'main_table.entity_id = a.entity_id',
                    array('template')
                )
            ->where('a.customer_abandonment_subscribed = ? OR a.customer_abandonment_subscribed IS NULL', 1)
            ->where('a.template <> ? OR a.template IS NULL', Emv_CartAlert_Constants::OUTDATED_FLAG)
            ->where('a.template <> ? OR a.template IS NULL', Emv_CartAlert_Constants::THIRD_ALERT_FLAG);

        // @var $quote Mage_Sales_Model_Quote
        foreach ($quotes as $quote) {
            $quote->delete();
        }
    }

    /**
     * Delete records from quote table (resource) for a particular email address
     */
    private static function deleteQuoteDataFor($email) {

        // @var $quotes Mage_Sales_Model_Mysql4_Quote_Collection
        $quotes = Mage::getModel('sales/quote')->getCollection();
        $quotes
            ->addFieldToFilter('items_count', array('gt' => 0))
            ->addFieldToFilter('customer_email', array('eq' => $email))
            ->addFieldToFilter('is_active', 1)
            ->setOrder('updated_at')
            ->getSelect()->joinLeft(
                    array('a' => $quotes->getTable('abandonment/abandonment')),
                    'main_table.entity_id = a.entity_id',
                    array('template')
                )
            ->where('a.customer_abandonment_subscribed = ? OR a.customer_abandonment_subscribed IS NULL', 1)
            ->where('a.template <> ? OR a.template IS NULL', Emv_CartAlert_Constants::OUTDATED_FLAG)
            ->where('a.template <> ? OR a.template IS NULL', Emv_CartAlert_Constants::THIRD_ALERT_FLAG);

        // @var $quote Mage_Sales_Model_Quote
        foreach ($quotes as $quote) {
            $quote->delete();
        }
    }

    /**
     * Delete all records from abandonment table (resource)
     */
    private static function deleteAbandonmentData() {
        $acs = Mage::getModel('abandonment/abandonment');
        $ac  = $acs->getCollection()->load();

        foreach($ac as $c) {
            $c->delete();
        }
    }

    /**
     * Print data in all rows from abandonment table (resource)
     */
    private static function printAbandonmentData() {
        $acs = Mage::getModel('abandonment/abandonment');
        $ac  = $acs->getCollection()
           ->load();

        foreach($ac as $c) {
            print_r($c->getData());
        }
    }
}

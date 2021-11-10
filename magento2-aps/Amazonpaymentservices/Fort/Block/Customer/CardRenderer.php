<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Amazonpaymentservices\Fort\Block\Customer;

use Amazonpaymentservices\Fort\Model\Ui\ConfigProvider;
use Magento\Framework\View\Element\Template;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Block\AbstractCardRenderer;

/**
 * @api
 * @since 100.1.0
 * in favor of official payment integration available on the marketplace
 */
class CardRenderer extends AbstractCardRenderer
{

    protected $_paymentMethodCode;
    /**
     * Can render specified token
     *
     * @param PaymentTokenInterface $token
     * @return boolean
     * @since 100.1.0
     */
    public function canRender(PaymentTokenInterface $token)
    {
        $this->_paymentMethodCode = $token->getPaymentMethodCode();
        return $token->getPaymentMethodCode() === ConfigProvider::CODE;
    }

    /**
     * @return string
     * @since 100.1.0
     */
    public function getNumberLast4Digits()
    {
        return $this->getTokenDetails()['maskedCC'];
    }

    /**
     * @return string
     * @since 100.1.0
     */
    public function getExpDate()
    {
        return $this->getTokenDetails()['expirationDate'];
    }

    /**
     * @return string
     * @since 100.1.0
     */
    public function getIconUrl()
    {
        $type = $this->getTokenDetails()['type'];
        
        return  $type;
    }

    /**
     * @return string
     * @since 100.1.0
     */
    public function getTokenType()
    {
        return $this->_paymentMethodCode;
    }

    /**
     * @return int
     * @since 100.1.0
     */
    public function getIconHeight()
    {
        return $this->getIconForType($this->getTokenDetails()['type'])['height'];
    }

    /**
     * @return int
     * @since 100.1.0
     */
    public function getIconWidth()
    {
        return $this->getIconForType($this->getTokenDetails()['type'])['width'];
    }

    /**
     * @return int
     * @since 100.1.0
     */
    public function getDeleteUrl()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $_helper = $objectManager->get('\Amazonpaymentservices\Fort\Helper\Data');

        return $_helper->getReturnUrl('amazonpaymentservicesfort/vault/vaultdelete');
    }
}

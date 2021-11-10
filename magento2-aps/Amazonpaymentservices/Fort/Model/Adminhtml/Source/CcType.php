<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Amazonpaymentservices\Fort\Model\Adminhtml\Source;

/**
 * Class CcType
 * @codeCoverageIgnore
 * in favor of official payment integration available on the marketplace
 */
class CcType extends \Magento\Payment\Model\Source\Cctype
{
    /**
     * List of specific credit card types
     * @var array
     */
    private $specificCardTypesList = [
    ];

    /**
     * Allowed credit card types
     *
     * @return string[]
     */
    public function getAllowedTypes()
    {
        return ['VI', 'MC', 'AE', 'MZ', 'MD'];
    }

    /**
     * Returns credit cards types
     *
     * @return array
     */
    public function getCcTypeLabelMap()
    {
        return array_merge($this->specificCardTypesList, $this->_paymentConfig->getCcTypes());
    }

    /**
     * @inheritdoc
     */
    public function toOptionArray()
    {
        $allowed = $this->getAllowedTypes();
        $options = [];

        foreach ($this->getCcTypeLabelMap() as $code => $name) {
            if (in_array($code, $allowed)) {
                $options[] = ['value' => $code, 'label' => $name];
            }
        }

        return $options;
    }
}

<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Amazonpaymentservices\Fort\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Amazonpaymentservices\Fort\Gateway\Http\Client\ClientMock;

/**
 * Get config
 */
class ConfigProvider implements ConfigProviderInterface
{
    const CODE = 'aps_fort';

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {
        return [
            'payment' => [
                self::CODE => [
                    'transactionResults' => [
                        ClientMock::SUCCESS => __('Success'),
                        ClientMock::FAILURE => __('Fraud')
                    ]
                ]
            ]
        ];
    }
}

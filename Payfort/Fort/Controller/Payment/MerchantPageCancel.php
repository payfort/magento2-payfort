<?php

// check magento version to include Appropriate class
if (interface_exists("Magento\Framework\App\CsrfAwareActionInterface"))
    include __DIR__ . "/v2.3/MerchantPageCancel.php";
else
    include __DIR__ . "/v2/MerchantPageCancel.php";
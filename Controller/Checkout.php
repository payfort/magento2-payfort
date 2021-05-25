<?php

// check magento version to include Appropriate class
if (interface_exists("Magento\Framework\App\CsrfAwareActionInterface"))
    include __DIR__ . "/Payment/v2.3/Checkout.php";
else
    include __DIR__ . "/Payment/v2/Checkout.php";
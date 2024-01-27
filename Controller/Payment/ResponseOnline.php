<?php

// check magento version to include Appropriate class
if (interface_exists("Magento\Framework\App\CsrfAwareActionInterface"))
    include __DIR__ . "/v2.3/ResponseOnline.php";
else
    include __DIR__ . "/v2/ResponseOnline.php";

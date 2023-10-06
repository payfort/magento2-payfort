<?php
namespace Amazonpaymentservices\Fort\Plugin;


use Magento\Framework\Stdlib\Cookie\PhpCookieManager;
use Magento\Framework\Stdlib\Cookie\PublicCookieMetadata;

class SwitchSameSite
{
    const AFFECTED_KEYS = 'PHPSESSID,form_key,private_content_version,X-Magento-Vary';
    private $affectedKeys = [];

    /**
     * @param PhpCookieManager $subject
     * @param string $name
     * @param string $value
     * @param PublicCookieMetadata|null $metadata
     * @return array
     */
    public function beforeSetPublicCookie(
        PhpCookieManager $subject,
                         $name,
                         $value,
        PublicCookieMetadata $metadata = null
    ) {
        if ($this->isAffectedKeys($name)) {
            $metadata->setSecure(true);
            $metadata->setSameSite('None');
        }

        return [$name, $value, $metadata];
    }

    private function isAffectedKeys($name)
    {
        if (!count($this->affectedKeys)) {
            $affectedKeys = self::AFFECTED_KEYS;
            $this->affectedKeys = explode(',', strtolower($affectedKeys));
        }

        return in_array(strtolower($name), $this->affectedKeys);
    }
}

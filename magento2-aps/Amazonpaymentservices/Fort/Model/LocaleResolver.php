<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Amazonpaymentservices\Fort\Model;

use Magento\Framework\Locale\ResolverInterface;

/**
 * Resolves locale for Amazonpaymentservices.
 *
 */
class LocaleResolver implements ResolverInterface
{
    /**
     * @var ResolverInterface
     */
    private $resolver;

    /**
     * Mapping Magento locales on Amazonpaymentservices locales.
     *
     * @var array
     */
    private $localeMap = [
        'zh_Hans_CN' => 'zh_CN',
        'zh_Hant_HK' => 'zh_HK',
        'zh_Hant_TW' => 'zh_TW'
    ];

    /**
     * @param ResolverInterface $resolver
     */
    public function __construct(ResolverInterface $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     * @inheritdoc
     */
    public function getDefaultLocalePath()
    {
        return $this->resolver->getDefaultLocalePath();
    }

    /**
     * @inheritdoc
     */
    public function setDefaultLocale($locale)
    {
        return $this->resolver->setDefaultLocale($locale);
    }

    /**
     * @inheritdoc
     */
    public function getDefaultLocale()
    {
        return $this->resolver->getDefaultLocale();
    }

    /**
     * @inheritdoc
     */
    public function setLocale($locale = null)
    {
        return $this->resolver->setLocale($locale);
    }

    /**
     * @inheritdoc
     */
    public function getLocale()
    {
        return $this->resolver->getLocale();
    }

    /**
     * @inheritdoc
     */
    public function emulate($scopeId)
    {
        return $this->resolver->emulate($scopeId);
    }

    /**
     * @inheritdoc
     */
    public function revert()
    {
        return $this->resolver->revert();
    }
}

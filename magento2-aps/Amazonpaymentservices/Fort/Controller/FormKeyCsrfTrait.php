<?php
/**
 * Form key based CSRF validation for user-initiated controllers
 *
 * @category Aps
 * @package  Aps_Fort
 * @license  GNU / GPL v3
 **/
namespace Amazonpaymentservices\Fort\Controller;

use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;

/**
 * Validates Magento's form key on controllers called by the storefront.
 *
 * Controllers using this trait must be called only from the plugin's own
 * JavaScript, which sends `form_key` with every request.
 */
trait FormKeyCsrfTrait
{
    /**
     * @var \Magento\Framework\Data\Form\FormKey\Validator|null
     */
    protected $apsFormKeyValidator;

    /**
     * Resolve the validator, falling back to the object manager when the
     * consuming controller has not injected one.
     *
     * @return \Magento\Framework\Data\Form\FormKey\Validator
     */
    private function getApsFormKeyValidator()
    {
        if ($this->apsFormKeyValidator === null) {
            $this->apsFormKeyValidator = \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Magento\Framework\Data\Form\FormKey\Validator::class);
        }

        return $this->apsFormKeyValidator;
    }

    /**
     * Return a JSON 403 so the calling AJAX sees a clear failure.
     *
     * @param RequestInterface $request
     * @return InvalidRequestException|null
     */
    public function createCsrfValidationException(
        RequestInterface $request
    ): ?InvalidRequestException {
        $result = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_JSON);
        $result->setHttpResponseCode(403);
        $result->setData([
            'success' => false,
            'error_message' => (string)__('Invalid form key. Please refresh the page and try again.'),
        ]);

        return new InvalidRequestException($result);
    }

    /**
     * @param RequestInterface $request
     * @return bool|null
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return $this->getApsFormKeyValidator()->validate($request);
    }
}

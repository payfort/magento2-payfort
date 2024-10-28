<?php

namespace Amazonpaymentservices\Fort\Controller\Payment;

use Amazonpaymentservices\Fort\Helper\Data;
use Amazonpaymentservices\Fort\Model\Payment;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Sales\Model\Order;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\Filesystem\DirectoryList as FileSystem;
use Magento\Sales\Model\Order\Config;
use Magento\Vault\Model\ResourceModel\PaymentToken;

class GetInstallmentPlans extends \Magento\Framework\App\Action\Action implements CsrfAwareActionInterface, HttpGetActionInterface, HttpPostActionInterface
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var \Magento\Sales\Model\Order\Config
     */
    protected $_orderConfig;

    /**
     *
     * @var \Amazonpaymentservices\Fort\Model\Payment
     */
    protected $_apsModel;

    /**
     * Helper
     *
     * @var \Amazonpaymentservices\Fort\Helper\Data
     */
    protected $_helper;

    /**
     * @var
     */
    protected $_resultJsonFactory;

    /**
     * @var File System
     */
    protected $_filesystem;

    /**
     * @var \Magento\Vault\Model\ResourceModel\PaymentToken
     */
    protected $_paymentToken;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @param Context $context ,
     * @param Session $checkoutSession ,
     * @param Config $orderConfig ,
     * @param Payment $apsModel ,
     * @param Data $helperFort
     * @param JsonFactory $resultJsonFactory
     * @param FileSystem $fileSystem
     * @param PaymentToken $paymentToken
     * @param \Magento\Customer\Model\Session $customerSession
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\Order\Config $orderConfig,
        \Amazonpaymentservices\Fort\Model\Payment $apsModel,
        \Amazonpaymentservices\Fort\Helper\Data $helperFort,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        FileSystem $fileSystem,
        \Magento\Vault\Model\ResourceModel\PaymentToken $paymentToken,
        \Magento\Customer\Model\Session $customerSession
    ) {
        parent::__construct($context);
        $this->_checkoutSession = $checkoutSession;
        $this->_orderConfig = $orderConfig;
        $this->_helper = $helperFort;
        $this->_apsModel = $apsModel;
        $this->_resultJsonFactory  = $resultJsonFactory;
        $this->_filesystem = $fileSystem;
        $this->_paymentToken = $paymentToken;
        $this->_customerSession = $customerSession;
    }
    
    public function createCsrfValidationException(
        RequestInterface $request
    ): ?InvalidRequestException {
            return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
    
    public function execute()
    {
        $responseParams = $this->getRequest()->getParams();
        //$arrPaymentPageData = $this->_helper->getInstallmentPlan();
        $arrPaymentPageData = [];
        $installmentData = [];
        $cardNumberOrToken = '';

        if (!empty($responseParams['cardNumber'])) {
            $cardNumberOrToken = $responseParams['cardNumber'];
            $arrPaymentPageData = $this->_helper->getInstallmentPlan($cardNumberOrToken,\Amazonpaymentservices\Fort\Helper\Data::INSTALLMENTS_PLAN_CARD);
        } elseif (!empty($responseParams['vaultSelected'])) {
            $customerId = $this->_customerSession->getCustomer()->getId();
            $tokenData = $this->_paymentToken->getByPublicHash($responseParams['vaultSelected'], $customerId);
            $tokenName = '';
            if (!empty($tokenData)) {
                $details = json_decode($tokenData['details'], 1);
                $tokenName = $tokenData['gateway_token'];
                $this->_helper->log("Token details");
                $this->_helper->log(json_encode($details));
                $this->_helper->log("Token details 2");
                $this->_helper->log(json_encode($tokenName));
                //$cardNumberOrToken = substr($details['maskedCC'], 0, 6);
                $cardNumberOrToken = $tokenName;
                $arrPaymentPageData = $this->_helper->getInstallmentPlan($cardNumberOrToken,\Amazonpaymentservices\Fort\Helper\Data::INSTALLMENTS_PLAN_TOKEN);
            }
        } else{
            $arrPaymentPageData = $this->_helper->getInstallmentPlan();
        }
        $installmentData = $this->installmentData($arrPaymentPageData, $cardNumberOrToken, !empty($responseParams['cardNumber']));

        /*if (!empty($responseParams['cardNumber'])) {
            $cardNumber = $responseParams['cardNumber'];
            $installmentData = $this->installmentData($arrPaymentPageData, $responseParams['cardNumber']);
        } elseif (!empty($responseParams['vaultSelected'])) {
            $customerId = $this->_customerSession->getCustomer()->getId();
            $tokenData = $this->_paymentToken->getByPublicHash($responseParams['vaultSelected'], $customerId);
            $tokenName = '';
            if (!empty($tokenData)) {
                $details = json_decode($tokenData['details'], 1);
                $cardNumber = substr($details['maskedCC'], 0, 6);
                $installmentData = $this->installmentData($arrPaymentPageData, $cardNumber);
            }
        }*/
        $responseData = [];
        if (isset($installmentData['success']) && $installmentData['success'] === true) {
            $responseData = $this->getInstallmentHandler($installmentData);
        } else {
            $responseData = $installmentData;
        }
        $responseData['res'] = $arrPaymentPageData;
        $jsonResult = $this->_resultJsonFactory->create();
        $jsonResult->setData($responseData);
        return $jsonResult;
    }

    private function installmentData($data, $cardNumber, $isNotToken)
    {
        $result = [
            'success' => false,
            'error_message' => $data['response_message'],
            'response'  => ''
        ];
        if ($data['response_code'] == 62000) {
            $data['installment_detail']['issuer_detail'] = array_filter(
                $data['installment_detail']['issuer_detail'],
                function ($row) {
                    return !empty($row['plan_details']);
                }
            );
            if (empty($data['installment_detail']['issuer_detail'])) {
                return [
                    'success' => false,
                    'error_message' => __('No plans found'),
                    'response'  => ''
                ];
            }
            $result = [
                'success' => true,
                'error_message' => '',
                'response'  => ''
            ];
            if($isNotToken){
                $issuer_key = $this->findBinInPlans($cardNumber, $data['installment_detail']['issuer_detail']);
                if (empty($issuer_key) && !isset($data['installment_detail']['issuer_detail'][ $issuer_key ])) {
                    return [
                        'success' => false,
                        'error_message' => __('There is no installment plan available'),
                        'response'  => ''
                    ];
                }
                $result['installment_data'] = $data['installment_detail']['issuer_detail'][ $issuer_key ];    
            } else {
                $result['installment_data'] = $data['installment_detail']['issuer_detail'][ 0 ];
            }
            
        }
        
        return $result;
    }

    /**
     * Find bin in plans
     *
     * @return int|string|null int
     */
    private function findBinInPlans($cardNumber, $issuerData)
    {
        $issuerKey = null;
        if (!empty($issuerData)) {
            foreach ($issuerData as $key => $row) {
                $cardRegex = '';
                $cardBins  = array_column($row['bins'], 'bin');
                if (!empty($cardBins)) {
                    $cardRegex = '/^' . implode('|', $cardBins) . '/';
                    if (preg_match($cardRegex, $cardNumber)) {
                        $issuerKey = $key;
                        break;
                    }
                }
            }
        }
        return $issuerKey;
    }

    /**
     * Get Installment plans ajax handler
     */
    public function getInstallmentHandler($response)
    {
        if (true === $response['success'] && !empty($response['installment_data'])) {
            $all_plans      = $response['installment_data']['plan_details'];
            $banking_system = $response['installment_data']['banking_system'];
            $interest_text  = 'Non Islamic' === $banking_system ? __('Interest') : __('Profit Rate');
            $planArr     = [];
            $x = 0;
            if (!empty($all_plans)) {
                foreach ($all_plans as $key => $plan) {
                    $baseCurrency = $this->_helper->getBaseCurrency();
                    $currencyCurrency = $this->_helper->getFrontCurrency();
                    $currency = $this->_helper->getFortCurrency($baseCurrency, $currencyCurrency);
                    $interest      = $this->_helper->convertDecAmount((float)$plan['fee_display_value'], $currency);
                    $interest_info = $interest . ( 'Percentage' === $plan['fees_type'] ? '%' : '' ) . ' ' . $interest_text;
                    $planArr[$x]['interest_info'] = $interest_info;
                    $planArr[$x]['amountPerMonth'] = $plan['amountPerMonth'];
                    $planArr[$x]['plan_code'] = $plan['plan_code'];
                    $planArr[$x]['issuer_code'] = $response['installment_data']['issuer_code'];
                    $planArr[$x]['number_of_installment'] = $plan['number_of_installment'];
                    $planArr[$x]['currency_code'] =  $plan['currency_code'];
                    $planArr[$x]['interest'] = $interest;
                    $planArr[$x]['fee_type'] = $plan['fees_type'];
                    $x++;
                    
                }
            }
            $terms_url          = $response['installment_data'][ 'terms_and_condition_' . $this->_helper->getLanguage() ];
            $processing_content = $response['installment_data'][ 'processing_fees_message_' . $this->_helper->getLanguage() ];
            $issuer_text = '';
            if ($this->_helper->getConfig('payment/aps_installment/issuer_code') == '1') {
                $issuer_text        = $response['installment_data'][ 'issuer_name_' . $this->_helper->getLanguage() ];
            }
            $issuer_logo        = $response['installment_data'][ 'issuer_logo_' . $this->_helper->getLanguage() ];
            $terms_text = '';
            if ($this->_helper->getConfig('payment/aps_installment/bank_logo') == '1') {
                $terms_text     .= "<img src='".$issuer_logo."' height='20px'/>";
            }
            $terms_text        .= __('I agree with the installment <a target="_blank" href="'.$terms_url.'">terms and condition</a> to proceed with the transaction');
            $plan_info          = '<input type="checkbox" name="installment_term" id="installment_term" required/>' . $terms_text;
            $plan_info         .= '<label class="aps_installment_terms_error aps_error"></label>';
            $plan_info         .= '<p> ' . $processing_content . '</p>';
            $issuer_info = '';
            $dataArr['success']  = true;
            $dataArr['plansArr']      = $planArr;
            $dataArr['plan_info']       = $plan_info;
            $dataArr['issuer_text']     = $issuer_text;
            $dataArr['issuer_logo']     = $issuer_logo;
            $dataArr['terms_url']     = $terms_url;
            $dataArr['confirmation_en'] = $response['installment_data']['confirmation_message_en'];
            $dataArr['confirmation_ar'] = $response['installment_data']['confirmation_message_ar'];
        } else {
            $dataArr['success']  = false;
            $dataArr['error_message'] = $response['error_message'];
        }
        return $dataArr;
    }
    public function getCacheLifetime()
    {
        return null;
    }
}

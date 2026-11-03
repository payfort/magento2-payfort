<?php
/**
 * Installment DOM XSS: upstream installment data must be escaped/validated
 * before it is placed into the plan_info markup the storefront renders.
 *
 * The Escaper is an htmlspecialchars stand-in (the real one needs the object
 * manager); the test asserts every upstream value is routed through it and
 * that only absolute http(s) URLs are used.
 */
namespace {
    if (!function_exists('__')) {
        function __(...$args)
        {
            $text = array_shift($args);
            if (!empty($args) && is_array($args[0])) {
                $args = $args[0];
            }
            return new \Magento\Framework\Phrase($text, $args);
        }
    }
}

namespace Amazonpaymentservices\Fort\Test\Unit\Payment {

    use Amazonpaymentservices\Fort\Controller\Payment\GetInstallmentPlans;
    use Amazonpaymentservices\Fort\Helper\Data;
    use PHPUnit\Framework\TestCase;
    use ReflectionClass;

    class GetInstallmentPlansEscapingTest extends TestCase
    {
        private function controller(array $config): GetInstallmentPlans
        {
            $helper = $this->getMockBuilder(Data::class)->disableOriginalConstructor()
                ->onlyMethods(['getBaseCurrency', 'getFrontCurrency', 'getFortCurrency', 'convertDecAmount', 'getLanguage', 'getConfig'])
                ->getMock();
            $helper->method('getFortCurrency')->willReturn('EGP');
            $helper->method('convertDecAmount')->willReturnArgument(0);
            $helper->method('getLanguage')->willReturn('en');
            $helper->method('getConfig')->willReturnCallback(fn($path) => $config[$path] ?? '0');

            $escaper = $this->getMockBuilder(\Magento\Framework\Escaper::class)->disableOriginalConstructor()
                ->onlyMethods(['escapeHtml', 'escapeHtmlAttr'])->getMock();
            $escape = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $escaper->method('escapeHtml')->willReturnCallback($escape);
            $escaper->method('escapeHtmlAttr')->willReturnCallback($escape);

            $ref = new ReflectionClass(GetInstallmentPlans::class);
            $controller = $ref->newInstanceWithoutConstructor();
            foreach (['_helper' => $helper, 'escaper' => $escaper] as $name => $value) {
                $prop = $ref->getProperty($name);
                $prop->setAccessible(true);
                $prop->setValue($controller, $value);
            }
            return $controller;
        }

        private function response(string $terms, string $logo, string $processing): array
        {
            return [
                'success' => true,
                'installment_data' => [
                    'plan_details' => [[
                        'fee_display_value' => 1, 'fees_type' => 'Percentage', 'amountPerMonth' => '100',
                        'plan_code' => 'P1', 'number_of_installment' => '6', 'currency_code' => 'EGP',
                    ]],
                    'banking_system' => 'Non Islamic',
                    'issuer_code' => 'BANK',
                    'terms_and_condition_en' => $terms,
                    'processing_fees_message_en' => $processing,
                    'issuer_name_en' => 'Bank',
                    'issuer_logo_en' => $logo,
                    'confirmation_message_en' => '',
                    'confirmation_message_ar' => '',
                ],
            ];
        }

        public function testHostileUpstreamDataIsInert(): void
        {
            $result = $this->controller(['payment/aps_installment/bank_logo' => '1'])->getInstallmentHandler($this->response(
                'javascript:alert(1)',
                "javascript:alert(2)' onerror='alert(3)",
                '<img src=x onerror=alert(4)><script>alert(5)</script>'
            ));

            $this->assertTrue($result['success']);
            $html = $result['plan_info'];
            $this->assertStringNotContainsString('javascript:', $html);
            $this->assertStringNotContainsString('<script', $html);
            $this->assertStringNotContainsString('<img', $html, 'unsafe logo must be omitted, processing text escaped');
            $this->assertStringNotContainsString('href=', $html, 'no link for an unsafe terms URL');
            $this->assertStringContainsString('&lt;script&gt;alert(5)&lt;/script&gt;', $html);
            $this->assertStringContainsString('<input type="checkbox" name="installment_term" id="installment_term" required/>', $html);
            $this->assertSame('', $result['terms_url']);
            $this->assertSame('', $result['issuer_logo']);
        }

        public function testValidUrlsKeepOriginalMarkup(): void
        {
            $terms = 'https://bank.example/terms?lang=en&v=2';
            $logo = 'https://bank.example/logo.png';
            $result = $this->controller(['payment/aps_installment/bank_logo' => '1'])
                ->getInstallmentHandler($this->response($terms, $logo, 'Fees apply'));

            $html = $result['plan_info'];
            $this->assertStringContainsString("<img src='https://bank.example/logo.png' height='20px'/>", $html);
            $this->assertStringContainsString(
                'I agree with the installment <a target="_blank" href="https://bank.example/terms?lang=en&amp;v=2">terms and condition</a> to proceed with the transaction',
                $html
            );
            $this->assertStringContainsString('<p> Fees apply</p>', $html);
            $this->assertSame($terms, $result['terms_url']);
            $this->assertSame($logo, $result['issuer_logo']);
        }

        public function testRawArabicPathAndQueryAreAcceptedPercentEncoded(): void
        {
            $result = $this->controller([])->getInstallmentHandler(
                $this->response('https://bank.example/الشروط?lang=عربي', '', '')
            );
            $encoded = 'https://bank.example/%D8%A7%D9%84%D8%B4%D8%B1%D9%88%D8%B7?lang=%D8%B9%D8%B1%D8%A8%D9%8A';
            $this->assertSame($encoded, $result['terms_url']);
            $this->assertStringContainsString('href="' . $encoded . '"', $result['plan_info']);
        }

        /**
         * @dataProvider rejectedUrls
         */
        public function testUnsafeOrMalformedUrlsAreRejected(string $url): void
        {
            $result = $this->controller([])->getInstallmentHandler($this->response($url, '', ''));
            $this->assertSame('', $result['terms_url']);
            $this->assertStringNotContainsString('href=', $result['plan_info']);
        }

        public function rejectedUrls(): array
        {
            return [
                'javascript' => ['javascript:alert(1)'],
                'data' => ['data:text/html,<b>x</b>'],
                'newline' => ["https://bank.example/a\nb"],
                'non-ascii host' => ['https://بنك.example/terms'],
                'relative' => ['/terms'],
            ];
        }

        public function testLogoOmittedWhenBankLogoDisabled(): void
        {
            $result = $this->controller([])->getInstallmentHandler(
                $this->response('https://bank.example/t', 'https://bank.example/l.png', '')
            );
            $this->assertStringNotContainsString('<img', $result['plan_info']);
        }
    }
}

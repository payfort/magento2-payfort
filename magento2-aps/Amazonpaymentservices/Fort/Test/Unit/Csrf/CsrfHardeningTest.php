<?php
/**
 * Source-level regression tests for the browser CSRF hardening of local
 * state-changing controllers and their storefront callers.
 *
 * The checks read the module sources directly so they run without a Magento
 * bootstrap.
 */

namespace Amazonpaymentservices\Fort\Test\Unit\Csrf;

use PHPUnit\Framework\TestCase;

class CsrfHardeningTest extends TestCase
{
    private const MUTATORS = [
        'Controller/Payment/OrderCancel.php',
        'Controller/Payment/Redirect.php',
        'Controller/Payment/AppleValidateAddress.php',
        'Controller/Payment/GetAppleValidation.php',
        'Controller/Payment/SendAppleDataToAps.php',
        'Controller/Payment/SendAppleCartDataToAps.php',
        'Controller/Payment/VisaCheckout.php',
        'Controller/Product/AddProductToCart.php',
    ];

    private const REDIRECT_CALLERS = [
        'aps_visacheckout_redirect.js',
        'aps_benefit-method.js',
        'aps_omannet-method.js',
        'aps_knet-method.js',
        'aps_fort_sadad-method.js',
        'aps_installment-method.js',
        'aps_fort_naps-method.js',
        'aps_fort_stc.js',
        'aps_fort_tabby.js',
        'aps_fort_cc-method.js',
    ];

    private function read(string $relative): string
    {
        $content = file_get_contents(dirname(__DIR__, 3) . '/' . $relative);
        $this->assertIsString($content, $relative);

        return $content;
    }

    /**
     * @dataProvider mutatorProvider
     */
    public function testMutatorsUseFormKeyTraitAndPostOnly(string $file): void
    {
        $source = $this->read($file);

        $this->assertStringContainsString('FormKeyCsrfTrait;', $source);
        $this->assertStringContainsString('HttpPostActionInterface', $source);
        $this->assertStringNotContainsString('HttpGetActionInterface', $source);
        $this->assertDoesNotMatchRegularExpression(
            '/function\s+validateForCsrf\s*\(/',
            $source,
            'Controller must not override the trait with a bypass'
        );
        $this->assertDoesNotMatchRegularExpression('/function\s+createCsrfValidationException\s*\(/', $source);
    }

    public function mutatorProvider(): array
    {
        $cases = [];
        foreach (self::MUTATORS as $file) {
            $cases[$file] = [$file];
        }

        return $cases;
    }

    /**
     * Subscription cancel is a plain page form: POST-only lets Magento's default
     * validator enforce the form key (non-AJAX POST) with its normal redirect on failure.
     */
    public function testSubscriptionCancelIsPostOnlyWithoutBypass(): void
    {
        $source = $this->read('Controller/Subscription/Cancel.php');
        $this->assertStringContainsString('implements HttpPostActionInterface', $source);
        $this->assertStringNotContainsString('HttpGetActionInterface', $source);
        $this->assertStringNotContainsString('CsrfAwareActionInterface', $source);
        $this->assertDoesNotMatchRegularExpression('/function\s+validateForCsrf\s*\(/', $source);
    }

    public function testGatewayCallbacksStayUnkeyed(): void
    {
        foreach (['Response.php', 'ResponseOnline.php', 'Webhook.php'] as $callback) {
            $source = $this->read('Controller/Payment/' . $callback);
            $this->assertStringNotContainsString('FormKeyCsrfTrait', $source, $callback);
        }
    }

    public function testRedirectCallersUseKeyedPost(): void
    {
        foreach (self::REDIRECT_CALLERS as $file) {
            $source = $this->read('view/frontend/web/js/view/payment/method-renderer/' . $file);
            $this->assertStringNotContainsString('$.mage.redirect(', $source, $file);
            $this->assertStringContainsString('postRedirect(', $source, $file);
        }
    }

    public function testNapsKeepsQuoteIdAndRandomIdsAreKept(): void
    {
        $dir = 'view/frontend/web/js/view/payment/method-renderer/';
        $this->assertStringContainsString("'quoteId=' + encodeURIComponent(quoteId)", $this->read($dir . 'aps_fort_naps-method.js'));
        foreach (['aps_installment-method.js', 'aps_fort_stc.js', 'aps_fort_tabby.js', 'aps_fort_cc-method.js'] as $file) {
            $this->assertStringContainsString('"?id="+randomNum', $this->read($dir . $file), $file);
        }
    }

    public function testPostRedirectModuleSendsFormKeyViaPost(): void
    {
        $source = $this->read('view/frontend/web/js/action/post-redirect.js');
        $this->assertStringContainsString('method="POST"', $source);
        $this->assertStringContainsString("name: 'form_key'", $source);
    }

    public function testCancellationNavigationIsKeyedPost(): void
    {
        foreach (['redirect.phtml', 'merchant-page.phtml'] as $template) {
            $source = $this->read('view/frontend/templates/' . $template);
            $this->assertStringNotContainsString('window.location', $source, $template);
            $this->assertStringContainsString('post-redirect', $source, $template);
            // The gateway-bound form keeps its disabled dummy key so the live
            // storefront form key is never posted to APS.
            $this->assertStringContainsString("\$form_data['form_key'] = 'abcd';", $source, $template);
            $this->assertStringContainsString("input[name=form_key]').attr(\"disabled\", \"disabled\")", $source, $template);
        }
    }

    public function testSubscriptionCancelIsKeyedPostForm(): void
    {
        $source = $this->read('view/frontend/templates/subscription/list.phtml');
        $this->assertStringContainsString('<form method="post"', $source);
        $this->assertStringContainsString("getBlockHtml('formkey')", $source);
        $this->assertStringNotContainsString("<a href = '<?= \$block->escapeUrl(\$block->getCancelUrl", $source);
    }

    public function testAppleCallersSendFormKey(): void
    {
        foreach (['product_button.phtml', 'cart_button.phtml'] as $template) {
            $source = $this->read('view/frontend/templates/express/' . $template);
            $this->assertStringContainsString("'&form_key='", $source, $template);
            $this->assertStringContainsString("response.form_key", $source, $template);
        }
        $apple = $this->read('view/frontend/web/js/view/payment/method-renderer/aps_apple-method.js');
        $this->assertStringContainsString("'&form_key='", $apple);
        $this->assertStringContainsString('response.form_key', $apple);
    }
}

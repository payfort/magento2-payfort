<?php
/**
 * Unit tests for the debug-log redaction primitives on Helper\Data.
 *
 * These guard the single trust boundary that keeps sensitive payment data
 * (card numbers, OTPs, tokens, emails, IPs, phone numbers) out of debug.log.
 */

namespace Amazonpaymentservices\Fort\Test\Unit\Helper;

use Amazonpaymentservices\Fort\Helper\Data;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

class DataTest extends TestCase
{
    /** @var Data */
    private $helper;

    protected function setUp(): void
    {
        // Helper\Data has a large DI constructor; the redaction methods depend
        // on none of it, so build the instance without invoking the constructor.
        $this->helper = (new ReflectionClass(Data::class))->newInstanceWithoutConstructor();
    }

    private function callPrivate(string $method, array $args)
    {
        $ref = new ReflectionMethod(Data::class, $method);
        $ref->setAccessible(true);

        return $ref->invokeArgs($this->helper, $args);
    }

    public function testSanitizeRedactsKnownSensitiveKeys(): void
    {
        $params = [
            'token_name'     => 'tok_1234567890',
            'card_number'    => '4111111111111111',
            'otp'            => '987654',
            'customer_email' => 'buyer@example.com',
            'customer_ip'    => '203.0.113.9',
            'phone_number'   => '+201234567890',
            'signature'      => 'abcdef0123456789',
            'amount'         => '10000',
            'currency'       => 'AED',
        ];

        $result = $this->helper->sanitizeForLog($params);

        foreach (['token_name', 'card_number', 'otp', 'customer_email', 'customer_ip', 'phone_number', 'signature'] as $key) {
            $this->assertStringContainsString('***REDACTED***', (string) $result[$key], "$key should be redacted");
            $this->assertStringNotContainsString((string) $params[$key], (string) $result[$key], "$key raw value must not survive");
        }

        // Non-sensitive fields are preserved verbatim for debugging.
        $this->assertSame('10000', $result['amount']);
        $this->assertSame('AED', $result['currency']);
    }

    public function testSanitizeKeepsShortPrefixForCorrelation(): void
    {
        $result = $this->helper->sanitizeForLog(['token_name' => 'tok_1234567890']);

        $this->assertSame('tok_***REDACTED***', $result['token_name']);
    }

    public function testCvvAndOtpAreFullyMasked(): void
    {
        $r = $this->helper->sanitizeForLog(['card_security_code' => '123', 'otp' => '987654']);

        // Short one-time secrets must never keep a leading prefix.
        $this->assertSame('***REDACTED***', $r['card_security_code']);
        $this->assertSame('***REDACTED***', $r['otp']);
    }

    public function testCardHolderNameEmailAndBinAreRedacted(): void
    {
        $r = $this->helper->sanitizeForLog([
            'card_holder_name' => 'John Q Cardholder',
            'email'            => 'buyer@example.com',
            'card_bin'         => '4111111111111111',
        ]);

        $this->assertSame('***REDACTED***', $r['card_holder_name']);
        $this->assertSame('***REDACTED***', $r['email']);
        // PAN carried in card_bin must be fully masked, not prefix-preserved.
        $this->assertSame('***REDACTED***', $r['card_bin']);
    }

    public function testShortPrefixKeysBelowThresholdAreFullyMasked(): void
    {
        // A prefix key is only prefix-preserved when the value is long enough
        // that the 4-char prefix is a small part of it.
        $r = $this->helper->sanitizeForLog(['signature' => 'abcd']);

        $this->assertSame('***REDACTED***', $r['signature']);
    }

    public function testSanitizeWalksNestedArrays(): void
    {
        $params = [
            'outer' => [
                'card_number' => '4111111111111111',
                'inner'       => ['otp' => '123456'],
            ],
        ];

        $result = $this->helper->sanitizeForLog($params);

        $this->assertStringContainsString('***REDACTED***', $result['outer']['card_number']);
        $this->assertStringContainsString('***REDACTED***', $result['outer']['inner']['otp']);
    }

    public function testSanitizeWalksNestedStdClass(): void
    {
        $payload = new \stdClass();
        $payload->card_number = '4111111111111111';
        $payload->meta = new \stdClass();
        $payload->meta->otp = '123456';

        $result = $this->helper->sanitizeForLog($payload);

        // Objects are cast to arrays so their properties can be redacted.
        $this->assertIsArray($result);
        $this->assertStringContainsString('***REDACTED***', $result['card_number']);
        $this->assertStringContainsString('***REDACTED***', $result['meta']['otp']);
    }

    public function testSanitizeLeavesNullAndEmptyValuesUntouched(): void
    {
        $result = $this->helper->sanitizeForLog([
            'card_number' => null,
            'otp'         => '',
        ]);

        $this->assertNull($result['card_number']);
        $this->assertSame('', $result['otp']);
    }

    public function testSanitizePassesThroughScalarInput(): void
    {
        $this->assertSame('plain', $this->helper->sanitizeForLog('plain'));
        $this->assertNull($this->helper->sanitizeForLog(null));
    }

    public function testRedactJsonInMessageScrubsEmbeddedPayload(): void
    {
        $message = 'WebHook Data:' . json_encode([
            'card_number'    => '4111111111111111',
            'customer_email' => 'buyer@example.com',
            'response_code'  => '14000',
        ]);

        $result = $this->callPrivate('redactJsonInMessage', [$message]);

        $this->assertStringStartsWith('WebHook Data:', $result);
        $this->assertStringContainsString('***REDACTED***', $result);
        $this->assertStringNotContainsString('4111111111111111', $result);
        $this->assertStringNotContainsString('buyer@example.com', $result);
        // Non-sensitive field survives, and the tail is still valid JSON.
        $this->assertStringContainsString('14000', $result);
        $json = substr($result, strlen('WebHook Data:'));
        $this->assertIsArray(json_decode($json, true));
    }

    public function testRedactJsonInMessageIsIdempotent(): void
    {
        $message = 'Response : ' . json_encode(['token_name' => 'tok_1234567890']);

        $once = $this->callPrivate('redactJsonInMessage', [$message]);
        $twice = $this->callPrivate('redactJsonInMessage', [$once]);

        $this->assertSame($once, $twice);
    }

    public function testRedactJsonInMessageLeavesNonJsonUntouched(): void
    {
        $message = 'APS Cron pending order : 100000123';

        $this->assertSame($message, $this->callPrivate('redactJsonInMessage', [$message]));
    }

    public function testRedactJsonInMessageFailsClosedOnUnparsablePayload(): void
    {
        // A bracketed span that is not valid JSON (e.g. surrounding text pulled
        // into the greedy match) must never be emitted verbatim.
        $message = 'Response [card_number=4111111111111111 otp=987654]';

        $result = $this->callPrivate('redactJsonInMessage', [$message]);

        $this->assertStringContainsString('***REDACTED_UNPARSED_PAYLOAD***', $result);
        $this->assertStringNotContainsString('4111111111111111', $result);
        $this->assertStringNotContainsString('987654', $result);
    }
}

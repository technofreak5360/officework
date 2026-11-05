<?php

namespace Tests\Feature\Api;

use App\Enquiries\EmailStatus;
use App\Mail\EnquiryReceived;
use App\Models\Enquiry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class EnquiryApiTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, mixed> */
    private function payload(array $overrides = []): array
    {
        return [
            'productId' => 3,
            'name' => 'Sam Taylor',
            'email' => 'sam@example.com',
            'phone' => '+44 20 7946 0000',
            'message' => 'Is this in stock?',
            ...$overrides,
        ];
    }

    public function test_a_valid_enquiry_is_saved_and_emailed_to_the_sales_inbox(): void
    {
        Mail::fake();

        $this->postJson('/api/enquiries', $this->payload())
            ->assertCreated()
            ->assertJsonPath('message', 'Thank you for your enquiry.');

        $this->assertDatabaseHas('enquiries', [
            'product_id' => 3,
            'product_title' => 'Mens Cotton Jacket',
            'name' => 'Sam Taylor',
            'email' => 'sam@example.com',
            'email_status' => EmailStatus::Sent->value,
        ]);

        Mail::assertSent(EnquiryReceived::class, fn (EnquiryReceived $mail) => $mail->hasTo('sales@example.com')
            && $mail->hasReplyTo('sam@example.com')
            && $mail->hasSubject('New enquiry: Mens Cotton Jacket'));
    }

    public function test_phone_and_message_are_optional(): void
    {
        Mail::fake();

        $this->postJson('/api/enquiries', ['productId' => 3, 'name' => 'Sam', 'email' => 'sam@example.com'])
            ->assertCreated();

        $this->assertDatabaseHas('enquiries', ['name' => 'Sam', 'phone' => null, 'message' => null]);
    }

    public function test_the_product_title_comes_from_the_catalogue_not_the_client(): void
    {
        Mail::fake();

        $this->postJson('/api/enquiries', $this->payload(['product_title' => 'Spoofed', 'email_status' => 'failed']))
            ->assertCreated();

        $this->assertDatabaseHas('enquiries', [
            'product_title' => 'Mens Cotton Jacket',
            'email_status' => EmailStatus::Sent->value,
        ]);
    }

    public function test_customer_input_is_escaped_in_the_email(): void
    {
        $enquiry = new Enquiry([
            'product_id' => 3,
            'product_title' => "Men's Jacket",
            'name' => '<script>alert(1)</script>',
            'email' => 'sam@example.com',
            'message' => "<b>first</b>\nsecond",
        ]);

        $html = (new EnquiryReceived($enquiry))->render();

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('<b>first', $html);
        $this->assertStringContainsString('&lt;b&gt;first&lt;/b&gt;<br />', $html);
    }

    /** @return array<string, array{array<string, mixed>, string}> */
    public static function invalidEnquiries(): array
    {
        return [
            'missing product' => [['productId' => null], 'productId'],
            'non-numeric product' => [['productId' => 'abc'], 'productId'],
            'unknown product' => [['productId' => 999], 'productId'],
            'missing name' => [['name' => ''], 'name'],
            'name too long' => [['name' => str_repeat('a', 121)], 'name'],
            'missing email' => [['email' => ''], 'email'],
            'invalid email' => [['email' => 'not-an-email'], 'email'],
            'email with a header injection' => [['email' => "sam@example.com\r\nBcc: x@example.com"], 'email'],
            'invalid phone' => [['phone' => 'call me'], 'phone'],
            'message too long' => [['message' => str_repeat('a', 2001)], 'message'],
        ];
    }

    #[DataProvider('invalidEnquiries')]
    public function test_an_invalid_enquiry_is_rejected(array $overrides, string $field): void
    {
        Mail::fake();

        $this->postJson('/api/enquiries', $this->payload($overrides))
            ->assertUnprocessable()
            ->assertJsonValidationErrors($field);

        $this->assertDatabaseCount('enquiries', 0);
        Mail::assertNothingSent();
    }

    public function test_a_mail_failure_keeps_the_lead_and_still_thanks_the_customer(): void
    {
        Exceptions::fake();
        Mail::shouldReceive('to')->once()->andThrow(new RuntimeException('SMTP down'));

        $this->postJson('/api/enquiries', $this->payload())->assertCreated();

        $this->assertDatabaseHas('enquiries', [
            'email' => 'sam@example.com',
            'email_status' => EmailStatus::Failed->value,
        ]);
        Exceptions::assertReported(RuntimeException::class);
    }

    public function test_a_catalogue_outage_returns_503_and_saves_nothing(): void
    {
        Mail::fake();
        config(['catalogue.driver' => 'fakestore']);
        Http::fake(['*' => Http::response('', 500)]);

        $this->postJson('/api/enquiries', $this->payload())->assertServiceUnavailable();

        $this->assertDatabaseCount('enquiries', 0);
        Mail::assertNothingSent();
    }

    public function test_enquiries_are_rate_limited(): void
    {
        Mail::fake();

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/enquiries', $this->payload())->assertCreated();
        }

        $this->postJson('/api/enquiries', $this->payload())->assertTooManyRequests();

        $this->assertDatabaseCount('enquiries', 5);
    }
}

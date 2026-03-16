<?php

use App\Models\Customer;
use App\Models\Setting;

beforeEach(function () {
    $this->user = createSuperAdmin();
    $this->actingAs($this->user);
    Setting::instance();
});

it('includes card_number and card_code in the receipt PDF', function () {
    $customer = Customer::factory()->create([
        'card_number' => 'CARD-12345',
        'card_code' => 'CODE-999',
    ]);

    $response = $this->get(route('customers.invoice', $customer));

    $response->assertOk();

    $html = view('pdf.customer-invoice', [
        'customer' => $customer->load('payments.receivedBy'),
        'settings' => Setting::instance(),
    ])->render();

    expect($html)
        ->toContain('CARD-12345')
        ->toContain('CODE-999')
        ->toContain('معلومات البطاقة');
});

it('includes internal_notes in the receipt when present', function () {
    $customer = Customer::factory()->create([
        'internal_notes' => 'ملاحظة تجريبية للاختبار',
    ]);

    $html = view('pdf.customer-invoice', [
        'customer' => $customer->load('payments.receivedBy'),
        'settings' => Setting::instance(),
    ])->render();

    expect($html)
        ->toContain('ملاحظة تجريبية للاختبار')
        ->toContain('ملاحظات');
});

it('does not show internal_notes block when notes are empty', function () {
    $customer = Customer::factory()->create([
        'internal_notes' => null,
    ]);

    $html = view('pdf.customer-invoice', [
        'customer' => $customer->load('payments.receivedBy'),
        'settings' => Setting::instance(),
    ])->render();

    expect($html)->not->toContain('ملاحظة تجريبية للاختبار');
});

it('does not show card info block when card fields are empty', function () {
    $customer = Customer::factory()->create([
        'card_number' => null,
        'card_code' => null,
    ]);

    $html = view('pdf.customer-invoice', [
        'customer' => $customer->load('payments.receivedBy'),
        'settings' => Setting::instance(),
    ])->render();

    expect($html)->not->toContain('معلومات البطاقة');
});

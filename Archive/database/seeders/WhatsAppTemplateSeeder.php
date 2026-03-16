<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $config = Setting::instance()->whatsapp_provider_config ?? [];

        if (($config['provider'] ?? '') !== 'cloud_api') {
            $this->command->warn('WhatsApp provider is not cloud_api. Skipping template creation.');

            return;
        }

        $accessToken = $config['access_token'] ?? '';
        $phoneNumberId = $config['phone_number_id'] ?? '';

        if (! $accessToken || ! $phoneNumberId) {
            $this->command->error('Missing access_token or phone_number_id in WhatsApp config.');

            return;
        }

        // Discover the WABA ID from the phone number
        $wabaId = $this->discoverWabaId($accessToken, $phoneNumberId);

        if (! $wabaId) {
            $this->command->error('Could not discover WABA ID. Please set it manually in the config.');

            return;
        }

        $this->command->info("WABA ID: {$wabaId}");

        // Get existing templates
        $existing = $this->getExistingTemplates($accessToken, $wabaId);
        $this->command->info('Existing templates: '.implode(', ', array_keys($existing)) ?: 'none');

        foreach ($this->templates() as $template) {
            $name = $template['name'];
            $lang = $template['language'];

            if (isset($existing["{$name}_{$lang}"])) {
                $status = $existing["{$name}_{$lang}"]['status'];
                $this->command->info("  [{$name}] ({$lang}) already exists — status: {$status}");

                continue;
            }

            $this->command->info("  [{$name}] ({$lang}) creating...");

            $response = Http::withToken($accessToken)
                ->post("https://graph.facebook.com/v22.0/{$wabaId}/message_templates", $template);

            if ($response->successful()) {
                $id = $response->json('id');
                $status = $response->json('status');
                $this->command->info("    Created! ID: {$id}, Status: {$status}");
            } else {
                $error = $response->json('error.message', $response->body());
                $this->command->error("    Failed: {$error}");
                Log::error('WhatsApp template creation failed', [
                    'template' => $name,
                    'error' => $error,
                ]);
            }
        }

        $this->command->newLine();
        $this->command->info('Done. Pending templates need Meta approval before they can be used.');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function templates(): array
    {
        return [
            [
                'name' => 'payment_due_reminder',
                'language' => 'ar',
                'category' => 'UTILITY',
                'components' => [
                    [
                        'type' => 'BODY',
                        'text' => "مرحباً {{1}}،\nهذا تذكير من {{2}} بخصوص قسطك المستحق.\n\nتاريخ الاستحقاق: {{3}}\nمبلغ القسط: {{4}}\nالمدفوع حتى الآن: {{5}}\nالمتبقي: {{6}}\n\nشكراً لتعاملك معنا.",
                        'example' => [
                            'body_text' => [['أحمد عباس', 'شوي شوي', '2026/03/01', '162,500 د.ع', '812,500 د.ع', '487,500 د.ع']],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'payment_received_confirmation',
                'language' => 'ar',
                'category' => 'UTILITY',
                'components' => [
                    [
                        'type' => 'BODY',
                        'text' => "مرحباً {{1}}،\nتم استلام تسديدك بنجاح من {{2}}.\n\nالمبلغ المستلم: {{3}}\nالمبلغ المدفوع الكلي: {{4}}\nالمتبقي: {{5}}\n\nشكراً لك.",
                        'example' => [
                            'body_text' => [['أحمد عباس', 'شوي شوي', '162,500 د.ع', '812,500 د.ع', '487,500 د.ع']],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'investor_summary',
                'language' => 'ar',
                'category' => 'UTILITY',
                'components' => [
                    [
                        'type' => 'BODY',
                        'text' => "مرحباً {{1}}،\nهذا ملخص استثمارك من {{2}}.\n\nمبلغ الاستثمار: {{3}}\nالمبلغ المستحق: {{4}}\nالمدفوع: {{5}}\nالمتبقي: {{6}}\nنسبة التقدم: {{7}}%\n\nشكراً لثقتك.",
                        'example' => [
                            'body_text' => [['حاج كاظم', 'شوي شوي', '50,000,000 د.ع', '60,000,000 د.ع', '30,000,000 د.ع', '30,000,000 د.ع', '50']],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'late_payer_alert',
                'language' => 'ar',
                'category' => 'UTILITY',
                'components' => [
                    [
                        'type' => 'BODY',
                        'text' => "تنبيه: زبون متأخر\n\nالاسم: {{1}}\nالهاتف: {{2}}\nمتأخر بـ {{3}} شهر\nالقسط الشهري: {{4}}\nالمتبقي: {{5}}\n\nيرجى المتابعة.",
                        'example' => [
                            'body_text' => [['علي حسين', '07701234567', '3', '400,000 د.ع', '1,200,000 د.ع']],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function discoverWabaId(string $accessToken, string $phoneNumberId): ?string
    {
        $response = Http::withToken($accessToken)
            ->get("https://graph.facebook.com/v22.0/debug_token", [
                'input_token' => $accessToken,
                'access_token' => $accessToken,
            ]);

        if (! $response->successful()) {
            return null;
        }

        $scopes = $response->json('data.granular_scopes', []);

        foreach ($scopes as $scope) {
            if ($scope['scope'] === 'whatsapp_business_management') {
                foreach ($scope['target_ids'] ?? [] as $wabaId) {
                    // Check if this WABA owns the phone number
                    $check = Http::withToken($accessToken)
                        ->get("https://graph.facebook.com/v22.0/{$wabaId}/phone_numbers", [
                            'fields' => 'id',
                        ]);

                    $phones = collect($check->json('data', []))->pluck('id')->all();

                    if (in_array($phoneNumberId, $phones)) {
                        return $wabaId;
                    }
                }
            }
        }

        return null;
    }

    private function getExistingTemplates(string $accessToken, string $wabaId): array
    {
        $existing = [];

        $response = Http::withToken($accessToken)
            ->get("https://graph.facebook.com/v22.0/{$wabaId}/message_templates", [
                'fields' => 'name,status,language',
                'limit' => 100,
            ]);

        foreach ($response->json('data', []) as $template) {
            $key = $template['name'].'_'.$template['language'];
            $existing[$key] = $template;
        }

        return $existing;
    }
}

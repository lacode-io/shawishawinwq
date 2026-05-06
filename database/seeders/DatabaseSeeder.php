<?php

namespace Database\Seeders;

use App\Enums\CustomerStatus;
use App\Enums\ExpenseSubType;
use App\Enums\ExpenseType;
use App\Enums\InvestorStatus;
use App\Enums\PaymentMethod;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\Expense;
use App\Models\FinanceClosing;
use App\Models\Investor;
use App\Models\InvestorPayout;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // ── Settings (رأس المال الكاش = 50 مليون) ──
        Setting::firstOrCreate(['id' => 1], [
            'site_name' => 'شوي شوي',
            'primary_color' => '#0ea5e9',
            'secondary_color' => '#8b5cf6',
            'cash_capital' => 50_000_000,
        ]);

        // ── Permissions ──
        $permissions = [
            'view_customers', 'create_customers', 'update_customers', 'delete_customers',
            'view_customer_payments', 'create_customer_payments', 'update_customer_payments', 'delete_customer_payments',
            'view_investors', 'create_investors', 'update_investors', 'delete_investors',
            'view_investor_payouts', 'create_investor_payouts', 'update_investor_payouts', 'delete_investor_payouts',
            'view_expenses', 'create_expenses', 'update_expenses', 'delete_expenses',
            'view_finance_closings', 'create_finance_closings', 'update_finance_closings', 'delete_finance_closings',
            'view_users', 'create_users', 'update_users', 'delete_users',
            'view_roles', 'create_roles', 'update_roles', 'delete_roles',
            'view_app_notes', 'create_app_notes', 'update_app_notes', 'delete_app_notes',
            'view_activity_log', 'manage_settings', 'export_pdf',
            'view_finance_dashboard', 'view_targets', 'mark_completed',
            'view_scheduled_notifications', 'manage_scheduled_notifications',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // ── Roles ──
        $admin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $admin->syncPermissions(Permission::all());

        $accountant = Role::firstOrCreate(['name' => 'accountant', 'guard_name' => 'web']);
        $accountant->syncPermissions([
            'view_customers', 'create_customers', 'update_customers',
            'view_customer_payments', 'create_customer_payments', 'update_customer_payments',
            'view_investors', 'create_investors', 'update_investors',
            'view_investor_payouts', 'create_investor_payouts', 'update_investor_payouts',
            'view_expenses', 'create_expenses', 'update_expenses',
            'view_finance_closings', 'create_finance_closings', 'update_finance_closings',
            'view_app_notes', 'create_app_notes', 'update_app_notes',
            'view_finance_dashboard', 'view_targets', 'mark_completed',
            'view_scheduled_notifications', 'manage_scheduled_notifications',
            'export_pdf',
        ]);

        $viewer = Role::firstOrCreate(['name' => 'viewer', 'guard_name' => 'web']);
        $viewer->syncPermissions([
            'view_customers', 'view_customer_payments',
            'view_investors', 'view_investor_payouts',
            'view_expenses', 'view_finance_closings',
            'view_app_notes',
            'view_scheduled_notifications',
            'view_activity_log', 'view_finance_dashboard', 'view_targets',
        ]);

        // ── Users ──
        $adminUser = User::factory()->create([
            'name' => 'مدير النظام',
            'email' => 'admin@shawishawi.test',
        ]);
        $adminUser->assignRole($admin);

        $accountantUser = User::factory()->create([
            'name' => 'محمد المحاسب',
            'email' => 'accountant@shawishawi.test',
        ]);
        $accountantUser->assignRole($accountant);

        $viewerUser = User::factory()->create([
            'name' => 'أحمد المشاهد',
            'email' => 'viewer@shawishawi.test',
        ]);
        $viewerUser->assignRole($viewer);

        // ══════════════════════════════════════════════
        // ── 5 زبائن ──
        // ══════════════════════════════════════════════

        $customers = [
            [
                'full_name' => 'علي حسين كاظم',
                'phone' => '07701234567',
                'address' => 'بغداد - الكرادة',
                'guarantor_name' => 'حسين كاظم علي',
                'guarantor_phone' => '07701234568',
                'product_type' => 'آيفون 15 برو ماكس',
                'product_cost_price' => 1_800_000,
                'product_sale_total' => 2_400_000,
                'delivery_date' => '2025-09-10',
                'duration_months' => 6,
            ],
            [
                'full_name' => 'أحمد عباس محمد',
                'phone' => '07812345678',
                'address' => 'بغداد - المنصور',
                'guarantor_name' => 'عباس محمد جاسم',
                'guarantor_phone' => '07812345679',
                'product_type' => 'لابتوب HP Pavilion',
                'product_cost_price' => 1_200_000,
                'product_sale_total' => 1_800_000,
                'delivery_date' => '2025-07-01',
                'duration_months' => 8,
            ],
            [
                'full_name' => 'حسن جابر عبدالله',
                'phone' => '07501234567',
                'address' => 'البصرة - العشار',
                'guarantor_name' => 'جابر عبدالله حسن',
                'guarantor_phone' => '07501234568',
                'product_type' => 'تلفزيون سامسونج 75 بوصة',
                'product_cost_price' => 2_000_000,
                'product_sale_total' => 3_000_000,
                'delivery_date' => '2025-05-15',
                'duration_months' => 10,
            ],
            [
                'full_name' => 'مصطفى كريم جواد',
                'phone' => '07731234567',
                'address' => 'كربلاء - حي الحسين',
                'guarantor_name' => 'كريم جواد صالح',
                'guarantor_phone' => '07731234568',
                'product_type' => 'ثلاجة LG 22 قدم',
                'product_cost_price' => 1_000_000,
                'product_sale_total' => 1_500_000,
                'delivery_date' => '2025-09-20',
                'duration_months' => 6,
            ],
            [
                'full_name' => 'زهراء عادل حميد',
                'phone' => '07901234567',
                'address' => 'النجف - حي السعد',
                'guarantor_name' => 'عادل حميد ناصر',
                'guarantor_phone' => '07901234568',
                'product_type' => 'آيفون 16 برو + كفر وحماية',
                'product_cost_price' => 1_700_000,
                'product_sale_total' => 2_500_000,
                'delivery_date' => '2025-07-10',
                'duration_months' => 8,
            ],
        ];

        foreach ($customers as $data) {
            $installment = (int) ceil($data['product_sale_total'] / $data['duration_months']);

            $customer = Customer::create([
                ...$data,
                'monthly_installment_amount' => $installment,
                'status' => CustomerStatus::Active,
                'created_by' => $adminUser->id,
                'updated_by' => $adminUser->id,
            ]);

            $deliveryDate = Carbon::parse($data['delivery_date']);
            $now = Carbon::now();
            $monthsSinceDelivery = max(0, (int) $deliveryDate->diffInMonths($now));
            $paidCount = min($monthsSinceDelivery, $data['duration_months']);

            for ($m = 1; $m <= $paidCount; $m++) {
                CustomerPayment::create([
                    'customer_id' => $customer->id,
                    'paid_at' => $deliveryDate->copy()->addMonths($m)->addDays(rand(0, 3)),
                    'amount' => $installment,
                    'payment_method' => rand(0, 3) === 0 ? PaymentMethod::Transfer : PaymentMethod::Cash,
                    'received_by' => rand(0, 1) ? $adminUser->id : $accountantUser->id,
                    'created_by' => $adminUser->id,
                    'updated_by' => $adminUser->id,
                ]);
            }
        }

        // ══════════════════════════════════════════════
        // ── 3 مستثمرين ──
        // ══════════════════════════════════════════════

        $investors = [
            [
                'full_name' => 'حاج كاظم عبدالزهراء',
                'phone' => '07700001111',
                'amount_invested' => 1_000_000,
                'investment_months' => 12,
                'profit_percent_total' => 10,
                'start_date' => '2025-10-01',
                'payout_due_date' => '2026-10-01',
            ],
            [
                'full_name' => 'أبو محمد النجفي',
                'phone' => '07800002222',
                'amount_invested' => 800_000,
                'investment_months' => 8,
                'profit_percent_total' => 10,
                'start_date' => '2025-11-01',
                'payout_due_date' => '2026-07-01',
            ],
            [
                'full_name' => 'سيد جاسم الموسوي',
                'phone' => '07500003333',
                'amount_invested' => 500_000,
                'investment_months' => 10,
                'profit_percent_total' => 10,
                'start_date' => '2025-10-01',
                'payout_due_date' => '2026-08-01',
            ],
        ];

        foreach ($investors as $data) {
            $totalDue = $data['amount_invested'] + (int) round($data['amount_invested'] * ($data['profit_percent_total'] / 100));
            $monthlyTarget = (int) ceil($totalDue / $data['investment_months']);

            $investor = Investor::create([
                ...$data,
                'monthly_target_amount' => $monthlyTarget,
                'status' => InvestorStatus::Active,
                'created_by' => $adminUser->id,
                'updated_by' => $adminUser->id,
            ]);

            $startDate = Carbon::parse($data['start_date']);
            $now = Carbon::now();
            $monthsSinceStart = max(0, (int) $startDate->diffInMonths($now));
            $paidCount = min($monthsSinceStart, $data['investment_months']);

            for ($m = 1; $m <= $paidCount; $m++) {
                InvestorPayout::create([
                    'investor_id' => $investor->id,
                    'paid_at' => $startDate->copy()->addMonths($m)->addDays(rand(0, 2)),
                    'amount' => $monthlyTarget,
                    'notes' => 'تسديد تلقائي من أرباح المبيعات - شهر ' . $m,
                    'created_by' => $adminUser->id,
                    'updated_by' => $adminUser->id,
                ]);
            }
        }

        // ══════════════════════════════════════════════
        // ── المصاريف ──
        // ══════════════════════════════════════════════

        $businessExpenses = [
            ['amount' => 150_000, 'notes' => 'إيجار المحل - شهر شباط', 'spent_at' => '2026-02-05'],
            ['amount' => 75_000, 'notes' => 'فاتورة كهرباء المحل', 'spent_at' => '2026-02-15'],
            ['amount' => 50_000, 'notes' => 'صيانة الحاسبة والطابعة', 'spent_at' => '2026-02-20'],
            ['amount' => 150_000, 'notes' => 'إيجار المحل - شهر آذار', 'spent_at' => '2026-03-05'],
            ['amount' => 80_000, 'notes' => 'فاتورة كهرباء + إنترنت', 'spent_at' => '2026-03-08'],
            ['amount' => 45_000, 'notes' => 'إعلانات فيسبوك وانستغرام', 'spent_at' => '2026-03-02'],
        ];

        $personalExpenses = [
            ['amount' => 500_000, 'notes' => 'إيجار البيت', 'sub_type' => ExpenseSubType::Haider, 'spent_at' => '2026-02-01'],
            ['amount' => 200_000, 'notes' => 'مصروف شهري', 'sub_type' => ExpenseSubType::Haider, 'spent_at' => '2026-02-10'],
            ['amount' => 500_000, 'notes' => 'إيجار البيت - آذار', 'sub_type' => ExpenseSubType::Haider, 'spent_at' => '2026-03-01'],
            ['amount' => 200_000, 'notes' => 'مصروف شهري آذار', 'sub_type' => ExpenseSubType::Haider, 'spent_at' => '2026-03-05'],
            ['amount' => 150_000, 'notes' => 'ملابس', 'sub_type' => ExpenseSubType::Haider, 'spent_at' => '2026-03-07'],
            ['amount' => 350_000, 'notes' => 'إيجار بيت', 'sub_type' => ExpenseSubType::Thaqr, 'spent_at' => '2026-02-01'],
            ['amount' => 150_000, 'notes' => 'مصروف شهري', 'sub_type' => ExpenseSubType::Thaqr, 'spent_at' => '2026-02-12'],
            ['amount' => 350_000, 'notes' => 'إيجار بيت - آذار', 'sub_type' => ExpenseSubType::Thaqr, 'spent_at' => '2026-03-01'],
            ['amount' => 150_000, 'notes' => 'مصروف شهري آذار', 'sub_type' => ExpenseSubType::Thaqr, 'spent_at' => '2026-03-06'],
            ['amount' => 120_000, 'notes' => 'طعام ومشتريات', 'sub_type' => ExpenseSubType::Thaqr, 'spent_at' => '2026-03-08'],
            ['amount' => 100_000, 'notes' => 'وقود السيارة', 'sub_type' => ExpenseSubType::Other, 'spent_at' => '2026-02-15'],
            ['amount' => 75_000, 'notes' => 'فاتورة هاتف', 'sub_type' => ExpenseSubType::Other, 'spent_at' => '2026-03-03'],
            ['amount' => 60_000, 'notes' => 'صيانة سيارة', 'sub_type' => ExpenseSubType::Other, 'spent_at' => '2026-03-09'],
        ];

        foreach ($businessExpenses as $expense) {
            Expense::create([
                'type' => ExpenseType::Business,
                'amount' => $expense['amount'],
                'spent_at' => $expense['spent_at'],
                'notes' => $expense['notes'],
                'created_by' => $adminUser->id,
                'updated_by' => $adminUser->id,
            ]);
        }

        foreach ($personalExpenses as $expense) {
            Expense::create([
                'type' => ExpenseType::Personal,
                'sub_type' => $expense['sub_type'],
                'amount' => $expense['amount'],
                'spent_at' => $expense['spent_at'],
                'notes' => $expense['notes'],
                'created_by' => $adminUser->id,
                'updated_by' => $adminUser->id,
            ]);
        }

        // ── Finance Closing ──
        FinanceClosing::create([
            'closed_at' => now()->subMonth()->endOfMonth(),
            'notes' => 'إغلاق حسابات شهر ' . now()->subMonth()->translatedFormat('F Y'),
            'rules_applied' => ['auto_close' => true, 'include_pending' => false],
            'snapshot_data' => [
                'total_capital' => 7_700_000,
                'cash_capital' => 50_000_000,
                'monthly_profit' => 458_334,
                'monthly_expenses' => 550_000,
            ],
            'created_by' => $adminUser->id,
            'updated_by' => $adminUser->id,
        ]);

        // ── Activity Log ──
        $activityEntries = [
            ['description' => 'تم created زبون', 'subject_type' => Customer::class, 'subject_id' => 1, 'causer_id' => $adminUser->id, 'event' => 'created', 'properties' => ['attributes' => ['full_name' => 'علي حسين كاظم', 'product_sale_total' => 2_400_000]]],
            ['description' => 'تم created مستثمر', 'subject_type' => Investor::class, 'subject_id' => 1, 'causer_id' => $adminUser->id, 'event' => 'created', 'properties' => ['attributes' => ['full_name' => 'حاج كاظم عبدالزهراء', 'amount_invested' => 10_000_000]]],
            ['description' => 'تم created مصروف', 'subject_type' => Expense::class, 'subject_id' => 1, 'causer_id' => $accountantUser->id, 'event' => 'created', 'properties' => ['attributes' => ['type' => 'business', 'amount' => 150_000]]],
        ];

        foreach ($activityEntries as $j => $entry) {
            activity()
                ->performedOn(new $entry['subject_type'](['id' => $entry['subject_id']]))
                ->causedBy($entry['causer_id'])
                ->withProperties($entry['properties'])
                ->event($entry['event'])
                ->log($entry['description']);

            \Spatie\Activitylog\Models\Activity::latest('id')->first()
                ->update(['created_at' => now()->subDays(30 - ($j * 10))->subHours(rand(1, 12))]);
        }
    }
}

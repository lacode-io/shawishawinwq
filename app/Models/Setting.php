<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'site_name',
        'logo_path',
        'primary_color',
        'secondary_color',
        'cash_capital',
        'extra_capital',
        'yearly_target_amount',
        'cash_register_balance',
        'whatsapp_provider_config',
    ];

    protected function casts(): array
    {
        return [
            'cash_capital' => 'integer',
            'extra_capital' => 'integer',
            'yearly_target_amount' => 'integer',
            'cash_register_balance' => 'integer',
            'whatsapp_provider_config' => 'array',
        ];
    }

    public static function instance(): static
    {
        return static::firstOrCreate(['id' => 1], [
            'site_name' => 'شوي شوي',
            'primary_color' => '#0ea5e9',
            'secondary_color' => '#8b5cf6',
        ]);
    }
}

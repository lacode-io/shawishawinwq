<?php

namespace App\Filament\Resources\InvestorResource\Pages;

use App\Filament\Resources\InvestorResource;
use App\Filament\Resources\InvestorResource\Widgets\InvestorStatsOverview;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInvestors extends ListRecords
{
    protected static string $resource = InvestorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            InvestorStatsOverview::class,
        ];
    }
}

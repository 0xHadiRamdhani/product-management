<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    public function getColumns(): int | string | array
    {
        return 2;
    }

    public function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\StatsOverview::class,
            \App\Filament\Widgets\StockValueChart::class,
            \App\Filament\Widgets\FastMovingParts::class,
            \App\Filament\Widgets\SlowMovingParts::class,
            \App\Filament\Widgets\PendingPurchaseOrders::class,
            \App\Filament\Widgets\LowStockAlerts::class,
            \App\Filament\Widgets\RecentStockMovements::class,
        ];
    }

    public function getFiltersFormSchema(): array
    {
        return [
            \Filament\Forms\Components\Select::make('location_id')
                ->label('Lokasi')
                ->relationship('location', 'name')
                ->searchable()
                ->preload()
                ->nullable(),
            \Filament\Forms\Components\DatePicker::make('start_date')
                ->label('Tanggal Mulai')
                ->default(now()->subDays(30)),
            \Filament\Forms\Components\DatePicker::make('end_date')
                ->label('Tanggal Selesai')
                ->default(now()),
        ];
    }
}
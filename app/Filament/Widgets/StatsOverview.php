<?php

namespace App\Filament\Widgets;

use App\Models\SparePart;
use App\Models\Stock;
use App\Models\PurchaseOrder;
use App\Models\ServiceJob;
use App\Models\LowStockAlert;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $totalSpareParts = SparePart::count();
        $totalStockValue = Stock::with('sparePart')
            ->get()
            ->sum(fn ($stock) => $stock->quantity * $stock->sparePart->cost_price);
        
        $pendingPOs = PurchaseOrder::whereIn('status', ['pending', 'approved', 'sent'])->count();
        $activeServiceJobs = ServiceJob::whereNotIn('status', ['completed', 'cancelled'])->count();
        $lowStockAlerts = LowStockAlert::where('is_resolved', false)->count();

        return [
            Stat::make('Total Spare Part', $totalSpareParts)
                ->description('Jumlah spare part dalam sistem')
                ->descriptionIcon('heroicon-o-cube')
                ->color('primary'),
            
            Stat::make('Nilai Stok', 'Rp ' . number_format($totalStockValue, 0, ',', '.'))
                ->description('Total nilai stok saat ini')
                ->descriptionIcon('heroicon-o-banknotes')
                ->color('success'),
            
            Stat::make('PO Pending', $pendingPOs)
                ->description('Purchase order yang belum selesai')
                ->descriptionIcon('heroicon-o-shopping-cart')
                ->color('warning'),
            
            Stat::make('Service Job Aktif', $activeServiceJobs)
                ->description('Service job yang sedang berjalan')
                ->descriptionIcon('heroicon-o-wrench-screwdriver')
                ->color('info'),
            
            Stat::make('Peringatan Stok', $lowStockAlerts)
                ->description('Spare part dengan stok rendah')
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->color('danger'),
        ];
    }
}
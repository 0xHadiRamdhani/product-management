<?php

namespace App\Filament\Resources\LowStockAlertResource\Pages;

use App\Filament\Resources\LowStockAlertResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLowStockAlert extends EditRecord
{
    protected static string $resource = LowStockAlertResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}

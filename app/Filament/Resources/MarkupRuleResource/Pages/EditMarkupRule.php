<?php

namespace App\Filament\Resources\MarkupRuleResource\Pages;

use App\Filament\Resources\MarkupRuleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMarkupRule extends EditRecord
{
    protected static string $resource = MarkupRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}

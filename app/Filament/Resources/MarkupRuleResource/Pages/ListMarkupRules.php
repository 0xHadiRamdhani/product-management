<?php

namespace App\Filament\Resources\MarkupRuleResource\Pages;

use App\Filament\Resources\MarkupRuleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMarkupRules extends ListRecords
{
    protected static string $resource = MarkupRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

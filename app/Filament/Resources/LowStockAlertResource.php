<?php

namespace App\Filament\Resources;

use App\Models\LowStockAlert;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LowStockAlertResource extends Resource
{
    protected static ?string $model = LowStockAlert::class;

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';
    protected static ?string $navigationGroup = 'Inventori';
    protected static ?string $navigationLabel = 'Peringatan Stok Rendah';
    protected static ?string $modelLabel = 'Peringatan Stok Rendah';
    protected static ?string $pluralModelLabel = 'Peringatan Stok Rendah';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Peringatan')->schema([
                    Forms\Components\Select::make('stock_id')
                        ->label('Stok')
                        ->relationship('stock', 'id')
                        ->searchable()
                        ->preload()
                        ->required(),
                    Forms\Components\Select::make('severity')
                        ->label('Tingkat Keparahan')
                        ->options([
                            'low' => 'Rendah',
                            'medium' => 'Sedang',
                            'high' => 'Tinggi',
                            'critical' => 'Kritis',
                        ])
                        ->required()
                        ->default('medium'),
                    Forms\Components\TextInput::make('current_quantity')
                        ->label('Jumlah Saat Ini')
                        ->required()
                        ->numeric(),
                    Forms\Components\TextInput::make('minimum_quantity')
                        ->label('Jumlah Minimum')
                        ->required()
                        ->numeric(),
                    Forms\Components\TextInput::make('recommended_quantity')
                        ->label('Jumlah Rekomendasi')
                        ->numeric(),
                    Forms\Components\Textarea::make('message')
                        ->label('Pesan')
                        ->maxLength(65535)
                        ->columnSpanFull(),
                ])->columns(2),

                Forms\Components\Section::make('Status')->schema([
                    Forms\Components\Toggle::make('is_resolved')
                        ->label('Sudah Diselesaikan')
                        ->default(false),
                    Forms\Components\DatePicker::make('resolved_at')
                        ->label('Tanggal Diselesaikan'),
                    Forms\Components\Textarea::make('resolution_notes')
                        ->label('Catatan Penyelesaian')
                        ->maxLength(65535)
                        ->columnSpanFull(),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('stock.sparePart.sku')
                    ->label('SKU')
                    ->searchable(),
                Tables\Columns\TextColumn::make('stock.sparePart.name')
                    ->label('Nama Spare Part')
                    ->searchable(),
                Tables\Columns\TextColumn::make('stock.location.name')
                    ->label('Lokasi')
                    ->searchable(),
                Tables\Columns\TextColumn::make('current_quantity')
                    ->label('Jumlah Saat Ini')
                    ->numeric(),
                Tables\Columns\TextColumn::make('minimum_quantity')
                    ->label('Minimum')
                    ->numeric(),
                Tables\Columns\TextColumn::make('severity')
                    ->label('Tingkat Keparahan')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'low' => 'gray',
                        'medium' => 'warning',
                        'high' => 'danger',
                        'critical' => 'danger',
                    }),
                Tables\Columns\IconColumn::make('is_resolved')
                    ->label('Status')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('resolved_at')
                    ->label('Diselesaikan')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('severity')
                    ->label('Tingkat Keparahan')
                    ->options([
                        'low' => 'Rendah',
                        'medium' => 'Sedang',
                        'high' => 'Tinggi',
                        'critical' => 'Kritis',
                    ]),
                Tables\Filters\TernaryFilter::make('is_resolved')
                    ->label('Status Penyelesaian'),
                Tables\Filters\SelectFilter::make('location')
                    ->label('Lokasi')
                    ->relationship('stock.location', 'name'),
                Tables\Filters\Filter::make('date_range')
                    ->label('Rentang Tanggal')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Dari'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Sampai'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
                Tables\Actions\Action::make('resolve')
                    ->label('Tandai Selesai')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn ($record) => !$record->is_resolved)
                    ->form([
                        Forms\Components\Textarea::make('resolution_notes')
                            ->label('Catatan Penyelesaian')
                            ->maxLength(65535),
                    ])
                    ->action(function ($record, $data) {
                        $record->update([
                            'is_resolved' => true,
                            'resolved_at' => now(),
                            'resolution_notes' => $data['resolution_notes'] ?? '',
                        ]);
                    }),
                Tables\Actions\Action::make('create_po')
                    ->label('Buat PO')
                    ->icon('heroicon-o-shopping-cart')
                    ->color('primary')
                    ->url(fn ($record) => route('filament.admin.resources.purchase-orders.create', [
                        'spare_part_id' => $record->stock->spare_part_id,
                        'location_id' => $record->stock->location_id,
                    ])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLowStockAlerts::route('/'),
            'create' => Pages\CreateLowStockAlert::route('/create'),
            'edit' => Pages\EditLowStockAlert::route('/{record}/edit'),
            'view' => Pages\ViewLowStockAlert::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StockResource\Pages;
use App\Filament\Resources\StockResource\RelationManagers;
use App\Models\Stock;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class StockResource extends Resource
{
    protected static ?string $model = Stock::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';
    protected static ?string $navigationGroup = 'Inventori';
    protected static ?string $navigationLabel = 'Stok';
    protected static ?string $modelLabel = 'Stok';
    protected static ?string $pluralModelLabel = 'Stok';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Stok')
                    ->schema([
                        Forms\Components\Select::make('spare_part_id')
                            ->label('Spare Part')
                            ->relationship('sparePart', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('location_id')
                            ->label('Lokasi')
                            ->relationship('location', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\TextInput::make('quantity')
                            ->label('Jumlah Stok')
                            ->required()
                            ->numeric()
                            ->default(0),
                        Forms\Components\TextInput::make('reserved_quantity')
                            ->label('Jumlah Dipesan')
                            ->numeric()
                            ->default(0),
                        Forms\Components\TextInput::make('min_stock_level')
                            ->label('Stok Minimum')
                            ->numeric()
                            ->default(0),
                        Forms\Components\TextInput::make('max_stock_level')
                            ->label('Stok Maksimum')
                            ->numeric()
                            ->default(0),
                        Forms\Components\TextInput::make('reorder_point')
                            ->label('Titik Pemesanan Ulang')
                            ->numeric()
                            ->default(0),
                        Forms\Components\Textarea::make('notes')
                            ->label('Catatan')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Informasi Tambahan')
                    ->schema([
                        Forms\Components\TextInput::make('bin_location')
                            ->label('Lokasi Rak/Bin')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('aisle')
                            ->label('Gang')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('shelf')
                            ->label('Rak')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('level')
                            ->label('Tingkat')
                            ->maxLength(255),
                    ])->columns(2),

                Forms\Components\Section::make('Pengaturan')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true),
                        Forms\Components\Toggle::make('allow_negative_stock')
                            ->label('Izinkan Stok Negatif')
                            ->default(false),
                        Forms\Components\Toggle::make('auto_reorder')
                            ->label('Pemesanan Otomatis')
                            ->default(false),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sparePart.sku')
                    ->label('SKU')
                    ->searchable(),
                Tables\Columns\TextColumn::make('sparePart.name')
                    ->label('Nama Spare Part')
                    ->searchable(),
                Tables\Columns\TextColumn::make('location.name')
                    ->label('Lokasi')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Stok')
                    ->numeric()
                    ->sortable()
                    ->color(fn ($state, $record) => 
                        $state <= $record->min_stock_level ? 'danger' : 
                        ($state <= $record->reorder_point ? 'warning' : 'success')
                    ),
                Tables\Columns\TextColumn::make('reserved_quantity')
                    ->label('Dipesan')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('available_quantity')
                    ->label('Tersedia')
                    ->numeric()
                    ->getStateUsing(fn ($record) => $record->quantity - $record->reserved_quantity)
                    ->sortable(),
                Tables\Columns\TextColumn::make('min_stock_level')
                    ->label('Min')
                    ->numeric(),
                Tables\Columns\TextColumn::make('max_stock_level')
                    ->label('Max')
                    ->numeric(),
                Tables\Columns\TextColumn::make('reorder_point')
                    ->label('Reorder')
                    ->numeric(),
                Tables\Columns\TextColumn::make('bin_location')
                    ->label('Lokasi')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean(),
                Tables\Columns\TextColumn::make('last_updated')
                    ->label('Terakhir Diperbarui')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('location')
                    ->label('Lokasi')
                    ->relationship('location', 'name'),
                Tables\Filters\SelectFilter::make('spare_part')
                    ->label('Spare Part')
                    ->relationship('sparePart', 'name'),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status Aktif'),
                Tables\Filters\Filter::make('low_stock')
                    ->label('Stok Rendah')
                    ->query(fn (Builder $query) => $query->whereColumn('quantity', '<=', 'min_stock_level')),
                Tables\Filters\Filter::make('reorder_needed')
                    ->label('Perlu Reorder')
                    ->query(fn (Builder $query) => $query->whereColumn('quantity', '<=', 'reorder_point')),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('adjust')
                    ->label('Penyesuaian')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->form([
                        Forms\Components\Select::make('adjustment_type')
                            ->label('Tipe Penyesuaian')
                            ->options([
                                'increase' => 'Penambahan',
                                'decrease' => 'Pengurangan',
                                'set' => 'Set Ulang',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('quantity')
                            ->label('Jumlah')
                            ->required()
                            ->numeric(),
                        Forms\Components\Textarea::make('reason')
                            ->label('Alasan')
                            ->required()
                            ->maxLength(65535),
                    ])
                    ->action(function ($record, $data) {
                        $record->adjustStock(
                            $data['quantity'],
                            $data['adjustment_type'],
                            $data['reason']
                        );
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\StockMovementsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStocks::route('/'),
            'create' => Pages\CreateStock::route('/create'),
            'edit' => Pages\EditStock::route('/{record}/edit'),
            'view' => Pages\ViewStock::route('/{record}'),
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
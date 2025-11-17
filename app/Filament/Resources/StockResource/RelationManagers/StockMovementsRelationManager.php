<?php

namespace App\Filament\Resources\StockResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StockMovementsRelationManager extends RelationManager
{
    protected static string $relationship = 'stockMovements';

    protected static ?string $title = 'Pergerakan Stok';
    protected static ?string $modelLabel = 'Pergerakan Stok';
    protected static ?string $pluralModelLabel = 'Pergerakan Stok';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('type')
                    ->label('Tipe Pergerakan')
                    ->options([
                        'in' => 'Masuk',
                        'out' => 'Keluar',
                        'adjustment' => 'Penyesuaian',
                        'transfer' => 'Transfer',
                        'purchase' => 'Pembelian',
                        'sale' => 'Penjualan',
                        'return' => 'Retur',
                        'damage' => 'Kerusakan',
                        'loss' => 'Kehilangan',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('quantity')
                    ->label('Jumlah')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('reference_number')
                    ->label('Nomor Referensi')
                    ->maxLength(255),
                Forms\Components\Select::make('reference_type')
                    ->label('Tipe Referensi')
                    ->options([
                        'purchase_order' => 'Purchase Order',
                        'service_job' => 'Service Job',
                        'stock_adjustment' => 'Penyesuaian Stok',
                        'stock_transfer' => 'Transfer Stok',
                        'sale' => 'Penjualan',
                        'return' => 'Retur',
                    ]),
                Forms\Components\Textarea::make('notes')
                    ->label('Catatan')
                    ->maxLength(65535)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('type')
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'in' => 'success',
                        'out' => 'danger',
                        'adjustment' => 'warning',
                        'transfer' => 'info',
                        'purchase' => 'success',
                        'sale' => 'danger',
                        'return' => 'info',
                        'damage' => 'danger',
                        'loss' => 'danger',
                    }),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Jumlah')
                    ->numeric()
                    ->color(fn ($state) => $state >= 0 ? 'success' : 'danger'),
                Tables\Columns\TextColumn::make('previous_quantity')
                    ->label('Stok Sebelumnya')
                    ->numeric(),
                Tables\Columns\TextColumn::make('new_quantity')
                    ->label('Stok Baru')
                    ->numeric(),
                Tables\Columns\TextColumn::make('reference_number')
                    ->label('Referensi')
                    ->searchable(),
                Tables\Columns\TextColumn::make('reference_type')
                    ->label('Tipe Referensi')
                    ->badge(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Dibuat Oleh')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('notes')
                    ->label('Catatan')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->notes),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipe Pergerakan')
                    ->options([
                        'in' => 'Masuk',
                        'out' => 'Keluar',
                        'adjustment' => 'Penyesuaian',
                        'transfer' => 'Transfer',
                        'purchase' => 'Pembelian',
                        'sale' => 'Penjualan',
                        'return' => 'Retur',
                        'damage' => 'Kerusakan',
                        'loss' => 'Kehilangan',
                    ]),
                Tables\Filters\SelectFilter::make('reference_type')
                    ->label('Tipe Referensi')
                    ->options([
                        'purchase_order' => 'Purchase Order',
                        'service_job' => 'Service Job',
                        'stock_adjustment' => 'Penyesuaian Stok',
                        'stock_transfer' => 'Transfer Stok',
                        'sale' => 'Penjualan',
                        'return' => 'Retur',
                    ]),
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
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->hidden(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // No bulk actions for stock movements
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
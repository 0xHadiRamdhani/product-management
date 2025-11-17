<?php

namespace App\Filament\Resources\PurchaseOrderResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Item PO';
    protected static ?string $modelLabel = 'Item PO';
    protected static ?string $pluralModelLabel = 'Item PO';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('spare_part_id')
                    ->label('Spare Part')
                    ->relationship('sparePart', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, $set) {
                        if ($state) {
                            $sparePart = \App\Models\SparePart::find($state);
                            if ($sparePart) {
                                $set('unit_price', $sparePart->cost_price);
                                $set('unit', $sparePart->unit);
                            }
                        }
                    }),
                Forms\Components\TextInput::make('quantity')
                    ->label('Jumlah')
                    ->required()
                    ->numeric()
                    ->default(1)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, $set, $get) {
                        $unitPrice = $get('unit_price') ?? 0;
                        $set('total_price', $state * $unitPrice);
                    }),
                Forms\Components\TextInput::make('unit_price')
                    ->label('Harga Satuan')
                    ->required()
                    ->numeric()
                    ->prefix('Rp')
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, $set, $get) {
                        $quantity = $get('quantity') ?? 0;
                        $set('total_price', $state * $quantity);
                    }),
                Forms\Components\TextInput::make('total_price')
                    ->label('Total Harga')
                    ->required()
                    ->numeric()
                    ->prefix('Rp')
                    ->disabled()
                    ->dehydrated(false),
                Forms\Components\TextInput::make('unit')
                    ->label('Satuan')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('discount_percentage')
                    ->label('Diskon (%)')
                    ->numeric()
                    ->suffix('%')
                    ->default(0),
                Forms\Components\TextInput::make('tax_percentage')
                    ->label('Pajak (%)')
                    ->numeric()
                    ->suffix('%')
                    ->default(11),
                Forms\Components\Textarea::make('notes')
                    ->label('Catatan')
                    ->maxLength(65535)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('sparePart.name')
            ->columns([
                Tables\Columns\TextColumn::make('sparePart.sku')
                    ->label('SKU')
                    ->searchable(),
                Tables\Columns\TextColumn::make('sparePart.name')
                    ->label('Nama Spare Part')
                    ->searchable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Jumlah')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('unit')
                    ->label('Satuan'),
                Tables\Columns\TextColumn::make('unit_price')
                    ->label('Harga Satuan')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('discount_percentage')
                    ->label('Diskon')
                    ->numeric()
                    ->suffix('%'),
                Tables\Columns\TextColumn::make('tax_percentage')
                    ->label('Pajak')
                    ->numeric()
                    ->suffix('%'),
                Tables\Columns\TextColumn::make('total_price')
                    ->label('Total')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('received_quantity')
                    ->label('Diterima')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('pending_quantity')
                    ->label('Pending')
                    ->numeric()
                    ->getStateUsing(fn ($record) => $record->quantity - $record->received_quantity),
                Tables\Columns\TextColumn::make('notes')
                    ->label('Catatan')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->notes),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('spare_part')
                    ->label('Spare Part')
                    ->relationship('sparePart', 'name'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('receive')
                    ->label('Terima')
                    ->icon('heroicon-o-truck')
                    ->color('success')
                    ->visible(fn ($record) => $record->received_quantity < $record->quantity)
                    ->form([
                        Forms\Components\TextInput::make('quantity')
                            ->label('Jumlah Diterima')
                            ->required()
                            ->numeric()
                            ->maxValue(fn ($record) => $record->quantity - $record->received_quantity),
                        Forms\Components\Textarea::make('notes')
                            ->label('Catatan Penerimaan')
                            ->maxLength(65535),
                    ])
                    ->action(function ($record, $data) {
                        $record->receive($data['quantity'], $data['notes'] ?? '');
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
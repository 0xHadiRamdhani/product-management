<?php

namespace App\Filament\Resources\CategoryResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SparePartsRelationManager extends RelationManager
{
    protected static string $relationship = 'spareParts';

    protected static ?string $title = 'Spare Parts';
    protected static ?string $modelLabel = 'Spare Part';
    protected static ?string $pluralModelLabel = 'Spare Parts';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('sku')
                    ->label('SKU')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('name')
                    ->label('Nama')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('part_number')
                    ->label('Nomor Part')
                    ->maxLength(255),
                Forms\Components\TextInput::make('barcode')
                    ->label('Barcode')
                    ->maxLength(255),
                Forms\Components\TextInput::make('brand')
                    ->label('Merek')
                    ->maxLength(255),
                Forms\Components\TextInput::make('model')
                    ->label('Model')
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->label('Deskripsi')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('cost_price')
                    ->label('Harga Pokok')
                    ->numeric()
                    ->prefix('Rp'),
                Forms\Components\TextInput::make('selling_price')
                    ->label('Harga Jual')
                    ->numeric()
                    ->prefix('Rp'),
                Forms\Components\TextInput::make('min_stock_level')
                    ->label('Stok Minimum')
                    ->numeric(),
                Forms\Components\TextInput::make('reorder_point')
                    ->label('Titik Pemesanan Ulang')
                    ->numeric(),
                Forms\Components\Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true),
            ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable(),
                Tables\Columns\TextColumn::make('brand')
                    ->label('Merek')
                    ->searchable(),
                Tables\Columns\TextColumn::make('model')
                    ->label('Model')
                    ->searchable(),
                Tables\Columns\TextColumn::make('cost_price')
                    ->label('Harga Pokok')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('selling_price')
                    ->label('Harga Jual')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('min_stock_level')
                    ->label('Stok Min')
                    ->numeric(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status Aktif'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
<?php

namespace App\Filament\Resources\SparePartResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StockRelationManager extends RelationManager
{
    protected static string $relationship = 'stocks';

    protected static ?string $title = 'Stok';
    protected static ?string $modelLabel = 'Stok';
    protected static ?string $pluralModelLabel = 'Stok';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('location_id')
                    ->label('Lokasi')
                    ->relationship('location', 'name')
                    ->required(),
                Forms\Components\TextInput::make('quantity')
                    ->label('Jumlah')
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
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('location.name')
            ->columns([
                Tables\Columns\TextColumn::make('location.name')
                    ->label('Lokasi')
                    ->searchable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Jumlah')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('reserved_quantity')
                    ->label('Dipesan')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('available_quantity')
                    ->label('Tersedia')
                    ->numeric()
                    ->getStateUsing(fn ($record) => $record->quantity - $record->reserved_quantity),
                Tables\Columns\TextColumn::make('min_stock_level')
                    ->label('Min')
                    ->numeric(),
                Tables\Columns\TextColumn::make('max_stock_level')
                    ->label('Max')
                    ->numeric(),
                Tables\Columns\TextColumn::make('reorder_point')
                    ->label('Reorder')
                    ->numeric(),
                Tables\Columns\TextColumn::make('last_updated')
                    ->label('Terakhir Diperbarui')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('location')
                    ->label('Lokasi')
                    ->relationship('location', 'name'),
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
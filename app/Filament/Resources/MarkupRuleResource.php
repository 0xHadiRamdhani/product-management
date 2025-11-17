<?php

namespace App\Filament\Resources;

use App\Models\MarkupRule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MarkupRuleResource extends Resource
{
    protected static ?string $model = MarkupRule::class;

    protected static ?string $navigationIcon = 'heroicon-o-calculator';
    protected static ?string $navigationGroup = 'Pengaturan';
    protected static ?string $navigationLabel = 'Aturan Markup';
    protected static ?string $modelLabel = 'Aturan Markup';
    protected static ?string $pluralModelLabel = 'Aturan Markup';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Aturan')->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nama Aturan')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\Textarea::make('description')
                        ->label('Deskripsi')
                        ->maxLength(65535)
                        ->columnSpanFull(),
                ]),

                Forms\Components\Section::make('Kondisi')->schema([
                    Forms\Components\Select::make('category_id')
                        ->label('Kategori')
                        ->relationship('category', 'name')
                        ->searchable()
                        ->preload()
                        ->nullable(),
                    Forms\Components\Select::make('supplier_id')
                        ->label('Supplier')
                        ->relationship('supplier', 'name')
                        ->searchable()
                        ->preload()
                        ->nullable(),
                    Forms\Components\TextInput::make('min_cost_price')
                        ->label('Harga Pokok Minimum')
                        ->numeric()
                        ->prefix('Rp'),
                    Forms\Components\TextInput::make('max_cost_price')
                        ->label('Harga Pokok Maksimum')
                        ->numeric()
                        ->prefix('Rp'),
                    Forms\Components\TextInput::make('min_quantity')
                        ->label('Jumlah Minimum')
                        ->numeric(),
                    Forms\Components\TextInput::make('max_quantity')
                        ->label('Jumlah Maksimum')
                        ->numeric(),
                ])->columns(2),

                Forms\Components\Section::make('Markup')->schema([
                    Forms\Components\TextInput::make('markup_percentage')
                        ->label('Persentase Markup')
                        ->required()
                        ->numeric()
                        ->suffix('%')
                        ->default(20),
                    Forms\Components\TextInput::make('min_markup_percentage')
                        ->label('Markup Minimum (%)')
                        ->numeric()
                        ->suffix('%'),
                    Forms\Components\TextInput::make('max_markup_percentage')
                        ->label('Markup Maksimum (%)')
                        ->numeric()
                        ->suffix('%'),
                    Forms\Components\TextInput::make('fixed_markup_amount')
                        ->label('Jumlah Markup Tetap')
                        ->numeric()
                        ->prefix('Rp'),
                ])->columns(2),

                Forms\Components\Section::make('Pengaturan')->schema([
                    Forms\Components\Toggle::make('is_active')
                        ->label('Aktif')
                        ->default(true),
                    Forms\Components\TextInput::make('priority')
                        ->label('Prioritas')
                        ->numeric()
                        ->default(0)
                        ->helperText('Semakin tinggi nilai, semakin tinggi prioritas'),
                    Forms\Components\DatePicker::make('valid_from')
                        ->label('Berlaku Dari')
                        ->default(now()),
                    Forms\Components\DatePicker::make('valid_until')
                        ->label('Berlaku Sampai'),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Aturan')
                    ->searchable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Kategori')
                    ->searchable(),
                Tables\Columns\TextColumn::make('supplier.name')
                    ->label('Supplier')
                    ->searchable(),
                Tables\Columns\TextColumn::make('markup_percentage')
                    ->label('Markup (%)')
                    ->numeric()
                    ->suffix('%')
                    ->sortable(),
                Tables\Columns\TextColumn::make('min_cost_price')
                    ->label('Min Harga')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('max_cost_price')
                    ->label('Max Harga')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean(),
                Tables\Columns\TextColumn::make('priority')
                    ->label('Prioritas')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('valid_from')
                    ->label('Berlaku Dari')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('valid_until')
                    ->label('Berlaku Sampai')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('Kategori')
                    ->relationship('category', 'name'),
                Tables\Filters\SelectFilter::make('supplier')
                    ->label('Supplier')
                    ->relationship('supplier', 'name'),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status Aktif'),
                Tables\Filters\Filter::make('valid_date')
                    ->label('Tanggal Berlaku')
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
                                fn (Builder $query, $date): Builder => $query->whereDate('valid_from', '<=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('valid_until', '>=', $date),
                            );
                    }),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('apply')
                    ->label('Terapkan')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->action(function ($record) {
                        $record->applyToSpareParts();
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
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMarkupRules::route('/'),
            'create' => Pages\CreateMarkupRule::route('/create'),
            'edit' => Pages\EditMarkupRule::route('/{record}/edit'),
            'view' => Pages\ViewMarkupRule::route('/{record}'),
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
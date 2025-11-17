<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SparePartResource\Pages;
use App\Filament\Resources\SparePartResource\RelationManagers;
use App\Models\SparePart;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SparePartResource extends Resource
{
    protected static ?string $model = SparePart::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';
    protected static ?string $navigationGroup = 'Master Data';
    protected static ?string $navigationLabel = 'Spare Part';
    protected static ?string $modelLabel = 'Spare Part';
    protected static ?string $pluralModelLabel = 'Spare Part';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Dasar')
                    ->schema([
                        Forms\Components\TextInput::make('sku')
                            ->label('SKU')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Spare Part')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Forms\Components\Select::make('category_id')
                            ->label('Kategori')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\TextInput::make('brand')
                            ->label('Merek')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('model')
                            ->label('Model')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('part_number')
                            ->label('Nomor Part')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('barcode')
                            ->label('Barcode')
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                    ])->columns(2),

                Forms\Components\Section::make('Deskripsi')
                    ->schema([
                        Forms\Components\Textarea::make('description')
                            ->label('Deskripsi')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Harga & Stok')
                    ->schema([
                        Forms\Components\TextInput::make('cost_price')
                            ->label('Harga Pokok')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, $set, $get) {
                                $markup = $get('markup_percentage');
                                if ($markup > 0) {
                                    $sellingPrice = $state * (1 + ($markup / 100));
                                    $set('selling_price', round($sellingPrice, 2));
                                }
                            }),
                        Forms\Components\TextInput::make('selling_price')
                            ->label('Harga Jual')
                            ->required()
                            ->numeric()
                            ->prefix('Rp'),
                        Forms\Components\TextInput::make('markup_percentage')
                            ->label('Markup (%)')
                            ->numeric()
                            ->suffix('%')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, $set, $get) {
                                $costPrice = $get('cost_price');
                                if ($costPrice > 0) {
                                    $sellingPrice = $costPrice * (1 + ($state / 100));
                                    $set('selling_price', round($sellingPrice, 2));
                                }
                            }),
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
                    ])->columns(3),

                Forms\Components\Section::make('Spesifikasi')
                    ->schema([
                        Forms\Components\TextInput::make('unit')
                            ->label('Satuan')
                            ->maxLength(255)
                            ->default('pcs'),
                        Forms\Components\TextInput::make('weight')
                            ->label('Berat (kg)')
                            ->numeric()
                            ->suffix('kg'),
                        Forms\Components\TextInput::make('dimensions')
                            ->label('Dimensi')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('material')
                            ->label('Material')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('color')
                            ->label('Warna')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('warranty_period')
                            ->label('Periode Garansi')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('compatibility')
                            ->label('Kompatibilitas')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('manufacturer')
                            ->label('Pabrikan')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('country_of_origin')
                            ->label('Negara Asal')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('hs_code')
                            ->label('Kode HS')
                            ->maxLength(255),
                    ])->columns(3),

                Forms\Components\Section::make('Pajak')
                    ->schema([
                        Forms\Components\Select::make('tax_type')
                            ->label('Jenis Pajak')
                            ->options([
                                'none' => 'Tidak Ada',
                                'ppn' => 'PPN',
                                'ppnbm' => 'PPnBM',
                            ])
                            ->default('ppn'),
                        Forms\Components\TextInput::make('tax_percentage')
                            ->label('Persentase Pajak')
                            ->numeric()
                            ->suffix('%')
                            ->default(11),
                    ])->columns(2),

                Forms\Components\Section::make('Pengaturan')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true),
                        Forms\Components\Toggle::make('is_taxable')
                            ->label('Kena Pajak')
                            ->default(true),
                        Forms\Components\Toggle::make('is_discountable')
                            ->label('Bisa Diskon')
                            ->default(true),
                    ])->columns(3),

                Forms\Components\Section::make('Gambar')
                    ->schema([
                        Forms\Components\FileUpload::make('image')
                            ->label('Gambar Utama')
                            ->image()
                            ->directory('spare-parts')
                            ->maxSize(2048),
                        Forms\Components\FileUpload::make('gallery')
                            ->label('Galeri')
                            ->image()
                            ->directory('spare-parts/gallery')
                            ->multiple()
                            ->maxSize(2048),
                    ])->columns(2),

                Forms\Components\Section::make('Spesifikasi Tambahan')
                    ->schema([
                        Forms\Components\KeyValue::make('specifications')
                            ->label('Spesifikasi')
                            ->keyLabel('Nama')
                            ->valueLabel('Nilai'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->label('Gambar')
                    ->circular(),
                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Kategori')
                    ->searchable()
                    ->sortable(),
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
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status Aktif'),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
                Tables\Actions\ViewAction::make(),
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
            RelationManagers\StockRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSpareParts::route('/'),
            'create' => Pages\CreateSparePart::route('/create'),
            'edit' => Pages\EditSparePart::route('/{record}/edit'),
            'view' => Pages\ViewSparePart::route('/{record}'),
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
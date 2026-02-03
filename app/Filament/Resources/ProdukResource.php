<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProdukResource\Pages;
use App\Filament\Resources\ProdukResource\RelationManagers;
use App\Models\Produk;
use FFI;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\TagsInput;
use Filament\Resources\Components\Tab;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Factories\Relationship;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

use function Symfony\Component\String\s;

class ProdukResource extends Resource
{
    protected static ?string $model = Produk::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([

                /* PRODUCT INFORMATION */
                Forms\Components\Section::make('Product Information')
                    ->schema([

                        Forms\Components\Grid::make(2)
                            ->schema([

                                Forms\Components\TextInput::make('name')
                                    ->label('Nama Produk')
                                    ->required()
                                    ->unique(
                                        table: Produk::class,
                                        column: 'name',
                                        ignoreRecord: true
                                    )
                                    ->live(true)
                                    ->afterStateUpdated(
                                        fn($state, callable $set) =>
                                        $set('slug', Str::slug($state))
                                    ),

                                Forms\Components\TextInput::make('price')
                                    ->label('Price')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->required(),
                            ]),

                        Forms\Components\Hidden::make('slug'),

                        Forms\Components\Grid::make(2)
                            ->schema([

                                Forms\Components\FileUpload::make('thumbnail')
                                    ->label('Thumbnail')
                                    ->image()
                                    ->directory('produks')
                                    ->disk('public')
                                    ->required(),

                                Forms\Components\Repeater::make('photos')
                                    ->label('Photos')
                                    ->relationship()
                                    ->schema([
                                        Forms\Components\FileUpload::make('photo')
                                            ->image()
                                            ->directory('produks/photos')
                                            ->disk('public')
                                            ->required(),
                                    ])
                                    ->addActionLabel('Tambah foto')
                                    ->collapsible(),
                            ]),

                        Forms\Components\Repeater::make('Sizes')
                            ->relationship()
                            ->schema([
                                // ukuran produk
                                Forms\Components\TextInput::make('size')
                                    ->label('Ukuran')
                                    ->required(),
                            ])
                            ->addActionLabel('Tambah ukuran produk lainnya'),
                    ])
                    ->columns(2),

                /* INFORMASI TAMBAHAN */
                Forms\Components\Section::make('Informasi Tambahan')
                    ->schema([

                        Forms\Components\Textarea::make('about')
                            ->label('Tentang Produk')
                            ->rows(5)
                            ->required(),

                        Forms\Components\Grid::make(2)
                            ->schema([

                                Forms\Components\Select::make('is_popular')
                                    ->label('Is popular?')
                                    ->options([
                                        1 => 'Populer',
                                        0 => 'Tidak',
                                    ])
                                    ->required(),

                                Forms\Components\TextInput::make('stock')
                                    ->label('Stock')
                                    ->numeric()
                                    ->prefix('pcs')
                                    ->required(),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([

                                Forms\Components\Select::make('category_id')
                                    ->label('Category')
                                    ->relationship('category', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required(),

                                Forms\Components\Select::make('brand_id')
                                    ->label('Brand')
                                    ->relationship('brand', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('thumbnail')
                    ->disk('public')
                    ->circular(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Produk')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('price')
                    ->sortable()
                    ->label('Harga')
                    ->money('IDR'),

                Tables\Columns\TextColumn::make('category.name')
                    ->sortable()
                    ->label('Kategori'),

                Tables\Columns\TextColumn::make('brand.name')
                    ->sortable()
                    ->label('Merek'),

                Tables\Columns\TextColumn::make('stock')
                    ->sortable()
                    ->suffix(' pcs')
                    ->label('Stok'),

                Tables\Columns\IconColumn::make('is_popular')
                    ->sortable()
                    ->label('Populer')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make()->label('Sampah'),
                Tables\Filters\SelectFilter::make('category')
                    ->relationship('category', 'name'),

                Tables\Filters\SelectFilter::make('brand_id')
                    ->relationship('brand', 'name'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),

            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    // Soft Deletes Restore & delete (permanent)
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProduks::route('/'),
            'create' => Pages\CreateProduk::route('/create'),
            'edit' => Pages\EditProduk::route('/{record}/edit'),
        ];
    }

    // Soft delete functionality
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}

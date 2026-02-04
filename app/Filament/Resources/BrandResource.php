<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BrandResource\Pages;
use App\Filament\Resources\BrandResource\RelationManagers;
use App\Models\Brand;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Components\Tab;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

/**
 * Brand Resource Resource Filament untuk mengelola data Brand(Create, Read, Update, Delete + Soft Delete)
 */

class BrandResource extends Resource
{
    protected static ?string $model = Brand::class;

    protected static ?string $navigationIcon = 'heroicon-o-star';

    /**
     * Konfigurasi form untuk membuat dan mengedit data Brand
     */

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                /** Input nama brand */
                Forms\Components\TextInput::make('name')
                    ->label('Nama Brand') //Label untuk input nama brand
                    ->required() //Wajib diisi
                    ->unique(ignoreRecord: true) //Nama brand jangan duplikat
                    ->rules([
                        'min:5', //Minimal 5 karakter
                        'max:35' //Maksimal 35 karakter
                    ])
                    ->validationMessages([
                        'unique' => 'Nama brand sudah digunakan',
                        'min' => 'Nama brand terlalu pendek (min 5 karakter)',
                        'max' => 'Nama brand terlalu panjang (max 40 karakter)'
                    ]),

                /** Input upload logo brand */
                Forms\Components\FileUpload::make('logo')
                    ->image() //Hanya file gambar yang diizinkan
                    ->directory('brands') //Direktori penyimpanan file
                    ->maxSize(1024) //Maksimal ukuran file 1MB
                    ->required(), //Wajib diisi
            ]);
    }

    /* Konfigurasi tabel untuk menampilkan data Brand beserta fitur pencarian, filter, aksi, dan aksi massal
     */

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                /** Kolom nama brand yang dapat dicari */
                Tables\Columns\TextColumn::make('name')->searchable(), // Kolom nama brand
                Tables\Columns\ImageColumn::make('logo')->square(), // Kolom logo brand
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make()->label('Sampah'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(), // Edit data brand
                Tables\Actions\DeleteAction::make(), // Hapus data brand
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(), // Soft Delete banyak data
                    // Soft Deletes Restore & delete (permanent)
                    Tables\Actions\ForceDeleteBulkAction::make(), // Hapus permanen
                    Tables\Actions\RestoreBulkAction::make(), // Restore data yang dihapus
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
            'index' => Pages\ListBrands::route('/'), // Halaman list brand
            'create' => Pages\CreateBrand::route('/create'), // Halaman buat brand
            'edit' => Pages\EditBrand::route('/{record}/edit'), // Halaman edit brand
        ];
    }

    /* Mengaktifkan fitur Soft Delete agar data yang dihapus tetap bisa ditampilkan
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}

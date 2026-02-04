<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Filament\Resources\CategoryResource\RelationManagers;
use App\Models\Category;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

/**
* Category Resource Resource Filament untuk mengelola data Category(Create, Read, Update, Delete + Soft Delete)
*/
class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    /**
     * Konfigurasi form untuk membuat dan mengedit data Category
     */
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                /** Input nama category */
                Forms\Components\TextInput::make('name')
                    ->label('Nama Category') //Label untuk input nama category
                    ->required() //Wajib diisi
                    ->unique(ignoreRecord: true) //Nama category jangan duplikat
                    ->rules([
                        'min:5', //Minimal 5 karakter
                        'max:20' //Maksimal 20 karakter
                    ])
                    ->validationMessages([
                        'unique' => 'Nama category sudah digunakan',
                        'min' => 'Nama category terlalu pendek (min 5 karakter)',
                        'max' => 'Nama category terlalu panjang (max 20 karakter)'
                    ]),
                /** Input upload icon category */
                Forms\Components\FileUpload::make('icon')
                    ->image() //Hanya file gambar yang diizinkan
                    ->directory('categories') //Direktori penyimpanan file
                    ->maxSize(1024) //Maksimal ukuran file 1MB
                    ->required() //Wajib diisi
                    ->nullable(), //Boleh kosong
            ]);
    }

    /**
     * Konfigurasi tabel untuk menampilkan data Category beserta fitur pencarian, filter, aksi, dan aksi massal
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                /** Kolom nama category yang dapat dicari */
                Tables\Columns\TextColumn::make('name')->searchable(), // kolom search
                Tables\Columns\ImageColumn::make('icon')->circular(), // kolom icon
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make()->label('Sampah'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(), // Edit data category
                Tables\Actions\DeleteAction::make(), // Hapus data category
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(), // Soft delete banyak data
                    // Soft Deletes Restore & delete (permanent)
                    Tables\Actions\ForceDeleteBulkAction::make(), // Hapus data permanen
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
            'index' => Pages\ListCategories::route('/'), // Halaman list category
            'create' => Pages\CreateCategory::route('/create'), // Halaman buat category
            'edit' => Pages\EditCategory::route('/{record}/edit'), // Halaman edit category
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

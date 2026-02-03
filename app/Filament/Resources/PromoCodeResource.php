<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PromoCodeResource\Pages;
use App\Filament\Resources\PromoCodeResource\RelationManagers;
use App\Models\PromoCode;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PromoCodeResource extends Resource
{
    protected static ?string $model = PromoCode::class;

    protected static ?string $navigationIcon = 'heroicon-o-gift';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->label('Promo Code')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->rules([
                                'min:8',
                                'max:20'
                            ])
                            ->validationMessages([
                                'unique' => 'Kode promo sudah digunakan',
                                'min' => 'Nama terlalu pendek (min 8 karakter)',
                                'max' => 'Nama terlalu panjang (max 20 karakter)'
                            ]),

                        Forms\Components\TextInput::make('discount_amount')
                            ->label('Discount Amount')
                            ->required()
                            ->prefix('IDR ')
                            ->rules([
                                'numeric',
                                'min:10000'
                            ])
                            ->validationMessages([
                                'numeric' => 'Discount amount hanya bisa diisi oleh angka',
                                'min' => 'Nominal terlalu kecil (min Rp.10.000)'
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Promo Code')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('discount_amount')
                    ->label('Discount Amount')
                    ->money('IDR', true)
                    ->sortable(),

                    Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc') 
            ->searchable()
            ->filters([
                Tables\Filters\TrashedFilter::make()->label('Sampah'),
            ])
            ->actions([
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
            'index' => Pages\ListPromoCodes::route('/'),
            'create' => Pages\CreatePromoCode::route('/create'),
            'edit' => Pages\EditPromoCode::route('/{record}/edit'),
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

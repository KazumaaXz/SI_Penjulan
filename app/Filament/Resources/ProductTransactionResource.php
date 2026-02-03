<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductTransactionResource\Pages;
use App\Models\ProductTransaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Components\Tab;
use Filament\Resources\Resource;
use App\Models\Produk;
use App\Models\PromoCode;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProductTransactionResource extends Resource
{
    protected static ?string $model = ProductTransaction::class;
    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    public static function generateBookingTrxId(): string
    {
        return 'TRX-' . now()->format('Ymd') . '-' . strtoupper(str()->random(4));
    }

    protected static function calculateAmounts(callable $set, callable $get): void
    {
        $produkId = $get('produk_id');
        $qty = (int) $get('quantity');
        $promoId = $get('promo_code_id');

        if (!$produkId || $qty <= 0) {
            $set('sub_total_amount', 0);
            $set('grand_total_amount', 0);
            return;
        }

        $produk = Produk::find($produkId);
        if (!$produk)
            return;

        $subTotal = $produk->price * $qty;

        $discount = 0;
        if ($promoId) {
            $promo = PromoCode::find($promoId);
            $discount = $promo?->discount_amount ?? 0;
        }

        $grandTotal = max($subTotal - $discount, 0);

        $set('sub_total_amount', $subTotal);
        $set('grand_total_amount', $grandTotal);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([

            /* DATA PEMBELI */
            Forms\Components\Section::make('Data Pembeli')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nama Pembeli')
                        ->columnSpanFull()
                        ->maxLength(255)
                        ->required(),

                    Forms\Components\TextInput::make('email')
                        ->required()
                        ->email()
                        ->maxLength(255)
                        ->label('Email Pengguna'),

                    Forms\Components\TextInput::make('phone')
                        ->label('Nomor Telepon')
                        ->required()
                        ->numeric()
                        ->maxLength(15)
                        ->extraInputAttributes([
                            'inputmode' => 'numeric',
                            'oninput' => "this.value = this.value.replace(/[^0-9]/g, '')",
                        ])
                        ->validationMessages([
                            'required' => 'Nomor telepon wajib diisi!',
                            'numeric' => 'Nomor telepon hanya boleh berisi angka',
                        ]),

                    Forms\Components\TextInput::make('booking_trx_id')
                        ->label('Booking Transaction ID')
                        ->default(fn() => self::generateBookingTrxId())
                        ->disabled()
                        ->dehydrated()
                        ->unique(ignoreRecord: true),

                ])
                ->columns(2),

            /* ALAMAT */
            Forms\Components\Section::make('Alamat Pengiriman')
                ->schema([
                    Forms\Components\Textarea::make('address')
                        ->required()
                        ->label('Alamat'),

                    Forms\Components\TextInput::make('city')
                        ->label('Kota')
                        ->required(),

                    Forms\Components\TextInput::make('post_code')
                        ->label('Kode Pos')
                        ->numeric()
                        ->required(),
                ])
                ->columns(2),

            /* PRODUK & PEMBAYARAN */
            Forms\Components\Section::make('Produk & Pembayaran')
                ->schema([
                    Forms\Components\Select::make('produk_id')
                        ->relationship('produk', 'name')
                        ->label('Produk')
                        ->searchable()
                        ->preload()
                        ->reactive()
                        ->afterStateUpdated(
                            fn($state, $set, $get) =>
                            self::calculateAmounts($set, $get)
                        )
                        ->required(),

                    Forms\Components\Select::make('produk_size')
                        ->label('Ukuran')
                        ->options(function (callable $get) {
                            $productId = $get('produk_id');

                            if (!$productId) {
                                return [];
                            }

                            return \App\Models\ProdukSize::where('produk_id', $productId)
                                ->pluck('size', 'id');
                        })
                        ->required()
                        ->disabled(fn(callable $get) => !$get('produk_id')),

                    Forms\Components\TextInput::make('quantity')
                        ->numeric()
                        ->reactive()
                        ->prefix('QTY')
                        ->placeholder('Masukkan jumlah')
                        ->afterStateUpdated(
                            fn($state, $set, $get) =>
                            self::calculateAmounts($set, $get)
                        )
                        ->required(),


                    Forms\Components\TextInput::make('sub_total_amount')
                        ->numeric()
                        ->prefix('Rp')
                        ->required()
                        ->label('Sub Total')
                        ->disabled()
                        ->dehydrated(),

                    Forms\Components\TextInput::make('grand_total_amount')
                        ->numeric()
                        ->prefix('Rp')
                        ->required()
                        ->label('Grand Total')
                        ->disabled()
                        ->dehydrated(),

                    Forms\Components\Select::make('promo_code_id')
                        ->relationship('promoCode', 'code')
                        ->reactive()
                        ->afterStateUpdated(
                            fn($state, $set, $get) =>
                            self::calculateAmounts($set, $get)
                        )
                        ->label('Kode Promo'),

                    Forms\Components\FileUpload::make('proof')
                        ->image()
                        ->directory('products/proof')
                        ->maxsize(1024)
                        ->disk('public')
                        ->required()
                        ->columnSpanFull()
                        ->label('Bukti Pembayaran'),

                    Forms\Components\Select::make('is_paid')
                        ->required()
                        ->label('Sudah Dibayar?')
                        ->options([
                            1 => 'Sudah Dibayar',
                            0 => 'Belum Dibayar',
                        ]),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('booking_trx_id')
                    ->label('Booking ID')
                    ->searchable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Pembeli')
                    ->searchable(),

                Tables\Columns\TextColumn::make('produk.name')
                    ->label('Produk'),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('Qty')
                    ->sortable(),

                Tables\Columns\TextColumn::make('sub_total_amount')
                    ->label('Sub Total')
                    ->money('IDR'),

                Tables\Columns\TextColumn::make('grand_total_amount')
                    ->label('Grand Total')
                    ->money('IDR'),

                Tables\Columns\IconColumn::make('is_paid')
                    ->label('Status')
                    ->boolean(),

                Tables\Columns\ImageColumn::make('proof')
                    ->label('Bukti Pembayaran')
                    ->disk('public')
                    ->height(50)
                    ->width(50)
                    ->square()
                    ->openUrlInNewTab(),

            ])
            ->filters([
                Tables\Filters\TrashedFilter::make()->label('Sampah'),

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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductTransactions::route('/'),
            'create' => Pages\CreateProductTransaction::route('/create'),
            'edit' => Pages\EditProductTransaction::route('/{record}/edit'),
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

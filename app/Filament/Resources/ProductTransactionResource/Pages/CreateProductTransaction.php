<?php

namespace App\Filament\Resources\ProductTransactionResource\Pages;

use App\Filament\Resources\ProductTransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateProductTransaction extends CreateRecord
{
    protected static string $resource = ProductTransactionResource::class;

    protected function afterCreate(): void
    {
        $transaction = $this->record;

        $product = \App\Models\Produk::find($transaction->produk_id);

        if (!$product) {
            return;
        }

        if ($product->stock < $transaction->quantity) {
            throw new \Exception('Stok produk tidak mencukupi');
        }

        $product->decrement('stock', $transaction->quantity);
    }

}

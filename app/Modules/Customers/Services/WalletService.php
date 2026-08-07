<?php

namespace App\Modules\Customers\Services;

use App\Modules\Customers\Models\WalletAccount;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class WalletService
{
    public function credit(int $customerId, float $amount, ?string $reference = null, ?string $description = null): void
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Credit amount must be positive.');
        }

        DB::transaction(function () use ($customerId, $amount, $reference, $description) {
            $account = WalletAccount::firstOrCreate(['customer_id' => $customerId]);
            $locked = WalletAccount::whereKey($account->id)->lockForUpdate()->first();

            $newBalance = $locked->balance + $amount;
            $locked->update(['balance' => $newBalance]);

            $locked->transactions()->create([
                'type' => 'credit',
                'amount' => $amount,
                'balance_after' => $newBalance,
                'reference' => $reference,
                'description' => $description,
            ]);
        });
    }

    public function debit(int $customerId, float $amount, ?string $reference = null, ?string $description = null): void
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Debit amount must be positive.');
        }

        DB::transaction(function () use ($customerId, $amount, $reference, $description) {
            $account = WalletAccount::firstOrCreate(['customer_id' => $customerId]);
            $locked = WalletAccount::whereKey($account->id)->lockForUpdate()->first();

            if ($locked->balance < $amount) {
                throw new InvalidArgumentException('Insufficient wallet balance.');
            }

            $newBalance = $locked->balance - $amount;
            $locked->update(['balance' => $newBalance]);

            $locked->transactions()->create([
                'type' => 'debit',
                'amount' => $amount,
                'balance_after' => $newBalance,
                'reference' => $reference,
                'description' => $description,
            ]);
        });
    }
}

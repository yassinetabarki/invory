<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Inventory\StockLedgerVerifier;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('invory:stock:verify')]
#[Description('Recompute every material\'s stock from its ledger and report drift (read-only; exits 1 when a balance drifts)')]
final class VerifyStockLedgerCommand extends Command
{
    public function handle(StockLedgerVerifier $verifier): int
    {
        $result = $verifier->verify();

        if ($result['drifts'] === []) {
            $this->info("Checked {$result['checked']} materials: every balance matches its ledger.");

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'SKU', 'Name', 'Stored', 'Ledger', 'Drift'],
            array_map(static fn (array $drift): array => [
                $drift['material_id'], $drift['sku'], $drift['name'], $drift['stored'], $drift['ledger'], $drift['drift'],
            ], $result['drifts']),
        );
        $this->error(count($result['drifts'])." of {$result['checked']} materials drift from their ledger.");

        return self::FAILURE;
    }
}

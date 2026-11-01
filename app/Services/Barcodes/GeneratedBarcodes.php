<?php

declare(strict_types=1);

namespace App\Services\Barcodes;

use App\Domain\Barcodes\VariantNames;

/**
 * What a Generate did (INV-8-c, AC-3, ADR-0020) and the sentence that tells it. The wording depends on how many variants were ASKED
 * for, not on which button asked: one asked reads "Barcode generated for Light wash S.", several read "4 barcodes generated for Denim
 * Jacket.", and variants that already had a number change it to "…ready for…, N new, M already used by PRD-JKT-002." (the SKU-list
 * rule of {@see VariantNames::skus()}; with no non-deleted product holding them, " by …" is dropped).
 */
final readonly class GeneratedBarcodes
{
    /**
     * @param  string  $product  the product's name as saved
     * @param  int  $asked  the variants asked for, at least 1
     * @param  int  $created  the variants that got a new number
     * @param  string  $variant  the first variant asked for, written by {@see VariantNames::one()} (read when only one was asked)
     * @param  list<string>  $holders  the SKUs of the other non-deleted products that show the numbers already used, each once, in SKU order
     */
    public function __construct(
        public string $product,
        public int $asked,
        public int $created,
        public string $variant,
        public array $holders,
    ) {}

    /** The variants that already had a number (their name, colour and size was already made). */
    public function alreadyUsed(): int
    {
        return $this->asked - $this->created;
    }

    /** The toast. */
    public function message(): string
    {
        $by = $this->holders === [] ? '' : ' by '.VariantNames::skus($this->holders);

        if ($this->asked === 1) {
            return $this->created === 1
                ? "Barcode generated for {$this->variant}."
                : "Barcode ready for {$this->variant}: already used{$by}.";
        }

        return match (true) {
            $this->created === $this->asked => "{$this->asked} barcodes generated for {$this->product}.",
            $this->created === 0 => "{$this->asked} barcodes ready for {$this->product}: all already used{$by}.",
            default => "{$this->asked} barcodes ready for {$this->product}: {$this->created} new, {$this->alreadyUsed()} already used{$by}.",
        };
    }
}

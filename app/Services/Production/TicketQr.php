<?php

declare(strict_types=1);

namespace App\Services\Production;

use App\Models\ProductionBatch;
use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;

/**
 * The QR code on a work ticket (INV-15-b, AC-1, AC-5, ADR-0024 decision 2): the link `/scan/t/<code>`, encoded with bacon/bacon-qr-code
 * (error correction M) and returned as the module count and one SVG path of the dark modules ("M4 4h1v1h-1z…"), drawn by the page in a
 * `viewBox="0 0 size size"`. The 4-module quiet zone is part of the size. Pure: no HTML ever goes to the page.
 */
final class TicketQr
{
    /** The white margin around the code, in modules, as the QR standard asks. */
    public const QUIET_ZONE = 4;

    /** The link a ticket's QR code holds: absolute, so a phone's own camera app opens it. */
    public function link(ProductionBatch $batch): string
    {
        return route('scan.ticket', ['code' => $batch->ticket_code]);
    }

    /** @return array{size: int, path: string} */
    public function of(ProductionBatch $batch): array
    {
        return $this->encode($this->link($batch));
    }

    /** @return array{size: int, path: string} */
    public function encode(string $content): array
    {
        $matrix = Encoder::encode($content, ErrorCorrectionLevel::M(), Encoder::DEFAULT_BYTE_MODE_ENCODING, null, false)->getMatrix();
        $width = $matrix->getWidth();
        $path = '';

        for ($y = 0; $y < $width; $y++) {
            // One rectangle per run of dark modules on a row keeps the path short.
            $x = 0;

            while ($x < $width) {
                if ($matrix->get($x, $y) !== 1) {
                    $x++;

                    continue;
                }

                $start = $x;

                while ($x < $width && $matrix->get($x, $y) === 1) {
                    $x++;
                }

                $path .= sprintf('M%d %dh%dv1h-%dz', $start + self::QUIET_ZONE, $y + self::QUIET_ZONE, $x - $start, $x - $start);
            }
        }

        return ['size' => $width + 2 * self::QUIET_ZONE, 'path' => $path];
    }
}

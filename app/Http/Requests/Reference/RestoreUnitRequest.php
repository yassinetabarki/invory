<?php

declare(strict_types=1);

namespace App\Http\Requests\Reference;

class RestoreUnitRequest extends RestoreReferenceRequest
{
    protected function table(): string
    {
        return 'units';
    }

    protected function parameter(): string
    {
        return 'unit';
    }

    protected function noun(): string
    {
        return 'unit';
    }
}

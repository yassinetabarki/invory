<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| Public storage accessibility (deployment check)
|--------------------------------------------------------------------------
|
| `php artisan storage:link` makes `public/storage` point at `storage/app/public` (an NTFS junction on Windows,
| a symlink on Linux). `composer setup` and the CI pgsql job run it. These tests only verify that the resulting
| path is a readable directory and serves the files written to the public disk.
*/

it('has public/storage set up as a readable directory', function () {
    $path = public_path('storage');

    expect(is_dir($path))->toBeTrue(
        'public/storage is not a readable directory. Run `php artisan storage:link`.'
    );
});

it('can access a file stored on the public disk through public/storage', function () {
    $disk = Storage::disk('public');
    $path = '_test/storage-setup-check.txt';

    try {
        $disk->put($path, 'ok');

        $viaPublic = public_path("storage/{$path}");

        expect(file_exists($viaPublic))->toBeTrue(
            'A file written to the public disk is not readable at public/storage. Run `php artisan storage:link`.'
        )
            ->and(file_get_contents($viaPublic))->toBe('ok');
    } finally {
        $disk->delete($path);
    }
});

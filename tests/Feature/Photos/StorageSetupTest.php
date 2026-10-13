<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| Public storage accessibility (deployment check)
|--------------------------------------------------------------------------
|
| On Windows + Docker Desktop, `php artisan storage:link` creates a symlink
| that resolves only inside the container, not from the Windows host. The
| recommended fix is to add an explicit Docker volume mount in compose.yaml:
|
|   - './storage/app/public:/var/www/html/public/storage'
|
| Either a working symlink OR a bind-mount makes `public/storage` a directory;
| these tests only verify that the resulting path is readable — they do not
| require it to be a symlink (a bind-mount is not a symlink).
*/

it('has public/storage set up as a readable directory', function () {
    $path = public_path('storage');

    // Must be a directory (symlink that resolves to a dir, or a volume mount).
    expect(is_dir($path))->toBeTrue(
        'public/storage is not a readable directory. '.
        "Add './storage/app/public:/var/www/html/public/storage' to compose.yaml, or ".
        'run `php artisan storage:link` inside the Docker container.'
    );
})->skip(PHP_OS_FAMILY === 'Windows', 'public/storage is a Docker mount — run this test inside the container.');

it('can access a file stored on the public disk through public/storage', function () {
    $disk = Storage::disk('public');
    $path = '_test/storage-setup-check.txt';

    try {
        $disk->put($path, 'ok');

        $viaPublic = public_path("storage/{$path}");

        expect(file_exists($viaPublic))->toBeTrue(
            'A file written to the public disk is not readable at public/storage. '.
            "Add './storage/app/public:/var/www/html/public/storage' to compose.yaml, or ".
            'run `php artisan storage:link` inside the Docker container.'
        )
            ->and(file_get_contents($viaPublic))->toBe('ok');
    } finally {
        $disk->delete($path);
    }
})->skip(PHP_OS_FAMILY === 'Windows', 'public/storage is a Docker mount — run this test inside the container.');

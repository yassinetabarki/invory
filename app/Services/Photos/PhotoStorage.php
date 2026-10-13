<?php

declare(strict_types=1);

namespace App\Services\Photos;

use Closure;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

/**
 * The disk side of material and product photos (D-5): the original is stored on the `public` disk under `materials/` or
 * `products/` with the hashed name `store()` gives it, and the row holds its path (`photo_path`, never mass-assignable).
 *
 * The file and the database can't share a transaction, so {@see self::persist()} orders them: the new file is written before the
 * transaction (and deleted again if it throws, so a failure leaves no orphan), the old one is deleted after it committed (so a
 * failure keeps the old photo).
 */
final readonly class PhotoStorage
{
    public const DISK = 'public';

    /**
     * Runs `$write` (the Action's transaction) and keeps the disk in step with it.
     *
     * `photo` (an upload) and `remove_photo` are taken out of `$data`. `$write` gets the other fields and the photo change:
     * `['photo_path' => <new path>]` for an upload (it wins over `remove_photo`), `['photo_path' => null]` for a removal, `[]`
     * when the photo stays. The Action sets it with `forceFill()`.
     *
     * @template T
     *
     * @param  string  $directory  `materials` or `products`
     * @param  array<string, mixed>  $data  validated input
     * @param  string|null  $current  the path the row holds now (null for a new row)
     * @param  Closure(array<string, mixed>, array{photo_path?: string|null}): T  $write
     * @return T
     */
    public function persist(string $directory, array $data, ?string $current, Closure $write): mixed
    {
        $upload = $data['photo'] ?? null;
        $photo = [];
        $stored = null;

        if ($upload instanceof UploadedFile) {
            $stored = $upload->store($directory, self::DISK);

            if ($stored === false) {
                throw new RuntimeException('The photo could not be stored.');
            }

            $photo = ['photo_path' => $stored];
        } elseif (filter_var($data['remove_photo'] ?? false, FILTER_VALIDATE_BOOL)) {
            $photo = ['photo_path' => null];
        }

        try {
            $result = $write(Arr::except($data, ['photo', 'remove_photo']), $photo);
        } catch (Throwable $e) {
            $this->delete($stored);

            throw $e;
        }

        if ($photo !== []) {
            $this->delete($current);
        }

        return $result;
    }

    /** Deletes a stored photo. A null path or a file that is already gone is nothing to do. */
    public function delete(?string $path): void
    {
        if ($path !== null) {
            Storage::disk(self::DISK)->delete($path);
        }
    }
}

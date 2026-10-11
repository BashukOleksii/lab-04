<?php

namespace App\Services;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RepairDefectFileService
{
    private const DISK = 'public';

    private function disk(): FilesystemAdapter
    {
        $disk = Storage::disk(self::DISK);
        return $disk;
    }

    public function store(UploadedFile $file): string
    {
        $path = $file->store('repair-defects', self::DISK);

        if ($path === false) {
            throw new RuntimeException('Не вдалося зберегти файл дефекту.');
        }

        return $path;
    }

    public function exists(?string $path): bool
    {
        return $path !== null
            && $path !== ''
            && $this->disk()->exists($path);
    }

    public function url(?string $path): ?string
    {
        if ($path === null || ! $this->exists($path)) {
            return null;
        }

        return $this->disk()->url($path);
    }

    public function isPdf(?string $path): bool
    {
        return $path !== null
            && strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'pdf';
    }

    public function download(string $path): StreamedResponse
    {
        abort_unless($this->exists($path), 404, 'Файл не знайдено.');

        return $this->disk()->download($path);
    }

    public function delete(?string $path): void
    {
        if ($path === null || ! $this->exists($path)) {
            return;
        }

        if (! $this->disk()->delete($path)) {
            throw new RuntimeException('Не вдалося видалити файл дефекту.');
        }
    }
}

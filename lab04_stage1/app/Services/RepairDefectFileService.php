<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RepairDefectFileService
{
    private const DISK = 'public';

    public function store(UploadedFile $file): string
    {
        // Laravel генерує безпечне ім'я; у БД зберігається лише відносний шлях.
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
            && Storage::disk(self::DISK)->exists($path);
    }

    public function url(?string $path): ?string
    {
        return $this->exists($path)
            ? Storage::disk(self::DISK)->url($path)
            : null;
    }

    public function isPdf(?string $path): bool
    {
        return $path !== null
            && strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'pdf';
    }

    public function download(string $path): StreamedResponse
    {
        abort_unless($this->exists($path), 404, 'Файл не знайдено.');

        return Storage::disk(self::DISK)->download($path);
    }

    public function delete(?string $path): void
    {
        // Видалення неіснуючого фізичного файла — допустимий сценарій.
        if ($this->exists($path)) {
            if (! Storage::disk(self::DISK)->delete($path)) {
                throw new RuntimeException('Не вдалося видалити файл дефекту.');
            }
        }
    }
}

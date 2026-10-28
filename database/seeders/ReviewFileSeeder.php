<?php

namespace Database\Seeders;

use App\Models\RepairRequest;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ReviewFileSeeder extends Seeder
{
    public function run(): void
    {
        $repairs = RepairRequest::query()
            ->where('subject', 'like', 'DEMO-%')
            ->orderBy('id')
            ->limit(3)
            ->get();

        foreach ($repairs as $repair) {
            if ($repair->review_file_path !== null) {
                continue;
            }

            $path = 'repair-reviews/demo-' .
                $repair->getKey() . '.pdf';

            if (! Storage::disk('local')->put(
                $path,
                $this->createPdf()
            )) {
                throw new RuntimeException(
                    'Не вдалося створити демонстраційний PDF.'
                );
            }

            $repair->review_file_path = $path;
            $repair->saveOrFail();
        }
    }

    private function createPdf(): string
    {
        $stream = 'BT /F1 18 Tf 72 750 Td '
            . '(RepairDesk sample review) Tj ET';

        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R '
                . '/MediaBox [0 0 595 842] '
                . '/Resources << /Font << /F1 4 0 R >> >> '
                . '/Contents 5 0 R >>',
            '<< /Type /Font /Subtype /Type1 '
                . '/BaseFont /Helvetica >>',
            "<< /Length " . strlen($stream)
                . " >>\nstream\n{$stream}\nendstream",
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [];

        foreach ($objects as $index => $object) {
            $offsets[] = strlen($pdf);

            $pdf .= ($index + 1)
                . " 0 obj\n{$object}\nendobj\n";
        }

        $xrefPosition = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";

        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        $pdf .= "trailer\n<< /Size "
            . (count($objects) + 1)
            . " /Root 1 0 R >>\n";

        $pdf .= "startxref\n{$xrefPosition}\n%%EOF\n";

        return $pdf;
    }
}

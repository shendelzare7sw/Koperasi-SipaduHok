<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Reader\Exception as ReaderException;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class ProductImportController extends Controller
{
    private const HEADERS = [
        'nama_produk',
        'deskripsi',
        'harga',
        'stok',
        'kategori',
        'status_aktif',
    ];

    private const MAX_ROWS = 1000;

    public function index(): View
    {
        return view('admin.products.import');
    }

    public function downloadTemplate(): StreamedResponse
    {
        $spreadsheet = $this->makeTemplate();

        return response()->streamDownload(function () use ($spreadsheet): void {
            (new XlsxWriter($spreadsheet))->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, 'template-import-produk-koperasi-sipaduhok.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0, no-cache, no-store, must-revalidate',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'import_file' => [
                'required',
                'file',
                'mimes:xlsx',
                'max:5120',
            ],
        ], [
            'import_file.required' => 'Pilih file Excel yang akan diimpor.',
            'import_file.mimes' => 'File import harus berformat .xlsx.',
            'import_file.max' => 'Ukuran file import maksimal 5 MB.',
        ]);

        $path = $request->file('import_file')->getRealPath();
        $spreadsheet = null;

        try {
            $reader = new XlsxReader;
            $reader->setReadDataOnly(true);
            $reader->setReadEmptyCells(false);

            if (! in_array('Data Produk', $reader->listWorksheetNames($path), true)) {
                return back()->withErrors([
                    'import_file' => 'Sheet "Data Produk" tidak ditemukan. Gunakan template resmi tanpa mengubah nama sheet.',
                ]);
            }

            $reader->setLoadSheetsOnly(['Data Produk']);
            $spreadsheet = $reader->load($path);
            $sheet = $spreadsheet->getSheetByName('Data Produk');

            if ($sheet === null) {
                throw new ReaderException('Sheet Data Produk tidak dapat dibaca.');
            }

            $highestRow = $sheet->getHighestDataRow();

            if ($highestRow - 1 > self::MAX_ROWS) {
                return back()->withErrors([
                    'import_file' => 'Maksimal '.number_format(self::MAX_ROWS, 0, ',', '.').' baris produk dalam sekali import.',
                ]);
            }

            $headers = [];
            foreach (range('A', 'F') as $column) {
                $headers[] = $this->normaliseHeader($sheet->getCell($column.'1')->getValue());
            }

            if ($headers !== self::HEADERS) {
                return back()->withErrors([
                    'import_file' => 'Susunan kolom tidak sesuai template. Unduh template baru dan jangan mengubah nama atau urutan kolom.',
                ]);
            }

            [$rows, $errors] = $this->validatedRows($sheet, $highestRow);

            if ($errors !== []) {
                return back()->with('import_errors', $errors);
            }

            if ($rows === []) {
                return back()->withErrors([
                    'import_file' => 'Sheet "Data Produk" belum berisi data produk.',
                ]);
            }

            DB::transaction(function () use ($rows): void {
                foreach ($rows as $row) {
                    Product::create([
                        'name' => $row['name'],
                        'slug' => $this->uniqueSlug($row['name']),
                        'description' => $row['description'],
                        'price' => $row['price'],
                        'stock' => $row['stock'],
                        'category' => $row['category'],
                        'is_active' => $row['is_active'],
                    ]);
                }
            });

            return redirect()->route('admin.products.index')->with(
                'success',
                count($rows).' produk berhasil diimpor. Foto produk dapat ditambahkan melalui menu Edit.'
            );
        } catch (ReaderException) {
            return back()->withErrors([
                'import_file' => 'File Excel rusak atau tidak dapat dibaca. Unduh ulang template resmi lalu coba kembali.',
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors([
                'import_file' => 'Import belum dapat diproses. Periksa isi file atau coba kembali beberapa saat lagi.',
            ]);
        } finally {
            $spreadsheet?->disconnectWorksheets();
        }
    }

    /**
     * @return array{0: array<int, array<string, mixed>>, 1: array<int, string>}
     */
    private function validatedRows($sheet, int $highestRow): array
    {
        $rows = [];
        $errors = [];
        $namesInFile = [];

        for ($rowNumber = 2; $rowNumber <= $highestRow; $rowNumber++) {
            $values = [];
            foreach (range('A', 'F') as $column) {
                $values[] = $sheet->getCell($column.$rowNumber)->getValue();
            }

            if (collect($values)->every(fn ($value) => $value === null || trim((string) $value) === '')) {
                continue;
            }

            $name = trim((string) $values[0]);
            $status = Str::lower(trim((string) $values[5]));
            $category = Str::of((string) $values[4])->trim()->lower()->replace([' ', '-'], '_')->value();
            $row = [
                'name' => $name,
                'description' => trim((string) $values[1]) ?: null,
                'price' => $values[2],
                'stock' => $values[3],
                'category' => $category,
                'is_active' => match ($status) {
                    'ya', '1', 'aktif', 'true' => true,
                    'tidak', '0', 'nonaktif', 'false' => false,
                    default => null,
                },
            ];

            $validator = Validator::make($row, [
                'name' => ['required', 'string', 'max:255', Rule::unique('products', 'name')],
                'description' => ['nullable', 'string', 'max:10000'],
                'price' => ['required', 'integer', 'min:0', 'max:999999999999'],
                'stock' => ['required', 'integer', 'min:0', 'max:1000000000'],
                'category' => ['required', Rule::in(array_keys(Product::CATEGORIES))],
                'is_active' => ['required', 'boolean'],
            ], [
                'name.required' => 'Nama produk wajib diisi.',
                'name.unique' => 'Nama produk sudah ada di database.',
                'name.max' => 'Nama produk maksimal 255 karakter.',
                'description.max' => 'Deskripsi maksimal 10.000 karakter.',
                'price.required' => 'Harga wajib diisi.',
                'price.integer' => 'Harga harus berupa bilangan bulat tanpa Rp, titik, atau koma.',
                'price.min' => 'Harga tidak boleh negatif.',
                'price.max' => 'Harga terlalu besar.',
                'stock.required' => 'Stok wajib diisi.',
                'stock.integer' => 'Stok harus berupa bilangan bulat.',
                'stock.min' => 'Stok tidak boleh negatif.',
                'stock.max' => 'Stok terlalu besar.',
                'category.in' => 'Kategori harus buku, alat_tulis, atau atribut_sekolah.',
                'is_active.required' => 'Status aktif harus diisi dengan ya atau tidak.',
                'is_active.boolean' => 'Status aktif harus diisi dengan ya atau tidak.',
            ]);

            if ($name !== '' && isset($namesInFile[Str::lower($name)])) {
                $validator->after(function ($validator): void {
                    $validator->errors()->add('name', 'Nama produk duplikat di dalam file.');
                });
            }

            if ($validator->fails()) {
                $errors[] = 'Baris '.$rowNumber.': '.implode(' ', $validator->errors()->all());

                continue;
            }

            $namesInFile[Str::lower($name)] = true;
            $rows[] = $row;
        }

        return [$rows, $errors];
    }

    private function normaliseHeader(mixed $header): string
    {
        return Str::of((string) $header)
            ->trim()
            ->lower()
            ->replace([' ', '-'], '_')
            ->replaceMatches('/_+/', '_')
            ->value();
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'produk';
        $slug = $base;
        $suffix = 2;

        while (Product::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    private function makeTemplate(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getProperties()
            ->setCreator('Koperasi Sipaduhok')
            ->setTitle('Template Import Produk Koperasi Sipaduhok')
            ->setDescription('Template resmi untuk menambahkan produk secara massal.');

        $dataSheet = $spreadsheet->getActiveSheet();
        $dataSheet->setTitle('Data Produk');
        $dataSheet->fromArray(self::HEADERS, null, 'A1');
        $this->styleTableHeader($dataSheet, 'A1:F1');
        $dataSheet->freezePane('A2');
        $dataSheet->setAutoFilter('A1:F1');
        $dataSheet->getColumnDimension('A')->setWidth(34);
        $dataSheet->getColumnDimension('B')->setWidth(54);
        $dataSheet->getColumnDimension('C')->setWidth(18);
        $dataSheet->getColumnDimension('D')->setWidth(12);
        $dataSheet->getColumnDimension('E')->setWidth(24);
        $dataSheet->getColumnDimension('F')->setWidth(18);
        $dataSheet->getStyle('C2:C'.(self::MAX_ROWS + 1))->getNumberFormat()->setFormatCode('#,##0');

        $categoryValidation = new DataValidation;
        $categoryValidation->setType(DataValidation::TYPE_LIST);
        $categoryValidation->setErrorStyle(DataValidation::STYLE_STOP);
        $categoryValidation->setAllowBlank(false);
        $categoryValidation->setShowErrorMessage(true);
        $categoryValidation->setErrorTitle('Kategori tidak valid');
        $categoryValidation->setError('Pilih kategori dari daftar yang tersedia.');
        $categoryValidation->setShowDropDown(true);
        $categoryValidation->setFormula1('"buku,alat_tulis,atribut_sekolah"');

        $statusValidation = new DataValidation;
        $statusValidation->setType(DataValidation::TYPE_LIST);
        $statusValidation->setErrorStyle(DataValidation::STYLE_STOP);
        $statusValidation->setAllowBlank(false);
        $statusValidation->setShowErrorMessage(true);
        $statusValidation->setErrorTitle('Status tidak valid');
        $statusValidation->setError('Pilih ya atau tidak.');
        $statusValidation->setShowDropDown(true);
        $statusValidation->setFormula1('"ya,tidak"');

        for ($row = 2; $row <= self::MAX_ROWS + 1; $row++) {
            $dataSheet->getCell('E'.$row)->setDataValidation(clone $categoryValidation);
            $dataSheet->getCell('F'.$row)->setDataValidation(clone $statusValidation);
        }

        $exampleSheet = $spreadsheet->createSheet();
        $exampleSheet->setTitle('Contoh Pengisian');
        $exampleSheet->fromArray(self::HEADERS, null, 'A1');
        $exampleSheet->fromArray([
            ['Buku Matematika Kelas X', 'Buku pelajaran matematika untuk siswa kelas X.', 65000, 30, 'buku', 'ya'],
            ['Pensil 2B', 'Pensil 2B untuk menulis dan ujian sekolah.', 3500, 120, 'alat_tulis', 'ya'],
            ['Dasi Sekolah', 'Dasi seragam resmi sekolah.', 15000, 50, 'atribut_sekolah', 'ya'],
        ], null, 'A2');
        $this->styleTableHeader($exampleSheet, 'A1:F1');
        $exampleSheet->getStyle('A2:F4')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFFFF7D6');
        $exampleSheet->getStyle('C2:C4')->getNumberFormat()->setFormatCode('#,##0');
        $exampleSheet->freezePane('A2');
        foreach (range('A', 'F') as $column) {
            $exampleSheet->getColumnDimension($column)->setAutoSize(true);
        }

        $guideSheet = $spreadsheet->createSheet();
        $guideSheet->setTitle('Petunjuk');
        $guideSheet->fromArray([
            ['PETUNJUK IMPORT PRODUK KOPERASI SIPADUHOK'],
            ['1. Isi produk pada sheet "Data Produk", mulai dari baris 2.'],
            ['2. Sheet "Contoh Pengisian" hanya contoh dan tidak ikut diimpor.'],
            ['3. Jangan mengubah nama, urutan kolom, atau nama sheet "Data Produk".'],
            ['4. Harga ditulis sebagai angka bulat tanpa Rp, titik, atau koma. Contoh: 25000.'],
            ['5. Stok ditulis sebagai angka bulat nol atau lebih.'],
            ['6. Kategori yang valid: buku, alat_tulis, atribut_sekolah.'],
            ['7. Status aktif diisi ya atau tidak.'],
            ['8. Nama produk tidak boleh sama dengan produk yang sudah ada atau baris lain.'],
            ['9. Maksimal 1.000 produk dan ukuran file maksimal 5 MB.'],
            ['10. Foto produk ditambahkan setelah import melalui menu Edit Produk.'],
        ], null, 'A1');
        $guideSheet->mergeCells('A1:F1');
        $guideSheet->getStyle('A1:F1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 14],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF143C7D']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $guideSheet->getColumnDimension('A')->setWidth(100);
        $guideSheet->getStyle('A2:A11')->getAlignment()->setWrapText(true);

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    private function styleTableHeader($sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF143C7D']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFCBD5E1']],
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(26);
    }
}

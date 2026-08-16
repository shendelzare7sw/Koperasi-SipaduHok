<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class ProductImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_download_template_with_examples_and_instructions(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.products.import.template'));

        $response->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->assertDownload('template-import-produk-koperasi-sipaduhok.xlsx');

        $path = tempnam(sys_get_temp_dir(), 'template-product-').'.xlsx';
        file_put_contents($path, $response->streamedContent());

        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx;
        $spreadsheet = $reader->load($path);

        $this->assertSame(['Data Produk', 'Contoh Pengisian', 'Petunjuk'], $spreadsheet->getSheetNames());
        $this->assertSame('Buku Matematika Kelas X', $spreadsheet->getSheetByName('Contoh Pengisian')->getCell('A2')->getValue());
        $this->assertSame('nama_produk', $spreadsheet->getSheetByName('Data Produk')->getCell('A1')->getValue());

        $spreadsheet->disconnectWorksheets();
        unlink($path);
    }

    public function test_admin_can_import_multiple_products_from_official_columns(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $file = $this->makeImportFile([
            ['Buku Bahasa Indonesia Kelas XI', 'Buku pelajaran resmi.', 72000, 25, 'buku', 'ya'],
            ['Penghapus Putih', 'Penghapus pensil.', 2500, 80, 'alat_tulis', 'tidak'],
        ]);

        $response = $this->actingAs($admin)->post(route('admin.products.import.store'), [
            'import_file' => $file,
        ]);

        $response->assertRedirect(route('admin.products.index'))
            ->assertSessionHas('success', '2 produk berhasil diimpor. Foto produk dapat ditambahkan melalui menu Edit.');

        $this->assertDatabaseHas('products', [
            'name' => 'Buku Bahasa Indonesia Kelas XI',
            'price' => 72000,
            'stock' => 25,
            'category' => 'buku',
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('products', [
            'name' => 'Penghapus Putih',
            'is_active' => false,
        ]);
    }

    public function test_invalid_row_cancels_entire_import_and_reports_row_number(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $file = $this->makeImportFile([
            ['Produk Valid', 'Baris ini valid.', 12000, 10, 'buku', 'ya'],
            ['Produk Salah', 'Kategori tidak valid.', 15000, 5, 'makanan', 'ya'],
        ]);

        $response = $this->actingAs($admin)->from(route('admin.products.import.index'))->post(route('admin.products.import.store'), [
            'import_file' => $file,
        ]);

        $response->assertRedirect(route('admin.products.import.index'))
            ->assertSessionHas('import_errors', fn (array $errors) => str_contains($errors[0], 'Baris 3'));

        $this->assertSame(0, Product::count());
    }

    /** @param array<int, array<int, mixed>> $rows */
    private function makeImportFile(array $rows): UploadedFile
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Produk');
        $sheet->fromArray(['nama_produk', 'deskripsi', 'harga', 'stok', 'kategori', 'status_aktif'], null, 'A1');
        $sheet->fromArray($rows, null, 'A2');

        $path = tempnam(sys_get_temp_dir(), 'product-import-').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();

        return new UploadedFile(
            $path,
            'produk-koperasi.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );
    }
}

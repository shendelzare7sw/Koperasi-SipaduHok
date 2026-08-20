<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('indonesia_regions', function (Blueprint $table) {
            $table->string('code', 13)->primary();
            $table->string('parent_code', 8)->nullable()->index();
            $table->unsignedTinyInteger('level')->index();
            $table->string('name', 150)->index();
            $table->string('postal_code', 5)->nullable();
        });

        $path = database_path('data/indonesia_regions.csv.gz');

        if (! is_file($path)) {
            throw new RuntimeException('Dataset wilayah Indonesia tidak ditemukan: '.$path);
        }

        $stream = gzopen($path, 'rb');

        if ($stream === false) {
            throw new RuntimeException('Dataset wilayah Indonesia tidak dapat dibuka.');
        }

        fgetcsv($stream);
        $batch = [];

        while (($row = fgetcsv($stream)) !== false) {
            if (count($row) !== 5) {
                continue;
            }

            $batch[] = [
                'code' => $row[0],
                'parent_code' => $row[1] !== '' ? $row[1] : null,
                'level' => (int) $row[2],
                'name' => $row[3],
                'postal_code' => $row[4] !== '' ? $row[4] : null,
            ];

            if (count($batch) === 1000) {
                DB::table('indonesia_regions')->insert($batch);
                $batch = [];
            }
        }

        gzclose($stream);

        if ($batch !== []) {
            DB::table('indonesia_regions')->insert($batch);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('indonesia_regions');
    }
};

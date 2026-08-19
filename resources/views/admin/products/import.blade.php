<x-layouts.app title="Import Produk Excel">
    <div class="mx-auto max-w-5xl">
        <div>
            <div>
                <p class="text-sm font-extrabold uppercase tracking-widest text-secondary">Kelola Produk</p>
                <h1 class="mt-1 text-3xl font-black">Import Produk dari Excel</h1>
                <p class="mt-2 max-w-2xl text-slate-500">Tambahkan banyak produk sekaligus menggunakan template resmi Toko Sipaduhok.</p>
            </div>
        </div>

        @if (session('import_errors'))
            <section class="mt-6 rounded-2xl border border-red-200 bg-red-50 p-5" role="alert">
                <div class="flex items-start gap-3">
                    <i class="fas fa-circle-exclamation mt-0.5 text-red-600" aria-hidden="true"></i>
                    <div>
                        <h2 class="font-black text-red-900">Import dibatalkan karena ada data yang perlu diperbaiki</h2>
                        <p class="mt-1 text-sm text-red-700">Tidak ada produk yang disimpan. Perbaiki baris berikut lalu unggah kembali file yang sama.</p>
                    </div>
                </div>
                <ul class="mt-4 max-h-72 list-disc space-y-1 overflow-y-auto pl-5 text-sm text-red-800">
                    @foreach (session('import_errors') as $importError)
                        <li>{{ $importError }}</li>
                    @endforeach
                </ul>
            </section>
        @endif

        <div class="mt-7 grid gap-6 lg:grid-cols-[0.9fr_1.1fr]">
            <section class="rounded-2xl bg-gradient-to-br from-primary to-primary-dark p-6 text-white shadow-lg shadow-primary/15">
                <div class="grid h-12 w-12 place-items-center rounded-xl bg-white/15 text-2xl text-accent-yellow">
                    <i class="fas fa-file-excel" aria-hidden="true"></i>
                </div>
                <h2 class="mt-5 text-xl font-black">1. Unduh template resmi</h2>
                <p class="mt-2 text-sm leading-6 text-blue-100">Template berisi sheet data, tiga contoh produk untuk seluruh kategori, petunjuk pengisian, dan pilihan kategori siap pakai.</p>
                <a href="{{ route('admin.products.import.template') }}" class="mt-6 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-accent-yellow px-4 py-3 font-black text-slate-900 hover:bg-yellow-300">
                    <i class="fas fa-download" aria-hidden="true"></i>
                    Download Template Excel
                </a>

                <div class="mt-6 border-t border-white/15 pt-5 text-sm text-blue-100">
                    <p class="font-bold text-white">Format yang diterima</p>
                    <ul class="mt-2 space-y-2">
                        <li><i class="fas fa-check mr-2 text-accent-yellow"></i>Excel `.xlsx`, maksimal 5 MB</li>
                        <li><i class="fas fa-check mr-2 text-accent-yellow"></i>Maksimal 1.000 produk sekali import</li>
                        <li><i class="fas fa-check mr-2 text-accent-yellow"></i>Harga dan stok berupa angka bulat</li>
                    </ul>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-xl font-black">2. Isi lalu unggah file</h2>
                <p class="mt-2 text-sm leading-6 text-slate-500">Isi produk pada sheet <strong>Data Produk</strong>. Sheet <strong>Contoh Pengisian</strong> hanya referensi dan tidak akan ikut diimpor. Jika memilih kategori <strong>lainnya</strong>, isi juga kolom <strong>kategori_lainnya</strong>.</p>

                <form
                    method="POST"
                    action="{{ route('admin.products.import.store') }}"
                    enctype="multipart/form-data"
                    class="mt-6"
                    x-data="{
                        fileName: '',
                        fileSize: '',
                        fileError: '',
                        dragging: false,
                        selectFile(file, dropped = false) {
                            this.fileError = '';
                            if (! file) return;

                            if (! file.name.toLowerCase().endsWith('.xlsx')) {
                                this.fileError = 'Pilih file Excel dengan ekstensi .xlsx.';
                                this.$refs.input.value = '';
                                this.fileName = '';
                                this.fileSize = '';
                                return;
                            }

                            if (file.size > 5 * 1024 * 1024) {
                                this.fileError = 'Ukuran file melebihi batas 5 MB.';
                                this.$refs.input.value = '';
                                this.fileName = '';
                                this.fileSize = '';
                                return;
                            }

                            if (dropped) {
                                const transfer = new DataTransfer();
                                transfer.items.add(file);
                                this.$refs.input.files = transfer.files;
                            }

                            this.fileName = file.name;
                            this.fileSize = file.size < 1024 * 1024
                                ? Math.max(1, Math.round(file.size / 1024)) + ' KB'
                                : (file.size / 1024 / 1024).toFixed(2) + ' MB';
                        },
                        clearFile() {
                            this.$refs.input.value = '';
                            this.fileName = '';
                            this.fileSize = '';
                            this.fileError = '';
                        }
                    }"
                    data-confirm="Seluruh isi file akan divalidasi. Produk baru disimpan hanya jika semua baris sudah benar."
                    data-confirm-title="Import produk sekarang?"
                    data-confirm-button="Ya, mulai import"
                >
                    @csrf
                    <label for="import_file" class="block text-sm font-bold text-slate-800">File Excel produk</label>
                    <label
                        for="import_file"
                        class="mt-2 flex min-h-52 cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed p-6 text-center transition"
                        :class="dragging ? 'scale-[1.01] border-primary bg-blue-100 shadow-lg shadow-primary/10' : (fileName ? 'border-emerald-400 bg-emerald-50' : 'border-slate-300 bg-slate-50 hover:border-primary hover:bg-blue-50')"
                        @dragenter.prevent="dragging = true"
                        @dragover.prevent="dragging = true"
                        @dragleave.prevent="dragging = false"
                        @drop.prevent="dragging = false; selectFile($event.dataTransfer.files[0], true)"
                    >
                        <span class="grid h-16 w-16 place-items-center rounded-2xl transition" :class="fileName ? 'bg-emerald-100 text-emerald-600' : 'bg-blue-100 text-primary'">
                            <i class="fas text-3xl" :class="fileName ? 'fa-file-circle-check' : 'fa-cloud-arrow-up'" aria-hidden="true"></i>
                        </span>
                        <span class="mt-4 max-w-full break-all font-black text-slate-800" x-text="fileName || (dragging ? 'Lepaskan file di sini' : 'Tarik & lepas file Excel di sini')"></span>
                        <span x-show="fileName" x-text="fileSize" class="mt-1 text-xs font-bold text-emerald-700"></span>
                        <span x-show="! fileName" class="mt-1 text-xs text-slate-500">atau klik area ini untuk memilih file `.xlsx` dari perangkat</span>
                        <span x-show="fileName" class="mt-3 inline-flex items-center gap-2 rounded-full bg-white px-3 py-1.5 text-xs font-bold text-primary shadow-sm"><i class="fas fa-rotate" aria-hidden="true"></i>Klik untuk ganti file</span>
                    </label>
                    <input
                        id="import_file"
                        name="import_file"
                        type="file"
                        accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                        class="sr-only"
                        required
                        x-ref="input"
                        @change="selectFile($event.target.files[0])"
                    >
                    <p x-cloak x-show="fileError" x-text="fileError" class="mt-2 text-sm font-bold text-red-600"></p>
                    @error('import_file')
                        <p class="mt-2 text-sm font-bold text-red-600">{{ $message }}</p>
                    @enderror

                    <div class="mt-5 rounded-xl border border-blue-100 bg-blue-50 p-4 text-sm text-slate-700">
                        <p class="font-black text-primary"><i class="fas fa-shield-halved mr-2"></i>Import aman dan menyeluruh</p>
                        <p class="mt-1 leading-6">Jika satu baris salah, semua data dibatalkan dan nomor baris yang perlu diperbaiki akan ditampilkan.</p>
                    </div>

                    <button type="submit" :disabled="fileName === '' || fileError !== ''" class="mt-5 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-primary px-5 py-3.5 font-black text-white transition hover:bg-secondary disabled:cursor-not-allowed disabled:bg-slate-300">
                        <i class="fas fa-file-import" aria-hidden="true"></i>
                        Validasi & Import Produk
                    </button>
                </form>
            </section>
        </div>
    </div>
</x-layouts.app>

<?php

namespace App\Http\Requests\LaporanInsiden;

use App\Support\LaporanInsidenJenis;
use App\Support\LaporanInsidenKorban;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;

class StoreLaporanInsidenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('Satpam') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'jenis_insiden' => ['required', 'string', Rule::in(LaporanInsidenJenis::all())],
            'lokasi_id' => ['nullable', 'integer', 'exists:lokasi,id', 'required_without:lokasi_manual'],
            'lokasi_manual' => ['nullable', 'string', 'max:200', 'required_without:lokasi_id'],
            'tanggal' => ['required', 'date'],
            'waktu' => ['required', 'date_format:H:i'],
            'kronologi' => ['required', 'string', 'min:10', 'max:10000'],
            'korban_list' => ['nullable', 'array', 'max:'.LaporanInsidenKorban::MAX_ITEMS],
            'korban_list.*.nama' => ['nullable', 'string', 'max:200'],
            'korban_list.*.usia' => ['nullable', 'string', 'max:20'],
            'korban_list.*.unit_prodi' => ['nullable', 'string', 'max:100'],
            'korban_list.*.jabatan' => ['nullable', 'string', 'max:100'],
            'korban_list.*.status' => ['nullable', 'string', 'max:100'],
            'foto' => ['required', 'array', 'min:1', 'max:10'],
            'foto.*' => ['image', 'max:5120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'jenis_insiden.required' => 'Jenis insiden wajib dipilih.',
            'jenis_insiden.in' => 'Jenis insiden tidak valid.',
            'lokasi_id.required_without' => 'Pilih lokasi dari daftar atau isi lokasi manual.',
            'lokasi_manual.required_without' => 'Pilih lokasi dari daftar atau isi lokasi manual.',
            'lokasi_id.exists' => 'Lokasi tidak ditemukan.',
            'tanggal.required' => 'Tanggal kejadian wajib diisi.',
            'waktu.required' => 'Waktu kejadian wajib diisi.',
            'waktu.date_format' => 'Format waktu tidak valid.',
            'kronologi.required' => 'Kronologi kejadian wajib diisi.',
            'kronologi.min' => 'Kronologi minimal 10 karakter.',
            'korban_list.max' => 'Maksimal '.LaporanInsidenKorban::MAX_ITEMS.' korban.',
            'foto.required' => 'Minimal satu foto TKP wajib diunggah.',
            'foto.min' => 'Minimal satu foto TKP wajib diunggah.',
            'foto.max' => 'Maksimal 10 foto TKP.',
            'foto.*.image' => 'File harus berupa gambar.',
            'foto.*.max' => 'Ukuran foto maksimal 5 MB.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $lokasiId = $this->input('lokasi_id');
            $manual = trim((string) $this->input('lokasi_manual', ''));

            if ($lokasiId && $manual !== '') {
                $validator->errors()->add('lokasi_id', 'Pilih lokasi dari daftar atau isi manual, tidak keduanya.');
            }

            foreach ($this->input('korban_list', []) as $index => $item) {
                if (! is_array($item)) {
                    continue;
                }

                $nama = trim((string) ($item['nama'] ?? ''));
                $hasExtra = collect(['usia', 'unit_prodi', 'jabatan', 'status'])
                    ->contains(fn (string $key) => trim((string) ($item[$key] ?? '')) !== '');

                if ($nama === '' && $hasExtra) {
                    $validator->errors()->add(
                        "korban_list.{$index}.nama",
                        'Nama korban wajib diisi jika detail korban diisi.',
                    );
                }
            }
        });
    }

    /**
     * @return list<array{nama: string, usia: ?string, unit_prodi: ?string, jabatan: ?string, status: ?string}>
     */
    public function korbanList(): array
    {
        $raw = $this->validated('korban_list') ?? [];

        return LaporanInsidenKorban::normalizeList(is_array($raw) ? $raw : []);
    }

    /**
     * @return list<UploadedFile>
     */
    public function fotoFiles(): array
    {
        $raw = $this->file('foto', []);

        if (! is_array($raw)) {
            return [];
        }

        return array_values(array_filter(
            $raw,
            fn ($file) => $file instanceof UploadedFile && $file->isValid(),
        ));
    }
}

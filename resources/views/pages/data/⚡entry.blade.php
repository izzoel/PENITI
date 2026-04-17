<?php
use App\Models\CutiKuota;
use App\Models\CutiSaldo;
use App\Models\Entries;
use App\Models\Pegawai;
use App\Models\SKPD as Skpd;
use App\Models\User;
use App\Services\DocVerifyService;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;

new class extends Component {
    use WithPagination;
    public $pegawai, $id_pegawai, $id_cuti_kuota, $telp, $tanggal_mulai, $tanggal_selesai, $lama_hari, $alasan, $alamat, $file;
    public $saldos;
    public $editFieldRowId, $lastSegment;
    public $user, $authRole, $authSkpd, $filterSkpd;
    public $selectedPegawai; // id pegawai yang dipilih
    public $cutisList = []; // daftar cuti untuk pegawai ini

    public $search = '';
    public $perPage = 10;

    protected $paginationTheme = 'bootstrap';
    protected $queryString = ['search', 'perPage'];

    protected $rules = [
        'telp' => 'required',
        'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
        // 'id_cuti_kuota' => 'required',
        'alasan' => 'required',
        'alamat' => 'required',
        'file' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
    ];

    protected $messages = [
        'telp.required' => 'Telepon wajib diisi.',
        'tanggal_selesai.required' => 'Rentang tanggal wajib diisi.',
        'tanggal_selesai.date' => 'Format tanggal selesai tidak valid.',
        'tanggal_selesai.after_or_equal' => 'Tanggal selesai harus sama atau setelah tanggal mulai.',
        // 'id_cuti_kuota.required' => 'Jenis cuti wajib diisi',
        'alasan.required' => 'Alasan wajib diisi.',
        'alamat.required' => 'Alamat wajib diisi.',
        'file.file' => 'File harus berupa dokumen yang valid.',
        'file.mimes' => 'Format file harus PDF, DOC, atau DOCX.',
        'file.max' => 'Ukuran file maksimal 10 MB.',
    ];

    public function updated($property)
    {
        $this->validateOnly($property);
    }
    public function updatedTanggalMulai()
    {
        $this->validateOnly('tanggal_mulai');
    }

    public function updatedTanggalSelesai()
    {
        $this->validateOnly('tanggal_selesai');
    }

    public function updatedIdPegawai()
    {
        $tahunSekarang = now()->year;

        $saldos = CutiSaldo::with('kuotas')
            ->where('id_user', $this->id_pegawai)
            ->whereIn('tahun', [$tahunSekarang, $tahunSekarang - 1, $tahunSekarang - 2])
            ->get()
            ->groupBy(fn($saldo) => strtolower($saldo->kuotas->jenis ?? '---'));

        $result = [];

        foreach ($saldos as $jenis => $items) {
            $sisaTotal = 0;

            foreach ($items as $saldo) {
                $tahun = $saldo->tahun;
                $kuota = $saldo->kuotas;
                $sisa = $saldo->sisa;

                if ($jenis === 'tahunan') {
                    if ($tahun == $tahunSekarang) {
                        $sisaTotal += $sisa;
                    } else {
                        // tahun-1 atau tahun-2 → hanya jika >=6, dibagi 2
                        $sisaTotal += $sisa >= 6 ? intval($sisa / 2) : 0;
                    }
                } else {
                    // selain tahunan → ambil sisa tahun sekarang saja
                    if ($tahun == $tahunSekarang) {
                        $sisaTotal += $sisa;
                    }
                }
            }

            // Batasi tahunan maksimum 24
            if ($jenis === 'tahunan') {
                $sisaTotal = min($sisaTotal, 24);
            }

            $result[] = [
                'id' => $saldo->id_cuti_kuota,
                'jenis' => ucfirst($jenis),
                'sisa' => $sisaTotal,
            ];
        }

        $this->dispatch('updateJenisCuti', ['saldos' => $result]);
    }

    public function loadSaldosEdit()
    {
        $tahunSekarang = now()->year;

        $saldos = CutiSaldo::with('kuotas')
            ->where('id_user', $this->id_pegawai)
            ->whereIn('tahun', [$tahunSekarang, $tahunSekarang - 1, $tahunSekarang - 2])
            ->get()
            ->groupBy(fn($saldo) => strtolower($saldo->kuotas->jenis ?? '---'));

        $result = [];

        foreach ($saldos as $jenis => $items) {
            $sisaTotal = 0;

            foreach ($items as $saldo) {
                $tahun = $saldo->tahun;
                $kuota = $saldo->kuotas;
                $sisa = $saldo->sisa;

                if ($jenis === 'tahunan') {
                    if ($tahun == $tahunSekarang) {
                        $sisaTotal += $sisa;
                    } else {
                        $sisaTotal += $sisa >= 6 ? intval($sisa / 2) : 0;
                    }
                } else {
                    if ($tahun == $tahunSekarang) {
                        $sisaTotal += $sisa;
                    }
                }
            }

            if ($jenis === 'tahunan') {
                $sisaTotal = min($sisaTotal, 24);
            }

            $result[] = [
                'id' => $items->first()->id_cuti_kuota, // pakai cuti_kuotas.id
                'jenis' => ucfirst($jenis),
                'sisa' => $sisaTotal,
            ];
        }

        $this->dispatch('updateJenisCutiEdit', ['saldos' => $result]);
    }

    public function mount()
    {
        $this->pegawai = auth()->user()->id_pegawai;
        $this->user = auth()->user()->id;
        $this->authRole = auth()->user()->roles->first()->name;
        $this->authSkpd = auth()->user()->id_skpd;

        $this->filterSkpd = $this->authSkpd;

        $this->saldos = CutiSaldo::where('id_user', $this->user)->get();
        $routeName = request()->route()?->getName();
        $this->lastSegment = $routeName ? collect(explode('.', $routeName))->last() : null;
    }

    public function konfirmasi($id, $jenis)
    {
        $this->dispatch('konfirmasi', [
            'id' => $id,
            'jenis' => $jenis,
        ]);
    }

    #[Computed]
    public function entries()
    {
        $query = Entries::query();

        // filter SKPD hanya jika ada pilihan
        $query->when($this->authRole == 'Super Admin' && $this->filterSkpd, function ($q) {
            $q->whereHas('user', function ($userQuery) {
                $userQuery->where('id_skpd', $this->filterSkpd);
            });
        });

        // filter untuk non-Super Admin
        if ($this->authRole != 'Super Admin') {
            $query->whereHas('user', function ($q) {
                $q->where('id_skpd', auth()->user()->id_skpd);
            });
        }

        // filter search
        if ($this->search) {
            $query->whereHas('user', function ($q) {
                $q->where('nama', 'like', '%' . $this->search . '%');
            });
        }

        return $query->orderBy('tanggal_mulai', 'desc')->paginate($this->perPage);
    }

    #[Computed]
    public function skpds()
    {
        return Skpd::orderBy('nama', 'asc')->get();
    }

    #[Computed]
    public function lamaHari()
    {
        if ($this->tanggal_mulai && $this->tanggal_selesai) {
            $this->lama_hari = Carbon::parse($this->tanggal_mulai)->diffInDays(Carbon::parse($this->tanggal_selesai)) + 1;
            return $this->lama_hari;
        }
        return 0;
    }

    #[Computed]
    public function cutis($id_pegawai)
    {
        return CutiSaldo::with('kuotas')
            ->where('id_user', $id_pegawai)
            ->where('tahun', now()->year)
            ->get()
            ->groupBy(fn($item) => $item->kuotas->jenis)
            ->map(function ($items, $jenis) {
                return [
                    'jenis' => $jenis,
                    'id' => $items->first()->id_cuti_kuota,
                    'sisa' => $items->sum('sisa'),
                ];
            })
            ->values();
    }

    #[Computed]
    public function pegawais()
    {
        return User::where('id_skpd', $this->authSkpd)
            ->when($this->search, function ($query) {
                $query->where('nama', 'like', '%' . $this->search . '%');
            })
            ->get();
    }

    public function modal($mode = null, $id = null)
    {
        $this->reset(['id_pegawai', 'id_cuti_kuota', 'tanggal_mulai', 'tanggal_selesai', 'alasan', 'alamat', 'telp', 'lama_hari']);

        if ($mode === 'edit' && $id) {
            $entryEdit = Entries::find($id);
            if ($entryEdit) {
                $this->id_pegawai = $entryEdit->id_pegawai;
                $this->id_cuti_kuota = $entryEdit->id_cuti_kuota ?? null;
                $this->telp = $entryEdit->telp ?? '';
                $this->tanggal_mulai = $entryEdit->tanggal_mulai;
                $this->tanggal_selesai = $entryEdit->tanggal_selesai;
                $this->lamaHari = $entryEdit->lama_hari ?? 0;
                $this->alasan = $entryEdit->alasan ?? '';
                $this->alamat = $entryEdit->alamat ?? '';
            }
            $this->dispatch('modalEdit');
        } else {
            $this->dispatch('modal');
        }
    }

    public function simpan()
    {
        $this->validate();
        $tahun = format_tahun($this->tanggal_mulai);
        $pegawai = Pegawai::find($this->id_pegawai);
        $id_atasan = empty($pegawai->id_atasan) ? 1 : $pegawai->id_atasan;
        $id_user = $pegawai->id ?? auth()->user()->id;
        $atasan = Pegawai::where('id_struktur', $id_atasan)->first();

        // Ambil saldo cuti tahunan 3 tahun terakhir
        $saldos = CutiSaldo::with('kuotas')
            ->where('id_user', $id_user)
            ->where('id_cuti_kuota', $this->id_cuti_kuota)
            ->whereIn('tahun', [$tahun, $tahun - 1, $tahun - 2])
            ->get()
            ->keyBy('tahun');

        $n = $saldos[$tahun]->sisa ?? 0;
        $n_1 = min($saldos[$tahun - 1]->sisa ?? 0, 6); // maksimal 6
        $n_2 = min($saldos[$tahun - 2]->sisa ?? 0, 6); // maksimal 6

        $lama_hari_tersisa = $this->lama_hari;

        // kurangi dari N
        if ($lama_hari_tersisa <= $n) {
            $n -= $lama_hari_tersisa;
            $lama_hari_tersisa = 0;
        } else {
            $lama_hari_tersisa -= $n;
            $n = 0;
        }

        // kurangi dari N-1
        if ($lama_hari_tersisa > 0) {
            if ($lama_hari_tersisa <= $n_1) {
                $n_1 -= $lama_hari_tersisa;
                $lama_hari_tersisa = 0;
            } else {
                $lama_hari_tersisa -= $n_1;
                $n_1 = 0;
            }
        }

        // kurangi dari N-2
        if ($lama_hari_tersisa > 0) {
            if ($lama_hari_tersisa <= $n_2) {
                $n_2 -= $lama_hari_tersisa;
                $lama_hari_tersisa = 0;
            } else {
                $lama_hari_tersisa -= $n_2;
                $n_2 = 0;
            }
        }

        // buat entry
        $entry = Entries::create([
            'id_pegawai' => $this->id_pegawai,
            'id_cuti_kuota' => $this->id_cuti_kuota,
            'id_skpd' => auth()->user()->id_skpd,
            'id_atasan' => $id_atasan,
            'tanggal_mulai' => $this->tanggal_mulai,
            'tanggal_selesai' => $this->tanggal_selesai,
            'lama_hari' => $this->lama_hari,
            'alasan' => $this->alasan,
            'alamat' => $this->alamat,
            'telp' => $this->telp,
            'kepada_nama' => $atasan->nama,
            'n' => $n,
            'n_1' => $n_1,
            'n_2' => $n_2,
        ]);

        $entry->update([
            'nomor_surat' => nomor_surat($entry),
        ]);

        // update saldo cuti N
        $this->updateSaldo($id_user, $this->id_cuti_kuota, $tahun, min($this->lama_hari, $saldos[$tahun]->sisa ?? 12));

        // update saldo N-1 & N-2 sesuai sisa
        $sisa_lama = $this->lama_hari - ($saldos[$tahun]->sisa ?? 0);
        if ($sisa_lama > 0 && isset($saldos[$tahun - 1])) {
            $pakai_n1 = min($sisa_lama, $n_1);
            $this->updateSaldo($id_user, $this->id_cuti_kuota, $tahun - 1, $pakai_n1);
            $sisa_lama -= $pakai_n1;
        }
        if ($sisa_lama > 0 && isset($saldos[$tahun - 2])) {
            $pakai_n2 = min($sisa_lama, $n_2);
            $this->updateSaldo($id_user, $this->id_cuti_kuota, $tahun - 2, $pakai_n2);
        }

        $this->dispatch('toast_success', 'Pengajuan cuti berhasil ditambahkan.');
        $this->dispatch('cleaning');
    }

    protected function updateSaldo($id_user, $id_cuti_kuota, $tahun, $lama_hari)
    {
        $saldo = CutiSaldo::firstOrCreate(
            [
                'id_user' => $id_user,
                'id_cuti_kuota' => $id_cuti_kuota,
                'tahun' => $tahun,
            ],
            [
                'kuota' => 12,
                'terpakai' => 0,
                'sisa' => 12,
            ],
        );

        $saldo->increment('terpakai', $lama_hari);
        $saldo->refresh();
        $saldo->sisa = max($saldo->kuota - $saldo->terpakai, 0);
        $saldo->save();
    }

    public function ubah()
    {
        $this->validate();

        $entry = $this->entries->firstWhere('id_pegawai', $this->id_pegawai);
        if (!$entry) {
            $this->dispatch('toast_fail', 'Pengajuan cuti ' . $this->entry->user->nama . ' gagal ditambahkan.');
            return;
        }

        $entry->update([
            'id_cuti_kuota' => $this->id_cuti_kuota,
            'telp' => $this->telp,
            'tanggal_mulai' => $this->tanggal_mulai,
            'tanggal_selesai' => $this->tanggal_selesai,
            'lama_hari' => $this->lamaHari ?? null,
            'alasan' => $this->alasan,
            'alamat' => $this->alamat,
            'status' => 'draft',
        ]);

        $this->reset(['id_pegawai', 'id_cuti_kuota', 'telp', 'tanggal_mulai', 'tanggal_selesai', 'alasan', 'alamat']);

        $this->dispatch('cleaning');
        $this->dispatch('toast_success', 'Pengajuan cuti berhasil disimpan.');
    }

    public function proses($id)
    {
        $entry = Entries::find($id);
        if (!$entry) {
            return;
        }

        $skpd = Skpd::where('id_skpd', $this->authSkpd)->first();
        $pimpinan = strtolower(trim(auth()->user()->nama)) === strtolower(trim(format_nama($skpd->pimpinan)));

        $data = [];

        switch ($entry->status) {
            case 'draft':
                $data['status'] = 'diajukan';
                break;

            case 'diajukan':
                $data['status'] = 'diproses';
                $data['disetujui_atasan_at'] = now();
                break;

            case 'diproses':
                $data['status'] = 'disetujui';
                $data['disetujui_pimpinan_at'] = now();
                break;

            case 'disetujui':
                $data['status'] = 'ditolak';
                break;

            case 'ditolak':
                $data['status'] = 'disetujui';

                if ($pimpinan) {
                    $data['disetujui_pimpinan_at'] = now();
                } else {
                    if ($this->authRole == 'Super Admin') {
                        $data['disetujui_pimpinan_at'] = now();
                    }
                    $data['disetujui_atasan_at'] = now();
                }
                break;

            default:
                return;
        }

        $entry->update($data);
        if ($data['status'] === 'disetujui') {
            DocVerifyService::issue($entry->fresh());
        }

        $this->dispatch('toast_success', 'Status berhasil ' . $data['status'] . '.');
    }

    #[On('hapus')]
    public function hapus($id)
    {
        $entry = Entries::findOrFail($id);
        $entry->delete();
        $this->dispatch('toast_success', 'Pengajuan cuti berhasil dihapus.');
    }
};
?>

<div>
    <ol class="breadcrumb bg-white pb-1 shadow-sm mb-4 py-4 pl-4">
        <li class="breadcrumb-item h4 ">
            <a wire:navigate href="{{ route('home.dashboard') }}"><b>Home</b></a>
        </li>
        <li class="breadcrumb-item active h4 text-dark">
            <b>
                Data
            </b>
        </li>
        <li class="breadcrumb-item active h4 text-dark">
            <b>
                Entry
            </b>
        </li>
    </ol>

    @php
        switch ($this->authRole) {
            case 'Admin':
                $entries = $this->entries->where('id_skpd', auth()->user()->id_skpd);
                break;
            case 'PNS':
                $entries = $this->entries->where('id_pegawai', auth()->user()->id);
                break;
            default:
                $entries = $this->entries;
                $skpds = $this->skpds;
                break;
        }
    @endphp

    <div class="row mt-1">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="d-flex align-items-center justify-content-between">
                        <span>Data Entry</span>
                        <br>
                        @if (akses('c', $this->lastSegment))
                            <a wire:click="modal" class="btn btn-sm btn-primary rounded pt-0 px-2 m-1">
                                <i class="fas fa-plus-circle"></i> Baru
                            </a>
                        @endif
                    </h4>
                </div>
                <div class="m-3">

                    <div class="row mb-3">

                        <div class="col-1">
                            <select wire:model.live="perPage" class="form-control" style="width: 110%">
                                <option value="5">5</option>
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                            </select>
                        </div>
                        <div wire:ignore id="container" class="col">
                            @if ($this->authRole == 'Super Admin')
                                <select id="selectSkpd" class="form-control form-control-sm">
                                    @foreach ($skpds as $skpd)
                                        <option value="{{ $skpd->id_skpd }}">{{ $skpd->nama }}</option>
                                    @endforeach
                                </select>
                            @endif
                        </div>
                        <div class="col-3">
                            <input type="text" wire:model.live.debounce.500ms="search" class="form-control" placeholder="ketik sesuatu...">
                        </div>

                    </div>

                    <table class="table table-hover table-bordered table-md text-center">
                        <thead>
                            <tr>
                                <th class="col-auto">#</th>
                                <th class="text-left">Nama</th>
                                <th class="col-auto text-left">NIP</th>
                                <th>Jenis</th>
                                <th class="col-3">Rentang</th>
                                <th>Status</th>
                                @if (akses('d', $this->lastSegment))
                                    <th class="col-1">Aksi</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($entries as $entry)
                                <tr>
                                    <td>
                                        {{ $loop->iteration }}
                                    </td>
                                    <td class="text-left">
                                        {{ $entry->user->nama ?? '---' }}
                                    </td>
                                    <td class="text-left">
                                        {{ $entry->user->nip ?? '---' }}
                                    </td>
                                    <td>
                                        {{ $entry->kuota->jenis ?? '---' }}
                                    </td>
                                    <td class="text-center ">
                                        {!! $entry->tanggal_mulai == $entry->tanggal_selesai
                                            ? format_tanggal($entry->tanggal_mulai)
                                            : format_tanggal($entry->tanggal_mulai) . ' - ' . format_tanggal($entry->tanggal_selesai) . '<br>' !!}
                                        <div class="text-center rounded-lg mx-auto" style="width: 3rem; background-color:#e1f0fc">
                                            <em class="text-primary">{{ $entry->lama_hari }} hari</em>
                                        </div>
                                    </td>
                                    <td>
                                        @if ($entry->status == 'draft')
                                            <button wire:click="proses({{ $entry->id }})" type="button" class="btn btn-sm btn-primary">
                                                Ajukan <i class="fas fa-share"></i>
                                            </button>
                                        @elseif ($entry->status == 'diajukan')
                                            <button {{ $this->authRole == 'Super Admin' || $this->authRole == 'Admin' ? 'wire:click=proses(' . $entry->id . ')' : '' }}
                                                type="button" class="btn btn-sm btn-warning">
                                                Verifikasi
                                            </button>
                                        @elseif ($entry->status == 'diproses')
                                            <button {{ $this->authRole == 'Super Admin' ? 'wire:click=proses(' . $entry->id . ')' : '' }} type="button"
                                                class="btn btn-sm btn-primary">
                                                Proses
                                            </button>
                                        @elseif ($entry->status == 'disetujui')
                                            <button {{ $this->authRole == 'Super Admin' || $this->authRole == 'Admin' ? 'wire:click=proses(' . $entry->id . ')' : '' }}
                                                type="button" class="btn btn-sm btn-success">
                                                Diterima <i class="fas fa-circle-check"></i>
                                            </button>
                                        @elseif ($entry->status == 'ditolak')
                                            <button {{ $this->authRole == 'Super Admin' || $this->authRole == 'Admin' ? 'wire:click=proses(' . $entry->id . ')' : '' }}
                                                type="button" class="btn btn-sm btn-danger">
                                                Ditolak <i class="fas fa-circle-xmark"></i>
                                            </button>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($entry->status == 'disetujui')
                                            <a href="{{ route('pdf', $entry->id) }}" target="_blank" class="btn btn-sm btn-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        @else
                                            {{-- @if ($entry->status != 'disetujui' && $entry->status != 'diajukan' && $entry->status != 'diproses')
                                                <button wire:click="modal('edit',{{ $entry->id }})" type="button" class="btn btn-sm btn-warning">
                                                    <i class="fas fa-pencil"></i>
                                                </button>
                                            @endif --}}
                                        @endif

                                        @if (akses('d', $this->lastSegment))
                                            @if ($entry->status != 'disetujui' && $entry->status != 'diajukan' && $entry->status != 'diproses')
                                                <button wire:click="konfirmasi({{ $entry->id }}, '{{ format_nama(addslashes($entry->user->nama)) }}')" type="button"
                                                    class="btn btn-sm btn-danger">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            @endif
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center"><em>Tidak ditemukan</em></td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    <div class="d-flex justify-content-end align-items-center">
                        <div>
                            {{ $this->entries->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if (akses('c', $this->lastSegment))
        @teleport('body')
            <x-modal id="modalEntry" title="Tambah Entry" simpan="simpan" ukuran="lg">

                <input type="hidden" id="role_user" value="{{ $this->authRole ?? '' }}">

                <div class="row">
                    <div wire:ignore class="col">
                        <div class="form-group">
                            <label>Pegawai</label>
                            <select id="pegawaiSelect" class="form-control form-control-sm pegawaiSelect" @if ($this->authRole != 'Super Admin' && $this->authRole != 'Admin') disabled @endif>
                                @foreach ($this->pegawais as $pegawai)
                                    <option value="{{ $pegawai->id }}" @if ($pegawai->id == auth()->user()->id_pegawai) selected @endif>
                                        {{ $pegawai->nama . ' -- ' . $pegawai->nip }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col">
                        <div class="form-group">
                            <label>Telepon</label>
                            <input wire:model.defer="telp" class="form-control @error('telp') is-invalid @enderror" placeholder="..."></input>
                            @error('telp')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col">
                        <div class="form-group">
                            <label>Rentang Tanggal</label>

                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <div class="input-group-text">
                                        <i class="fas fa-calendar"></i>
                                    </div>
                                </div>

                                <div wire:ignore class="flex-fill">
                                    <input type="text" id="daterange" class="form-control daterange-cus" placeholder="Pilih rentang tanggal">
                                </div>

                                <div class="input-group-append">
                                    <div class="input-group-text hitung-rentang">
                                        {{ $this->lamaHari }}&nbsp;hari
                                    </div>
                                </div>
                            </div>

                            @error('tanggal_mulai')
                                <div class="text-danger" style="font-size:12px">{{ $message }}</div>
                            @enderror

                            @error('tanggal_selesai')
                                <div class="text-danger" style="font-size:12px">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div wire:ignore class="col">
                        <div class="form-group">
                            <label>Jenis Cuti</label>
                            <select id="jenisCutiSelect" class="form-control form-control-sm jenisCutiSelect">
                                @foreach ($this->cutis($this->id_pegawai) as $cuti)
                                    <option value="{{ $cuti['id'] }}">
                                        {{ $cuti['jenis'] }} (Sisa: {{ $cuti['sisa'] }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col">
                        <div class="form-group">
                            <label>Alasan</label>
                            <textarea wire:model.defer="alasan" class="form-control @error('alasan') is-invalid @enderror" placeholder="..."></textarea>
                            @error('alasan')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>
                    <div class="col">
                        <div class="form-group">
                            <label>Alamat</label>
                            <textarea wire:model.defer="alamat" class="form-control @error('alamat') is-invalid @enderror" placeholder="..."></textarea>
                            @error('alamat')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>
                </div>
                <div id="file_wrapper" class="row d-none">
                    <div class="col-6">
                        <div class="form-group">
                            <label>File</label>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" id="customFile">
                                <label class="custom-file-label text-muted @error('file') is-invalid @enderror" for="customFile">pilih file</label>
                            </div>
                            @error('file')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>
                </div>
            </x-modal>
        @endteleport
    @endif

    @teleport('body')
        <x-modal id="modalEdit" title="Edit Entry" simpan="ubah" ukuran="lg">

            <input type="hidden" id="role_user" value="{{ $this->authRole ?? '' }}">

            <div class="row">
                <div wire:ignore class="col">
                    <div class="form-group">
                        <label>Pegawai</label>
                        <select wire:model.defer="id_pegawai" id="pegawaiSelectEdit" class="form-control form-control-sm pegawaiSelect" disabled>
                            @foreach ($this->entries as $entry)
                                <option value="{{ $entry->id_pegawai }}">
                                    {{ $entry->user->nama }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col">
                    <div class="form-group">
                        <label>Telepon</label>
                        <input wire:model.defer="telp" class="form-control @error('telp') is-invalid @enderror" placeholder="...">
                        @error('telp')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col">
                    <div class="form-group">
                        <label>Rentang Tanggal</label>

                        <div class="input-group">
                            <div class="input-group-prepend">
                                <div class="input-group-text">
                                    <i class="fas fa-calendar"></i>
                                </div>
                            </div>

                            <div wire:ignore class="flex-fill">
                                <input type="text" id="daterangeEdit" class="form-control daterange-cus-edit" placeholder="Pilih rentang tanggal">
                            </div>

                            <div class="input-group-append">
                                <div class="input-group-text hitung-rentang">
                                    {{ $this->lamaHari }}&nbsp;hari
                                </div>
                            </div>
                        </div>

                        @error('tanggal_mulai')
                            <div class="text-danger" style="font-size:12px">{{ $message }}</div>
                        @enderror

                        @error('tanggal_selesai')
                            <div class="text-danger" style="font-size:12px">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                {{-- <div wire:ignore class="col">
                    <div class="form-group">
                        <label>Jenis Cuti</label>
                        <select id="jenisCutiEdit" class="form-control form-control-sm jenisCutiEdit">
                            @foreach ($this->cutis($this->id_pegawai) as $cuti)
                                <option value="{{ $cuti['id'] }}">
                                    {{ $cuti['jenis'] }} (Sisa: {{ $cuti['sisa'] }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div> --}}
                <div wire:ignore class="col">
                    <div class="form-group">
                        <label>Jenis Cuti</label>
                        <select id="jenisCutiEdit" wire:model="id_cuti_kuota" class="form-control form-control-sm jenisCutiEdit">
                            @foreach ($this->cutis($this->id_pegawai) as $cuti)
                                <option value="{{ $cuti['id'] }}">
                                    {{ $cuti['jenis'] }} (Sisa: {{ $cuti['sisa'] }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col">
                    <div class="form-group">
                        <label>Alasan</label>
                        <textarea wire:model.defer="alasan" class="form-control @error('alasan') is-invalid @enderror" placeholder="..."></textarea>
                        @error('alasan')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>
                </div>
                <div class="col">
                    <div class="form-group">
                        <label>Alamat</label>
                        <textarea wire:model.defer="alamat" class="form-control @error('alamat') is-invalid @enderror" placeholder="..."></textarea>
                        @error('alamat')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>
                </div>
            </div>
            <div id="file_wrapper" class="row d-none">
                <div class="col-6">
                    <div class="form-group">
                        <label>File</label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="customFile">
                            <label class="custom-file-label text-muted @error('file') is-invalid @enderror" for="customFile">pilih file</label>
                        </div>
                        @error('file')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>
                </div>
            </div>
        </x-modal>
    @endteleport

</div>

@push('scripts')
    <script>
        const pegawai = $(".pegawaiSelect");
        const jenis = $(".jenisCutiSelect");
        const tanggal = $('.daterange-cus');
        const tanggalEdit = $('.daterange-cus-edit');

        const filterSkpd = $('#selectSkpd');
        const selectedSkpd = $wire.get('filterSkpd')

        $(document).ready(function() {
            filterSkpd.select2();
            filterSkpd.val(selectedSkpd).trigger('change');
            filterSkpd.on('change', function() {
                $wire.set('filterSkpd', $(this).val());
            });

            moment.locale('id');
        });


        $('#modalEntry').on('shown.bs.modal', function() {
            $wire.set('id_pegawai', pegawai.val())
            $wire.set('id_cuti_kuota', jenis.val())
            pegawai.on('change', function() {
                $wire.set('id_pegawai', pegawai.val())
            });

            jenis.on('change', function() {
                $wire.set('id_cuti_kuota', $(this).val());
            });

            if (tanggal.data('daterangepicker')) {
                tanggal.data('daterangepicker').remove();
            }
            tanggal.daterangepicker({
                linkedCalendars: false,
                showCustomRangeLabel: false,
                autoUpdateInput: false,
                startDate: moment(),
                endDate: moment(),
                locale: {
                    format: 'D MMMM YYYY',
                    applyLabel: "Pilih",
                    cancelLabel: "Batal",
                    daysOfWeek: ["Min", "Sen", "Sel", "Rab", "Kam", "Jum", "Sab"],
                    monthNames: [
                        "Januari", "Februari", "Maret", "April", "Mei", "Juni",
                        "Juli", "Agustus", "September", "Oktober", "November", "Desember"
                    ]
                }
            });
            tanggal.on('apply.daterangepicker', function(ev, picker) {
                const start = picker.startDate.format('YYYY-MM-DD');
                const end = picker.endDate.format('YYYY-MM-DD');

                $(this).val(picker.startDate.format('D MMMM YYYY') + ' - ' + picker.endDate.format('D MMMM YYYY'));

                $wire.set('tanggal_mulai', start);
                $wire.set('tanggal_selesai', end);
            });

        });
        $('#modalEdit').on('shown.bs.modal', function() {
            if (tanggalEdit.data('daterangepicker')) {
                tanggalEdit.data('daterangepicker').remove();
            }

            const startDate = $wire.get('tanggal_mulai') ? moment($wire.get('tanggal_mulai')) : moment();
            const endDate = $wire.get('tanggal_selesai') ? moment($wire.get('tanggal_selesai')) : moment();

            tanggalEdit.daterangepicker({
                linkedCalendars: false,
                showCustomRangeLabel: false,
                autoUpdateInput: true,
                startDate: startDate,
                endDate: endDate,
                locale: {
                    format: 'D MMMM YYYY',
                    applyLabel: "Pilih",
                    cancelLabel: "Batal",
                    daysOfWeek: ["Min", "Sen", "Sel", "Rab", "Kam", "Jum", "Sab"],
                    monthNames: [
                        "Januari", "Februari", "Maret", "April", "Mei", "Juni",
                        "Juli", "Agustus", "September", "Oktober", "November", "Desember"
                    ]
                }
            });
            tanggalEdit.on('apply.daterangepicker', function(ev, picker) {
                const start = picker.startDate.format('YYYY-MM-DD');
                const end = picker.endDate.format('YYYY-MM-DD');

                $(this).val(picker.startDate.format('D MMMM YYYY') + ' - ' + picker.endDate.format('D MMMM YYYY'));

                $wire.set('tanggal_mulai', start);
                $wire.set('tanggal_selesai', end);
            });

        });
        Livewire.on('toast_success', (message) => {
            showToast('success', message);
        });
        Livewire.on('toast_fail', (message) => {
            showToast('error', message);
        });
        Livewire.on('modal', (payload) => {
            pegawai.select2({
                dropdownParent: '#modalEntry',
            });
            jenis.select2({
                dropdownParent: '#modalEntry',
            });

            $('#modalEntry').modal('show');
        });
        Livewire.on('modalEdit', () => {
            const pegawaiSelectEdit = $('#pegawaiSelectEdit');
            const jenisCutiEdit = $('#jenisCutiEdit');

            if (!pegawaiSelectEdit.hasClass('select2-hidden-accessible')) {
                pegawaiSelectEdit.select2({
                    dropdownParent: $('#modalEdit'),
                });
            }
            if (!jenisCutiEdit.hasClass('select2-hidden-accessible')) {
                jenisCutiEdit.select2({
                    dropdownParent: $('#modalEdit'),
                });
            }

            const selectedId = $wire.get('id_pegawai');
            const selectedIdJenis = $wire.get('id_cuti_kuota');
            if (selectedId) {
                pegawaiSelectEdit.val(selectedId).trigger('change');
            }
            if (selectedIdJenis) {
                jenisCutiEdit.val(selectedIdJenis).trigger('change');
            }

            $('#modalEdit').modal('show');
        });
        Livewire.on('cleaning', () => {
            $('#modalEntry').modal('hide');
            $('#modalEdit').modal('hide');
        });
        Livewire.on('konfirmasi', (payload) => {

            const data = payload[0] ?? payload;

            Swal.fire({
                title: 'Konfirmasi hapus',
                text: "Anda yakin ingin menghapus Entry " + data.jenis + " ?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {

                if (result.isConfirmed) {
                    Livewire.dispatch('hapus', {
                        id: data.id
                    });
                }

            });
        });
        Livewire.on('updateJenisCuti', (event) => {
            let saldos = (event[0]?.saldos) ?? [];
            let $select = $('#jenisCutiSelect');

            $select.empty();

            saldos.forEach(cuti => {
                let option = new Option(`${cuti.jenis} (Sisa: ${cuti.sisa})`, cuti.id, false, false);
                $select.append(option);
            });

            $select.trigger('change');
        });
        Livewire.on('updateJenisCutiEdit', (event) => {
            let saldos = (event[0]?.saldos) ?? [];
            let $select = $('#jenisCutiEdit');
            $select.empty();
            console.log(saldos);

            saldos.forEach(cuti => {
                let option = new Option(`${cuti.jenis} (Sisa: ${cuti.sisa})`, cuti.id, false, false);
                $select.append(option);
            });

            $select.val($wire.get('id_cuti_kuota')).trigger('change');
        });
    </script>
@endpush

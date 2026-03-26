<?php
use App\Models\CutiKuota;
use App\Models\CutiSaldo;
use App\Models\Pegawai;
use App\Models\SKPD as Skpd;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;

new class extends Component {
    use WithPagination;
    public $id_pegawai, $jenis, $tahun, $kuota, $terpakai, $sisa;

    public $editFieldRowId, $editFieldRowIdSaldo, $editFieldRowIdKuota;
    public $lastSegment;
    public $authRole, $authSkpd, $filterSkpd;

    public $searchSaldo = '';
    public $searchKuota = '';
    public $search = '';
    public $perPage = 10;

    protected $paginationTheme = 'bootstrap';
    protected $queryString = ['searchSaldo', 'searchKuota', 'perPage'];

    protected $messages = [
        'jenis.required' => 'Jenis cuti wajib diisi.',
        'tahun.required' => 'Tahun wajib diisi.',
        'kuota.required' => 'Kuota cuti wajib diisi.',
        'kuota.numeric' => 'Kuota cuti harus berupa angka.',
        'kuota.min' => 'Kuota cuti minimal 1 hari.',
        'terpakai.required' => 'Cuti terpakai wajib diisi.',
        'sisa.required' => 'Sisa cuti wajib diisi.',
    ];

    protected array $allowedModels = [
        'carry_over' => CutiKuota::class,
        'aktif' => CutiKuota::class,
    ];

    public function mount()
    {
        $this->authRole = auth()->user()->roles->first()->name;
        $this->authSkpd = auth()->user()->id_skpd;

        $this->filterSkpd = $this->authSkpd;

        $saldo = CutiSaldo::with(['user', 'kuotas'])
            ->get()
            ->pluck('id_user');

        $routeName = request()->route()?->getName();
        $this->lastSegment = $routeName ? collect(explode('.', $routeName))->last() : null;
    }

    #[Computed]
    public function saldos()
    {
        switch ($this->authRole) {
            case 'Super Admin':
                return CutiSaldo::with(['user', 'kuotas', 'skpd'])
                    ->when($this->filterSkpd, function ($query) {
                        $query->where('id_skpd', $this->filterSkpd);
                    })
                    ->when($this->searchSaldo, function ($query) {
                        $query->where(function ($q) {
                            $q->orWhereHas('user', function ($pegawai) {
                                $pegawai->where('nama', 'like', '%' . $this->searchSaldo . '%')->orWhere('nip', 'like', '%' . $this->searchSaldo . '%');
                            });

                            $q->orWhereHas('kuotas', function ($kuota) {
                                $kuota->where('jenis', 'like', '%' . $this->searchSaldo . '%');
                            });

                            $q->orWhere('tahun', 'like', '%' . $this->searchSaldo . '%');
                        });
                    })
                    ->orderBy('id_user')
                    ->orderBy('tahun')
                    ->paginate($this->perPage);
                break;

            case 'Admin':
                return CutiSaldo::with(['user', 'kuotas'])
                    ->where('id_skpd', $this->authSkpd)
                    ->when($this->searchSaldo, function ($query) {
                        $query->where(function ($q) {
                            $q->orWhereHas('user', function ($pegawai) {
                                $pegawai->where('nama', 'like', '%' . $this->searchSaldo . '%')->orWhere('nip', 'like', '%' . $this->searchSaldo . '%');
                            });

                            $q->orWhereHas('kuotas', function ($kuota) {
                                $kuota->where('jenis', 'like', '%' . $this->searchSaldo . '%');
                            });

                            $q->orWhere('tahun', 'like', '%' . $this->searchSaldo . '%');
                        });
                    })
                    ->orderBy('id_user')
                    ->orderBy('tahun')
                    ->paginate($this->perPage);
                break;
            default:
                break;
        }
    }

    #[Computed]
    public function kuotas()
    {
        return CutiKuota::when($this->searchKuota, function ($query) {
            $query->where('jenis', 'like', '%' . $this->searchKuota . '%');
        })->paginate($this->perPage);
    }

    #[Computed]
    public function skpds()
    {
        return Skpd::orderBy('nama', 'asc')->get();
    }

    #[Computed]
    public function pegawais()
    {
        // return Pegawai::where('id_skpd', $this->authSkpd)->get();
        return User::where('id_skpd', $this->authSkpd)->get();
    }

    public function modal(string $type)
    {
        $this->resetErrorBag();
        $this->resetValidation();
        $this->dispatch('modal', [
            'type' => $type,
        ]);
    }

    public function konfirmasi($id, $jenis, $type)
    {
        $this->dispatch('konfirmasi', [
            'id' => $id,
            'jenis' => $jenis,
            'type' => $type,
        ]);
    }

    public function editRow($id, $field, $value)
    {
        if ($field === 'jenis' || $field === 'kuota') {
            $this->editFieldRowIdKuota = $id . '-' . $field;
            $this->jenis = $value;
        }
    }

    public function ubah($id, $field, $value)
    {
        $data = CutiKuota::find($id);

        if (!$data) {
            return;
        }

        $data->update([
            $field => $value,
        ]);

        $this->editFieldRowIdKuota = null;

        $displayName = $field === 'role' ? $value : $data->name;
        $this->dispatch('toast_success', 'Role ' . $data->name . ' berhasil diubah.');
    }

    public function toggle(int $id, string $type)
    {
        if (!isset($this->allowedModels[$type])) {
            return;
        }

        $model = $this->allowedModels[$type];

        $cuti = $model::find($id);
        if (!$cuti) {
            return;
        }

        $cuti->update([
            $type => !$cuti->$type,
        ]);
        $this->dispatch('toast_success', 'Cuti ' . $cuti->jenis . ' berhasil diubah.');
    }

    public function simpanSaldo()
    {
        $this->validate([
            'jenis' => 'required',
            'tahun' => 'required',
            'kuota' => 'required',
            'terpakai' => 'required',
            'sisa' => 'required',
        ]);

        CutiSaldo::create([
            'id_user' => $this->id_pegawai,
            'id_cuti_kuota' => $this->jenis,
            'id_skpd' => $this->authSkpd,
            'tahun' => $this->tahun,
            'kuota' => $this->kuota,
            'terpakai' => $this->terpakai,
            'sisa' => $this->sisa,
        ]);
        $this->dispatch('toast_success', 'Saldo cuti berhasil ditambahkan.');
        $this->dispatch('cleaning');
        $this->reset(['tahun', 'kuota', 'terpakai', 'sisa']);
    }
    public function simpanKuota()
    {
        $this->validate([
            'jenis' => 'required|string|max:30',
            'kuota' => 'required|numeric|min:1',
        ]);

        CutiKuota::create([
            'jenis' => $this->jenis,
            'kuota' => $this->kuota,
            'carry_over' => 0,
            'aktif' => 0,
        ]);

        $this->dispatch('toast_success', 'Jenis cuti ' . $this->jenis . ' berhasil ditambahkan.');
        $this->dispatch('cleaning');
        $this->reset(['jenis', 'kuota']);
    }

    #[On('hapus')]
    public function hapus($id, string $type)
    {
        $model = $this->allowedModels[$type];
        $cuti = $model::findOrFail($id);
        $cuti->delete();
        $this->dispatch('toast_success', 'Jenis cuti ' . $cuti->jenis . ' berhasil dihapus.');
    }

    public function masal()
    {
        $tahun = now()->year;

        $pegawais = User::all();
        $kuotas = CutiKuota::all();

        $now = now();
        $batch = [];

        foreach ($pegawais as $pegawai) {
            foreach ($kuotas as $kuota) {
                $jenisKelamin = strtolower($pegawai->pegawai?->jenis_kelamin ?? '');

                if (strtolower($kuota->jenis) === 'melahirkan' && $jenisKelamin !== 'perempuan') {
                    continue;
                }

                $batch[] = [
                    'id_user' => $pegawai->id,
                    'id_cuti_kuota' => $kuota->id,
                    'id_skpd' => $pegawai->id_skpd,
                    'tahun' => $tahun,
                    'kuota' => $kuota->kuota,
                    'terpakai' => 0,
                    'sisa' => $kuota->kuota,
                    'expired' => $kuota->expired ?? null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        CutiSaldo::insertOrIgnore($batch);

        $this->dispatch('cleaning');
        $this->dispatch('toast_success', 'Tambah masal selesai.');
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
                Setting
            </b>
        </li>
        <li class="breadcrumb-item active h4 text-dark">
            <b>
                Cuti
            </b>
        </li>
    </ol>

    <div class="row mt-1">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="d-flex align-items-center justify-content-between">
                        <span>Setting Saldo</span>
                        @if (akses('c', $this->lastSegment))
                            <a wire:click="modal('saldo')" class="btn btn-sm btn-primary rounded pt-0 px-2 m-1">
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
                        <div wire:ignore class="col">
                            @if (akses('u', $this->lastSegment))
                                <select id="selectSkpd" class="form-control form-control-sm select2">
                                    <option value=""></option>
                                    @foreach ($this->skpds as $skpd)
                                        <option value="{{ $skpd->id_skpd }}">{{ $skpd->nama }}</option>
                                    @endforeach
                                </select>
                            @endif
                        </div>
                        <div class="col-3">
                            <input type="text" wire:model.live.debounce.500ms="searchSaldo" class="form-control" placeholder="ketik sesuatu...">
                        </div>

                    </div>

                    <table class="table table-hover table-bordered table-md text-center">
                        <thead>
                            <tr>
                                <th class="col-1">#</th>
                                <th class="text-left">Nama</th>
                                <th class="text-left">NIP</th>
                                <th>Tahun</th>
                                <th>Jenis</th>
                                <th>Kuota</th>
                                <th>Terpakai</th>
                                <th>Sisa</th>
                                <th>Expired</th>
                                @if (akses('d', $this->lastSegment))
                                    <th class="col-1">Aksi</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $grouped = collect($this->saldos->items())->groupBy(fn($item) => $item->id_user . '-' . $item->tahun);
                            @endphp


                            @foreach ($grouped as $group)
                                @php
                                    $rowspan = $group->count();
                                    $first = true;
                                @endphp

                                @foreach ($group as $saldo)
                                    <tr wire:key="saldo-{{ $saldo->id }}">

                                        @if ($first)
                                            <td rowspan="{{ $rowspan }}">
                                                {{ $loop->parent->iteration }}
                                            </td>
                                            <td rowspan="{{ $rowspan }}" class="text-left">
                                                {{ $saldo->user->nama ?? '---' }}
                                            </td>
                                            <td rowspan="{{ $rowspan }}" class="text-left">
                                                {{ $saldo->user->nip ?? '---' }}
                                            </td>
                                            <td rowspan="{{ $rowspan }}" class="text-left">
                                                @if (akses('u', $this->lastSegment))
                                                    <x-inline-input-edit :id="$saldo->id" field="tahun" :value="$saldo->tahun" :edit-field-row-id="$editFieldRowIdSaldo" />
                                                @else
                                                    {{ $saldo->tahun ?? '---' }}
                                                @endif
                                            </td>

                                            @php $first = false; @endphp
                                        @endif

                                        <td class="text-left">
                                            @if (akses('u', $this->lastSegment))
                                                <x-inline-input-edit :id="$saldo->id" field="jenis" :value="$saldo->kuotas->jenis" :edit-field-row-id="$editFieldRowIdSaldo" />
                                            @else
                                                {{ $saldo->kuotas->jenis ?? '---' }}
                                            @endif
                                        </td>
                                        <td>
                                            @if (akses('u', $this->lastSegment))
                                                <x-inline-input-edit :id="$saldo->id" field="kuota" :value="$saldo->kuota" :edit-field-row-id="$editFieldRowIdSaldo" />
                                            @else
                                                {{ $saldo->kuota ?? '---' }}
                                            @endif
                                        </td>
                                        <td>
                                            @if (akses('u', $this->lastSegment))
                                                <x-inline-input-edit :id="$saldo->id" field="terpakai" :value="$saldo->terpakai" :edit-field-row-id="$editFieldRowIdSaldo" />
                                            @else
                                                {{ $saldo->terpakai ?? '---' }}
                                            @endif
                                        </td>
                                        <td>
                                            @if (akses('u', $this->lastSegment))
                                                <x-inline-input-edit :id="$saldo->id" field="sisa" :value="$saldo->sisa" :edit-field-row-id="$editFieldRowIdSaldo" />
                                            @else
                                                {{ $saldo->sisa ?? '---' }}
                                            @endif
                                        </td>
                                        <td>
                                            @if (akses('u', $this->lastSegment))
                                                <x-inline-input-edit :id="$saldo->id" field="expired" :value="$saldo->expired" :edit-field-row-id="$editFieldRowIdSaldo" />
                                            @else
                                                {{ $saldo->expired ?? '---' }}
                                            @endif
                                        </td>
                                        @if (akses('d', $this->lastSegment))
                                            <td>
                                                <button wire:click="konfirmasi({{ $saldo->id }}, '{{ addslashes($saldo->jenis) }}','carry_over')" type="button"
                                                    class="btn btn-sm btn-danger">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        @endif

                                    </tr>
                                @endforeach
                            @endforeach

                        </tbody>
                    </table>
                    <div class="d-flex justify-content-end align-items-center">
                        <div>
                            {{ $this->saldos->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if (akses('c', $this->lastSegment))
        @teleport('body')
            <x-modal id="modalSettingCutiSaldo" title="Tambah Saldo" simpan="simpanSaldo" ukuran="lg" tombol="masal">

                <div class="row">
                    <div wire:ignore class="col">
                        <div class="form-group">
                            <label>Pegawai</label>
                            <select wire:model.defer="id_pegawai" class="form-control form-control-sm pegawaiSelect">
                                @foreach ($this->pegawais as $pegawai)
                                    <option value="{{ $pegawai->id }}">
                                        {{-- {{ $pegawai->user->nama . ' -- ' . $pegawai->user->nip }} --}}
                                        {{ $pegawai->nama . ' -- ' . $pegawai->nip }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div wire:ignore class="col-4">
                        <div class="form-group">
                            <label>Jenis<span class="text-danger">*</span></label>
                            <select wire:model.defer="jenis" class="form-control form-control-sm jenisSelect">
                                @foreach ($this->kuotas as $kuota)
                                    <option value="{{ $kuota->id }}">
                                        {{ $kuota->jenis }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col">
                        <div class="form-group">
                            <label>Tahun<span class="text-danger">*</span></label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <div class="input-group-text">
                                        <i class="fas fa-calendar"></i>
                                    </div>
                                </div>

                                <!-- WAJIB input, bukan div -->
                                <input type="text" class="form-control tahunSelect" placeholder="Pilih tahun">
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="form-group">
                            <label>Kuota<span class="text-danger">*</span></label>
                            <input wire:model.defer="kuota" type="number" class="form-control text-right @error('kuota') is-invalid @enderror " placeholder="...">
                            @error('kuota')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>
                    <div class="col">
                        <div class="form-group">
                            <label>Terpakai<span class="text-danger">*</span></label>
                            <input wire:model.defer="terpakai" type="number" class="form-control text-right @error('terpakai') is-invalid @enderror " placeholder="...">
                            @error('terpakai')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>
                    <div class="col">
                        <div class="form-group">
                            <label>Sisa<span class="text-danger">*</span></label>
                            <input wire:model.defer="sisa" type="number" class="form-control text-right @error('sisa') is-invalid @enderror " placeholder="...">
                            @error('sisa')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>
                </div>

            </x-modal>
        @endteleport

        @teleport('body')
            <x-modal id="modalSettingCutiKuota" title="Tambah Jenis" simpan="simpanKuota" ukuran="md">

                <div class="row">
                    <div class="col">
                        <div class="form-group">
                            <label>Jenis<span class="text-danger">*</span></label>
                            <input wire:model.defer="jenis" type="text" class="form-control @error('jenis') is-invalid @enderror" placeholder="...">
                            @error('jenis')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>
                    <div class="col">
                        <div class="form-group">
                            <label>Kuota<span class="text-danger">*</span></label>
                            <div class="input-group mb-2">
                                <input wire:model.defer="kuota" type="text" class="form-control text-right @error('kuota') is-invalid @enderror " placeholder="...">
                                <div class="input-group-append">
                                    <div class="input-group-text">hari</div>
                                </div>
                                @error('kuota')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                            @error('kuota')
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

    @if (akses('u', $this->lastSegment))
        <div class="row mt-1">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="d-flex align-items-center justify-content-between">
                            <span>Setting Kuota</span>
                            @if (akses('c', $this->lastSegment))
                                <a wire:click="modal('kuota')" class="btn btn-sm btn-primary rounded pt-0 px-2 m-1">
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

                            <div class="col-3 offset-8">
                                <input type="text" wire:model.live.debounce.500ms="searchKuota" class="form-control" placeholder="ketik sesuatu...">
                            </div>

                        </div>

                        <table class="table table-hover table-bordered table-md text-center">
                            <thead>
                                <tr>
                                    <th class="col-1">#</th>
                                    <th class="text-left">Jenis Cuti</th>
                                    <th>Kuota / Tahun</th>
                                    <th>Carry Over</th>
                                    <th>Aktif</th>
                                    @if (akses('d', $this->lastSegment))
                                        <th class="col-1">Aksi</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($this->kuotas as $kuota)
                                    <tr wire:key="kuota-{{ $kuota->id }}">
                                        <td>
                                            {{ $loop->iteration }}
                                        </td>
                                        <td class="text-left">
                                            @if (akses('u', $this->lastSegment))
                                                <x-inline-input-edit :id="$kuota->id" field="jenis" :value="$kuota->jenis" :edit-field-row-id="$editFieldRowIdKuota" />
                                            @else
                                                {{ $kuota->jenis ?? '---' }}
                                            @endif
                                        </td>
                                        <td>
                                            @if (akses('u', $this->lastSegment))
                                                <x-inline-input-edit :id="$kuota->id" field="kuota" :value="$kuota->kuota" :edit-field-row-id="$editFieldRowIdKuota" />
                                            @else
                                                {{ $kuota->kuota ?? '---' }}
                                            @endif
                                        </td>
                                        <td>
                                            <label class="custom-switch pl-0">
                                                <input @if (akses('u', $this->lastSegment)) wire:change="toggle({{ $kuota->id }}, 'carry_over')" @else disabled @endif
                                                    type="checkbox" class="custom-switch-input" @checked($kuota->carry_over)>
                                                <span class="custom-switch-indicator"></span>
                                            </label>
                                        </td>
                                        <td>
                                            <label class="custom-switch pl-0">
                                                <input @if (akses('u', $this->lastSegment)) wire:change="toggle({{ $kuota->id }}, 'aktif')" @else disabled @endif type="checkbox"
                                                    class="custom-switch-input" @checked($kuota->aktif)>
                                                <span class="custom-switch-indicator"></span>
                                            </label>
                                        </td>
                                        @if (akses('d', $this->lastSegment))
                                            <td>
                                                <button wire:click="konfirmasi({{ $kuota->id }}, '{{ addslashes($kuota->jenis) }}','carry_over')" type="button"
                                                    class="btn btn-sm btn-danger">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        @endif
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center"><em>Tidak ditemukan</em></td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>

                        <div class="d-flex justify-content-end align-items-center">
                            <div>
                                {{ $this->kuotas->links() }}
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    @endif
</div>


@push('scripts')
    <script>
        const filterSkpd = $('#selectSkpd');
        const selectedSkpd = $wire.get('filterSkpd')

        $(document).ready(function() {
            filterSkpd.select2({
                placeholder: "-- pilih --",
            });
            filterSkpd.on('change', function() {
                $wire.set('filterSkpd', $(this).val());
            });
        });

        $('#modalSettingCutiSaldo').on('shown.bs.modal', function() {
            const pegawai = $(".pegawaiSelect");
            const jenis = $(".jenisSelect");
            const tahun = $('.tahunSelect');
            $wire.set('id_pegawai', pegawai.val())
            $wire.set('jenis', jenis.val())
            pegawai.on('change', function() {
                $wire.set('id_pegawai', pegawai.val())
            });
            jenis.on('change', function() {
                $wire.set('jenis', jenis.val())
            });

            if (!tahun.data('daterangepicker')) {

                tahun.datepicker({
                    autoclose: true,
                    format: "yyyy",
                    viewMode: "years",
                    minViewMode: "years"
                });
            }

            tahun.on('change', function() {
                $wire.set('tahun', tahun.val())
            });

        });

        Livewire.on('toast_success', (message) => {
            showToast('success', message);
        });
        Livewire.on('toast_fail', (message) => {
            showToast('error', message);
        });
        Livewire.on('modal', (payload) => {
            const data = payload[0] ?? payload;

            if (data.type == 'kuota') {

                $('#modalSettingCutiKuota').modal('show');

            } else if (data.type == 'saldo') {
                $(".pegawaiSelect").select2({
                    dropdownParent: '#modalSettingCutiSaldo',
                });
                $(".jenisSelect").select2({
                    dropdownParent: '#modalSettingCutiSaldo',
                });
                $('#modalSettingCutiSaldo').modal('show');
            }


        });
        Livewire.on('cleaning', () => {
            $('#modalSettingCutiKuota').modal('hide');
            $('#modalSettingCutiSaldo').modal('hide');
        });
        Livewire.on('konfirmasi', (payload) => {

            const data = payload[0] ?? payload;

            Swal.fire({
                title: 'Konfirmasi hapus',
                text: "Anda yakin ingin menghapus Jenis Cuti " + data.jenis + " ?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {

                if (result.isConfirmed) {
                    Livewire.dispatch('hapus', {
                        id: data.id,
                        type: data.type,
                    });
                }

            });
        });
    </script>
@endpush

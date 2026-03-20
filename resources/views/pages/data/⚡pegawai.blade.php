<?php

use App\Models\Pegawai;
use App\Models\SKPD as Skpd;
use App\Services\SyncDataPegawai;
use Illuminate\Support\Facades\Http;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
new class extends Component {
    use WithPagination;
    public $editFieldRowId, $lastSegment;
    public $authRole, $authSkpd, $filterSkpd;

    public $search = '';
    public $perPage = 10;

    protected $paginationTheme = 'bootstrap';
    protected $queryString = ['search', 'perPage'];

    public function mount()
    {
        $this->authRole = auth()->user()->roles->first()->name;
        $this->authSkpd = auth()->user()->id_skpd;

        $this->filterSkpd = $this->authSkpd;

        $routeName = request()->route()?->getName();
        $this->lastSegment = $routeName ? collect(explode('.', $routeName))->last() : null;
    }

    #[Computed]
    public function skpds()
    {
        return Skpd::orderBy('nama', 'asc')->get();
    }

    #[Computed]
    public function pegawais()
    {
        switch ($this->authRole) {
            case 'Super Admin':
                return Pegawai::with('skpd')
                    ->when($this->filterSkpd, function ($query) {
                        $query->where('id_skpd', $this->filterSkpd);
                    })
                    ->when($this->search, function ($query) {
                        $query->where(function ($q) {
                            $q->where('nama', 'like', "%{$this->search}%")
                                ->orWhere('nip', 'like', "%{$this->search}%")
                                ->orWhere('jabatan', 'like', "%{$this->search}%")
                                ->orWhereHas('skpd', function ($s) {
                                    $s->where('nama', 'like', "%{$this->search}%");
                                });
                        });
                    })
                    ->paginate($this->perPage);
                break;

            case 'Admin':
                return Pegawai::with('skpd')
                    ->where('id_skpd', $this->authSkpd)
                    ->when($this->search, function ($query) {
                        $search = $this->search;

                        $query->where(function ($q) use ($search) {
                            $q->where('nama', 'like', "%{$search}%")
                                ->orWhere('nip', 'like', "%{$search}%")
                                ->orWhere('jabatan', 'like', "%{$search}%")
                                ->orWhereHas('skpd', function ($s) use ($search) {
                                    $s->where('nama', 'like', "%{$search}%");
                                });
                        });
                    })
                    ->orderBy('nama', 'asc')
                    ->paginate($this->perPage);

            default:
                break;
        }
    }

    public function sync()
    {
        try {
            app(SyncDataPegawai::class)->handle();
            $this->dispatch('toast_success', 'User berhasil di-sync.');
        } catch (Throwable $e) {
            $this->dispatch('toast_fail', 'User gagal di-sync.');
        }
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
                Pegawai
            </b>
        </li>
    </ol>

    <div class="row mt-1">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="d-flex align-items-center justify-content-between">
                        <span>Data Pegawai</span>
                        @if (akses('c', $this->lastSegment))
                            <a wire:click="sync" class="btn btn-sm btn-warning rounded pt-0 px-2 m-1">
                                <i wire:loading.class="fa-spin" wire:target="sync" class="fas fa-sync"></i> Sync
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
                            <input type="text" wire:model.live.debounce.500ms="search" class="form-control" placeholder="ketik sesuatu...">
                        </div>

                    </div>

                    <table class="table table-hover table-bordered table-md text-center">
                        <thead>
                            <tr>
                                <th class="col-1">#</th>
                                <th class="text-left">Nama</th>
                                <th class="text-left">Pimpinan</th>
                                <th class="text-left">NIP</th>
                                <th class="text-left">Jabatan</th>
                                <th class="text-left">Atasan</th>
                                <th class="text-left">SKPD</th>
                                @if (akses('d', $this->lastSegment))
                                    <th class="col-1">Aksi</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($this->pegawais as $pegawai)
                                <tr>
                                    <td>
                                        {{ $loop->iteration }}
                                    </td>
                                    <td>
                                        <img src="{{ $pegawai->foto ?? asset('stisla/img/avatar/tapin.svg') }}" alt="Avatar"
                                            onerror="this.onerror=null;this.src='{{ asset('stisla/img/avatar/tapin.svg') }}';" class="img-fluid rounded shadow-sm"
                                            style="max-height: 200px;">
                                        {{-- <img src="{{ $pegawai->foto }}" class="img-fluid rounded shadow-sm" style="max-height: 200px;"> --}}
                                    </td>
                                    <td class="text-left">
                                        @if (akses('u', $this->lastSegment))
                                            <x-inline-input-edit :id="$pegawai->id" field="nama" :value="$pegawai->nama" :edit-field-row-id="$editFieldRowId" />
                                        @else
                                            {{ format_nama($pegawai->nama) ?? '---' }}
                                        @endif
                                    </td>
                                    <td class="text-left">
                                        @if (akses('u', $this->lastSegment))
                                            <x-inline-input-edit :id="$pegawai->id" field="nip" :value="$pegawai->nip" :edit-field-row-id="$editFieldRowId" />
                                        @else
                                            {{ format_nama($pegawai->nip) ?? '---' }}
                                        @endif
                                    </td>
                                    {{-- <td class="text-left">
                                        @if (akses('u', $this->lastSegment))
                                            <x-inline-input-edit :id="$pegawai->id" field="pangkat" :value="$pegawai->pangkat" :edit-field-row-id="$editFieldRowId" />
                                        @else
                                            {{ format_nama($pegawai->pangkat) ?? '---' }}
                                        @endif
                                    </td> --}}
                                    <td class="text-left">
                                        @if (akses('u', $this->lastSegment))
                                            <x-inline-input-edit :id="$pegawai->id" field="jabatan" :value="$pegawai->jabatan" :edit-field-row-id="$editFieldRowId" />
                                        @else
                                            {{ format_nama($pegawai->jabatan) ?? '---' }}
                                        @endif
                                    </td>
                                    <td class="text-left">
                                        {{ $pegawai->id_atasan === null
                                            ? '---'
                                            : ($pegawai->id_atasan === 0 && $pegawai->id_skpd !== null
                                                ? \App\Models\Pegawai::where('id_skpd', $pegawai->id_skpd)->whereNull('id_atasan')->value('nama')
                                                : \App\Models\Pegawai::where('id_skpd', $pegawai->id_skpd)->where('id_struktur', $pegawai->id_atasan)->value('nama') ?? '---') }}
                                    </td>
                                    <td class="text-left">
                                        {{ $pegawai->skpd->nama ?? '---' }}
                                    </td>
                                    @if (akses('d', $this->lastSegment))
                                        <td>
                                            <button wire:click="konfirmasi({{ $pegawai->id }}, '{{ format_nama(addslashes($pegawai->nama)) }}')" type="button"
                                                class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    @endif
                                    {{-- @endif --}}
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center"><em>Tidak ditemukan</em></td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    <div class="d-flex justify-content-end align-items-center">
                        <div>
                            {{ $this->pegawais->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        const filterSkpd = $('#selectSkpd');
        const selectedSkpd = $wire.get('filterSkpd')

        $(document).ready(function() {
            filterSkpd.select2({
                placeholder: "-- pilih --",
                allowClear: true
            });
            filterSkpd.on('change', function() {
                $wire.set('filterSkpd', $(this).val());
            });
        });


        Livewire.on('toast_success', (message) => {
            showToast('success', message);
        });
        Livewire.on('toast_fail', (message) => {
            showToast('error', message);
        });
    </script>
@endpush

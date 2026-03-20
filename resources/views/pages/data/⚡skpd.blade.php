<?php

use App\Models\SKPD as Skpd;
use App\Services\SyncDataSkpd;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;

new class extends Component {
    use WithPagination;
    public $editFieldRowId, $lastSegment;

    public $search = '';
    public $perPage = 10;

    protected $paginationTheme = 'bootstrap';
    protected $queryString = ['search', 'perPage'];

    public function mount()
    {
        $routeName = request()->route()?->getName();
        $this->lastSegment = $routeName ? collect(explode('.', $routeName))->last() : null;
    }

    #[Computed]
    public function skpds()
    {
        return Skpd::when($this->search, function ($query) {
            $query->where('nama', 'like', '%' . $this->search . '%')->orderBy('nama');
        })->paginate($this->perPage);
    }

    public function sync()
    {
        try {
            app(SyncDataSkpd::class)->handle();
            $this->dispatch('toast_success', 'SKPD berhasil di-sync.');
        } catch (Throwable $e) {
            $this->dispatch('toast_fail', 'SKPD gagal di-sync.');
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
                SKPD
            </b>
        </li>
    </ol>

    <div class="row mt-1">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="d-flex align-items-center justify-content-between">
                        <span>Data SKPD</span>
                        @if (akses('u', $this->lastSegment))
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
                        <div class="col-3 offset-8">
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
                                <th class="text-left">Alamat</th>
                                <th class="text-left">Telepon</th>
                                @if (akses('d', $this->lastSegment))
                                    <th class="col-1">Aksi</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($this->skpds as $skpd)
                                <tr>
                                    <td>
                                        {{ $loop->iteration }}
                                    </td>
                                    <td class="text-left">
                                        {{ $skpd->nama ?? '---' }}
                                    </td>
                                    <td class="text-left">
                                        {{ format_nama($skpd->pimpinan) ?? '---' }}
                                    </td>
                                    <td class="text-left">
                                        {{ $skpd->nip_pimpinan ?? '---' }}
                                    </td>
                                    <td class="text-left">
                                        {{ $skpd->jabatan_pimpinan ?? '---' }}
                                    </td>
                                    <td class="text-left">
                                        {{ $skpd->alamat ?? '---' }}
                                    </td>
                                    <td class="text-left">
                                        {{ $skpd->telepon ?? '---' }}
                                    </td>
                                    @if (akses('d', $this->lastSegment))
                                        <td>
                                            <button wire:click="konfirmasi({{ $skpd->id }}, '{{ format_nama(addslashes($skpd->nama)) }}')" type="button"
                                                class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    @endif
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
                            {{ $this->skpds->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


@push('scripts')
    <script>
        Livewire.on('toast_success', (message) => {
            showToast('success', message);
        });
        Livewire.on('toast_fail', (message) => {
            showToast('error', message);
        });
    </script>
@endpush

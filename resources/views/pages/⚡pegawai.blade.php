<?php

use Livewire\Component;
use Illuminate\Support\Facades\Http;
use Livewire\Attributes\On;

new class extends Component {
    public $pegawais = [];
    public $skpds;
    public $meta = [];
    public $error = null;

    public $page = 1;
    public $limit = 10;
    public $total = 0;
    public $lastPage = 1;
    public $search = '';
    public $skpd = '';

    public $role;
    public $editFieldRowId;
    public $iteration = 0;

    public $selectedPegawai = null;

    public function showRole($nip)
    {
        $this->selectedPegawai = collect($this->pegawais)->firstWhere('struktur.nip', $nip);
        dd($this->selectedPegawai);
        $this->dispatch('openRoleModal');
    }

    public function mount()
    {
        $this->loadData();
    }

    public function loadData()
    {
        try {
            $apiUrl = 'http://diskominfo.app.test/api/v1';

            $response_skpd = Http::timeout(10)
                ->acceptJson()
                ->withHeaders([
                    'token-profil' => env('prisma_token'),
                ])
                ->get($apiUrl . '/offices');

            if (!$response_skpd->successful()) {
                $this->error = 'Gagal mengambil data SKPD.';
                return;
            }

            $strukturUrl = $apiUrl . '/struktur';

            if (!empty($this->skpd)) {
                $strukturUrl = $apiUrl . "/struktur/skpd/{$this->skpd}";
            }
            $response_struktur = Http::timeout(10)
                ->acceptJson()
                ->withHeaders([
                    'token-profil' => env('prisma_token'),
                ])
                ->get($strukturUrl, [
                    'page' => $this->page,
                    'limit' => $this->limit,
                    'search' => $this->search,
                ]);

            if (!$response_struktur->successful()) {
                $this->error = 'Gagal mengambil data Struktur.';
                return;
            }

            $data = $response_skpd->json()['data'] ?? [];
            $meta = $response_struktur->json()['meta'] ?? [];

            $skpd = collect($response_skpd->json()['data'] ?? []);
            $strukturData = collect($response_struktur->json()['data'] ?? []);

            $this->skpds = isset($data['id']) ? [$data] : $data;
            $this->total = $meta['total'] ?? $strukturData->count();
            $this->lastPage = $meta['last_page'] ?? 1;

            $skpdItem = $skpd;

            $strukturData = collect($response_struktur->json()['data'] ?? []);

            $this->pegawais = $strukturData
                ->map(function ($struktur) use ($skpdItem) {
                    return [
                        'skpd' => $skpdItem,
                        'struktur' => $struktur,
                        'role' => 'admin',
                    ];
                })
                ->toArray();

            // dd($this->pegawais);
        } catch (\Exception $e) {
            $this->error = 'Error koneksi: ' . $e->getMessage();
        }
    }

    public function updatedSearch()
    {
        $this->page = 1;
        $this->loadData();
    }

    #[On('select-updated')]
    public function updatedSkpd($value)
    {
        $this->skpd = $value;
        $this->page = 1;
        $this->loadData();
        $this->dispatch('update-skpd');
    }

    public function nextPage()
    {
        if ($this->page < $this->lastPage) {
            $this->page++;
            $this->loadData();
        }
    }

    public function prevPage()
    {
        if ($this->page > 1) {
            $this->page--;
            $this->loadData();
        }
    }

    public function goToPage($number)
    {
        if ($number >= 1 && $number <= $this->lastPage) {
            $this->page = $number;
            $this->loadData();
        }
    }

    public function formatNama($nama)
    {
        return collect(explode(',', $nama ?? ''))
            ->map(function ($item, $i) {
                return $i === 0 ? ucwords(strtolower(trim($item))) : trim($item);
            })
            ->implode(', ');
    }

    public function editRow($id, $field, $value)
    {
        $this->editFieldRowId = $id . '-' . $field;

        if ($field === 'role') {
            $this->role = $value;
        }
    }

    public function ubah($id, $field, $value)
    {
        dd($id, $field, $value);
        // $data = ModelMenu::find($id);

        // if (!$data) {
        //     return;
        // }

        // if ($field === 'parent_id') {
        //     $value = empty($value) ? null : $value;
        // } elseif ($field === 'urutan') {
        //     $value = $value ?? 0;
        // }

        // $data->update([
        //     $field => $value,
        // ]);

        // if ($field === 'segment' && !empty($value)) {
        //     $namaSegment = Str::studly(Str::afterLast($value, '/'));
        //     $folder = collect(explode('/', $value))
        //         ->slice(0, -1)
        //         ->map(fn($s) => Str::studly($s))
        //         ->implode('/');

        //     $segmentPath = $folder ? $folder . '/' . $namaSegment : $namaSegment;

        //     $komponenPath = app_path("Livewire/Backend/{$segmentPath}.php");

        //     if (!file_exists($komponenPath)) {
        //         Artisan::call('make:livewire', [
        //             'name' => 'Backend' . ($folder ? '/' . $folder : '') . '/' . $namaSegment,
        //         ]);
        //     }
        // }
    }

    public function updatingBrandName()
    {
        $this->dispatch('brandSelected');
        $this->iteration++;
    }
};
?>

<div>
    <div class="main-content">
        <section class="section">

            <ol class="breadcrumb bg-white pb-1 shadow-sm mb-4 py-4 pl-4">
                <li class="breadcrumb-item h4 ">
                    <a wire:navigate href="{{ route('dashboard') }}"><b>Home</b></a>
                </li>
                <li class="breadcrumb-item active h4 text-dark" aria-current="page">
                    <b>
                        Data
                    </b>
                </li>
                <li class="breadcrumb-item active h4 text-dark" aria-current="page">
                    <b>
                        Pegawai
                    </b>
                </li>
            </ol>

            <div class="row mt-1">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4>Data Pegawai
                                <a href="" class="btn btn-sm btn-primary rounded pt-0 px-2 m-1">
                                    <i class="fas fa-circle-plus"></i> Baru
                                </a>
                            </h4>
                        </div>
                        <div class="m-3">

                            <div class="row">
                                <div class="col">
                                    <div class="form-group">
                                        <label>Cari</label>
                                        <div class="input-group mb-3">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                            </div>
                                            <input wire:model.live.debounce.500ms="search" type="text" class="form-control" placeholder="nama / nip ...">
                                        </div>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="form-group">
                                        <label>SKPD</label>
                                        <div wire:ignore class="input-group mb-3">

                                            <select wire:model.live="skpd" class="form-control select-skpd">
                                                <option value="">Semua SKPD</option>

                                                @foreach ($skpds as $skpd)
                                                    <option value="{{ $skpd['id_skpd'] }}">
                                                        {{ $skpd['name'] }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>


                            <div wire:loading wire:target="search,loadData">
                                <small class="text-muted">Mencari data...</small>
                            </div>
                            <table class="table table-hover table-bordered border">
                                <thead>
                                    <tr>
                                        <th scope="col">No</th>
                                        <th scope="col">Nama</th>
                                        <th scope="col">NIP</th>
                                        <th scope="col">Role</th>
                                        <th scope="col">Atasan</th>
                                        <th scope="col">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($pegawais as $pegawai)
                                        <tr>
                                            <th scope="row">{{ ($page - 1) * $limit + $loop->iteration }}</th>
                                            <td>
                                                {{ $this->formatNama($pegawai['struktur']['nama'] ?? '-') }}
                                            </td>

                                            <td>{{ $pegawai['struktur']['nip'] }}</td>
                                            <td class="text-center">
                                                <button type="button" class="badge badge-light border-0" wire:click="showRole('{{ $pegawai['struktur']['nip'] }}')">
                                                    {{ $pegawai['role'] ?? 'Null' }}
                                                </button>

                                                {{-- <button type="button" class="badge bg-light border-0" data-toggle="modal" data-target="#roleModal">
                                                    {{ $pegawai['role'] ?? 'Null' }}
                                                </button> --}}



                                                {{-- <span class="badge badge-light" data-bs-toggle="modal" data-bs-target="#roleModal{{ $pegawai['struktur']['nip'] }}">
                                                    {{ $pegawai['role'] ?? 'Null' }}
                                                </span> --}}

                                                {{-- <button type="button" class="badge bg-light border-0" data-bs-toggle="modal"
                                                    data-bs-target="#roleModal{{ $pegawai['struktur']['nip'] }}">
                                                    {{ $pegawai['role'] ?? 'Null' }}
                                                </button> --}}

                                                {{-- @if ($editFieldRowId == $pegawai['struktur']['nip'] . '-role') --}}
                                                {{-- <div wire:ignore>
                                                    <select class="form-control select-role">
                                                        <option value="1">Admin</option>
                                                        <option value="2">Atasan</option>
                                                    </select>
                                                </div> --}}
                                                {{-- @else
                                                    <div wire:click="editRow('{{ $pegawai['struktur']['nip'] }}', 'role', '{{ $pegawai['role'] }}')">
                                                        <span class="badge badge-light">{{ $pegawai['role'] ?? 'Null' }}</span>
                                                    </div>
                                                @endif --}}


                                                {{-- @if ($editFieldRowId == $pegawai['struktur']['nip'] . '-role')
                                                    <div wire:key="selecting-role-{{ $iteration }}">

                                                        <select wire:model.live="role" class="form-control select-role">
                                                            <option value="1">Admin</option>
                                                            <option value="2">Atasan</option>

                                                        </select>
                                                        </>
                                                    @else
                                                        <div wire:click="editRow('{{ $pegawai['struktur']['nip'] }}', 'role', '{{ $pegawai['role'] }}')" class="edit-icon"
                                                            style="cursor: pointer; position: relative;">
                                                            <span class="badge badge-light">{{ $pegawai['role'] ?? 'Null' }}</span>
                                                            <i class="bx bx-edit-alt text-warning icon-hover" style="position: absolute; right: 0;"></i>
                                                        </div>
                                                @endif --}}
                                            </td>
                                            <td>
                                                @if ($pegawai['struktur']['parent_id'] == 0)
                                                    {{ $this->formatNama($pegawai['skpd']['pimpinan'] ?? '-') }}
                                                @else
                                                    {{ $this->formatNama(collect($pegawais)->pluck('struktur')->firstWhere('id_struktur', $pegawai['struktur']['parent_id'])['nama'] ?? '-') }}
                                                @endif
                                            </td>
                                            <td>
                                                <a href="" class="btn btn-sm btn-warning"><i class="fas fa-key"></i></a>
                                                <a href="" class="btn btn-sm btn-danger"><i class="fas fa-toggle-off"></i></a>
                                            </td>
                                        </tr>

                                        <!-- Modal -->


                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center">Tidak ada data</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>

                            <nav aria-label="Page navigation">
                                <ul class="pagination justify-content-end">


                                    <li class="page-item {{ $page == 1 ? 'disabled' : '' }}">
                                        <a wire:click="prevPage" class="page-link" style="cursor:pointer;">
                                            Back
                                        </a>
                                    </li>
                                    @for ($i = max(1, $page - 2); $i <= min($lastPage, $page + 2); $i++)
                                        <li class="page-item {{ $page == $i ? 'active' : '' }}">
                                            <a wire:click="goToPage({{ $i }})" class="page-link" style="cursor:pointer;">
                                                {{ $i }}
                                            </a>
                                        </li>
                                    @endfor


                                    <li class="page-item {{ $page == $lastPage ? 'disabled' : '' }}">
                                        <a wire:click="nextPage" class="page-link" style="cursor:pointer;">
                                            Next
                                        </a>
                                    </li>

                                </ul>
                            </nav>

                        </div>

                    </div>
                </div>
            </div>
            {{-- </div> --}}
            {{-- </div> --}}
            {{-- </div> --}}

        </section>
    </div>

    <div class="modal fade" id="roleModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Modal title</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    ...
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

</div>

@push('scripts')
    <script>
        function initSelect2() {
            $('.select-skpd').select2();
            $('.select-role').select2({
                minimumResultsForSearch: Infinity
            });
        }

        $(document).ready(function() {
            initSelect2();
            $('.select-skpd').on("change", function(e) {
                window.Livewire.dispatch('select-updated', [$(this).val()]);
            });
            $('.select-role').on("change", function(e) {
                window.Livewire.dispatch('role-updated', [$(this).val()]);
            });
        });

        Livewire.on("update-skpd", function() {
            setTimeout(initSelect2, 0)
        })
        Livewire.on("update-role", function() {
            setTimeout(initSelect2, 0)
        })

        Livewire.on('brandSelected', postId => {
            jQuery(document).ready(function() {
                $('.select-role').select2();
            });
        })
    </script>
@endpush

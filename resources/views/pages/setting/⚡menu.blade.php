<?php

use App\Models\Menu;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Spatie\Permission\Models\Permission;

new class extends Component {
    use WithPagination;
    public $urutan, $menu, $segment, $icon, $parent_id;

    public $editFieldRowId;

    public $search = '';
    public $perPage = 10;

    protected $rules = [
        'urutan' => 'integer|nullable',
        'menu' => 'required|string|max:30',
        'segment' => 'required|string|max:30',
        'icon' => 'nullable',
        'parent_id' => 'nullable|exists:menus,id',
    ];

    protected $messages = [
        'menu.required' => 'Nama menu wajib diisi.',
        'segment.required' => 'Alamat segment wajib diisi.',
    ];

    protected $paginationTheme = 'bootstrap';

    protected $queryString = ['search', 'perPage'];

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedPerPage()
    {
        $this->resetPage();
    }

    #[Computed]
    public function totalMenus()
    {
        return Menu::count();
    }

    #[Computed]
    public function menus()
    {
        return Menu::with('parent')
            ->when($this->search, function ($query) {
                $query
                    ->where('menu', 'like', '%' . $this->search . '%')
                    ->orWhere('segment', 'like', '%' . $this->search . '%')
                    ->orWhere('icon', 'like', '%' . $this->search . '%');
            })
            ->orderBy('urutan')
            ->paginate($this->perPage);
    }

    public function modal()
    {
        $this->reset(['menu', 'segment', 'icon', 'parent_id']);
        $this->dispatch('modal');
    }

    public function konfirmasi($id, $menu)
    {
        $this->dispatch('konfirmasi', [
            'id' => $id,
            'menu' => $menu,
        ]);
    }

    public function editRow($id, $field, $value)
    {
        $this->editFieldRowId = $id . '-' . $field;

        if ($field === 'urutan') {
            $this->urutan = $value;
        } elseif ($field === 'menu') {
            $this->menu = $value;
        } elseif ($field === 'segment') {
            $this->segment = $value;
        } elseif ($field === 'icon') {
            $this->icon = $value;
        }
    }

    public function ubah($id, $field, $value)
    {
        $data = Menu::find($id);

        if (!$data) {
            return;
        }

        if ($field === 'parent_id') {
            $value = empty($value) ? null : $value;
        } elseif ($field === 'urutan') {
            $value = $value ?? 0;
        }

        $data->update([
            $field => $value,
        ]);

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

        $this->editFieldRowId = null;
        // $this->reset(['tampil_tambah', 'urutan', 'menu', 'segment', 'icon', 'parent_id']);
        $this->dispatch('toast_success', 'Menu ' . $data->menu . ' berhasil diubah.');
        $this->dispatch('sidebar_reload');
    }

    public function simpan()
    {
        $this->validate();

        Menu::create([
            'urutan' => $this->urutan ?? 0,
            'menu' => $this->menu,
            'segment' => $this->segment,
            'icon' => $this->icon,
            'parent_id' => $this->parent_id,
        ]);

        $permissionCodes = ['c', 'r', 'u', 'd'];

        $permissionNames = collect($permissionCodes)->map(fn($code) => $code . '_' . Str::slug($this->segment, '_'))->toArray();

        foreach ($permissionNames as $permName) {
            Permission::firstOrCreate([
                'name' => $permName,
                'guard_name' => 'web',
            ]);
        }
        $this->dispatch('toast_success', 'Menu ' . $this->menu . ' berhasil ditambahkan.');
        $this->dispatch('cleaning');
        $this->reset(['urutan', 'menu', 'segment', 'icon', 'parent_id']);

        $total = Menu::count();
        $lastPage = (int) ceil($total / $this->perPage);
        $this->setPage($lastPage);
    }

    #[On('hapus')]
    public function hapus($id)
    {
        $menu = Menu::findOrFail($id);
        $segment = $menu->segment;

        $menu->delete();

        $this->dispatch('toast_success', 'Menu ' . $this->menu . ' berhasil dihapus.');
        $this->dispatch('sidebar_reload');
    }

    #[On('refreshTable')]
    public function refreshTable() {}
};
?>

@push('style')
    <style>
        .edit-icon .icon-hover {
            opacity: 0;
            transition: opacity 0.2s ease-in-out;
            position: absolute;
            right: 0px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 16px;
            color: #6c757d;
        }

        .edit-icon:hover .icon-hover {
            opacity: 1;
        }

        .row-parent {
            background-color: #DDE2FF;
        }

        .row-child {}
    </style>
@endpush

<div>
    <ol class="breadcrumb bg-white pb-1 shadow-sm mb-4 py-4 pl-4">
        <li class="breadcrumb-item h4 ">
            <a wire:navigate href="{{ route('dashboard') }}"><b>Home</b></a>
        </li>
        <li class="breadcrumb-item active h4 text-dark">
            <b>
                Setting
            </b>
        </li>
        <li class="breadcrumb-item active h4 text-dark">
            <b>
                Menu
            </b>
        </li>
    </ol>

    <div class="row mt-1">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="d-flex align-items-center justify-content-between">
                        <span>Setting Menu</span>
                        <a wire:click="modal" class="btn btn-sm btn-primary rounded pt-0 px-2 m-1">
                            <i class="fas fa-plus-circle"></i> Baru
                        </a>
                    </h4>
                </div>
                <div class="m-3">

                    <div class="row mb-3">

                        <div class="col-1">
                            <select wire:model.live="perPage" class="form-control">
                                <option value="5">5</option>
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                            </select>
                        </div>

                        <div class="col-2 offset-9">
                            <input type="text" wire:model.live.debounce.500ms="search" class="form-control" placeholder="ketik sesuatu...">
                        </div>

                    </div>

                    <table class="table table-hover table-bordered table-md text-center">
                        <thead>
                            <tr>
                                <th class="col-1">Urutan</th>
                                <th class="text-left">Menu</th>
                                <th class="text-left">Segment</th>
                                <th class="text-left">Icon
                                    <a href="https://fontawesome.com/search?ic=free-collection" target="_blank" rel="noopener noreferrer">
                                        <i class="fa fa-circle-question"></i>
                                    </a>
                                </th>
                                <th>Parent</th>
                                <th class="col-1">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($this->menus as $menu)
                                <tr class="{{ is_null($menu->parent_id) ? 'row-parent' : 'row-child' }}">
                                    <th>
                                        @if ($editFieldRowId == $menu->id . '-urutan')
                                            <div class="d-flex justify-content-center">
                                                <input wire:blur="ubah('{{ $menu->id }}', 'urutan', $event.target.value)"
                                                    wire:keydown.enter="ubah('{{ $menu->id }}', 'urutan', $event.target.value)" class="form-control form-control-sm"
                                                    value="{{ $menu->urutan }}" @click.outside="$wire.set('editFieldRowId', null)" />
                                            </div>
                                        @else
                                            <div wire:click="editRow('{{ $menu->id }}', 'urutan', '{{ $menu->urutan }}')" class="edit-icon"
                                                style="cursor: pointer; position: relative;">
                                                {{ $menu->urutan ?? '---' }}
                                                <i class="fa-solid fa-pencil text-warning icon-hover"></i>
                                            </div>
                                        @endif
                                    </th>
                                    <td class="text-left">
                                        @if ($editFieldRowId == $menu->id . '-menu')
                                            <div class="d-flex justify-content-center">
                                                <input wire:blur="ubah('{{ $menu->id }}', 'menu', $event.target.value)"
                                                    wire:keydown.enter="ubah('{{ $menu->id }}', 'menu', $event.target.value)" class="form-control form-control-sm"
                                                    value="{{ $menu->menu }}" @click.outside="$wire.set('editFieldRowId', null)" />
                                            </div>
                                        @else
                                            <div wire:click="editRow('{{ $menu->id }}', 'menu', '{{ $menu->menu }}')" class="edit-icon"
                                                style="cursor: pointer; position: relative;">
                                                {{ $menu->menu ?? '---' }}
                                                <i class="fa-solid fa-pencil text-warning icon-hover"></i>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="text-left">
                                        @if ($editFieldRowId == $menu->id . '-segment')
                                            <div class="d-flex justify-content-center">
                                                <input wire:blur="ubah('{{ $menu->id }}', 'segment', $event.target.value)"
                                                    wire:keydown.enter="ubah('{{ $menu->id }}', 'segment', $event.target.value)" class="form-control form-control-sm"
                                                    value="{{ $menu->segment }}" @click.outside="$wire.set('editFieldRowId', null)" />
                                            </div>
                                        @else
                                            <div wire:click="editRow('{{ $menu->id }}', 'segment', '{{ $menu->segment }}')" class="edit-icon"
                                                style="cursor: pointer; position: relative;">
                                                {!! $menu->segment ? '<div class="badge badge-primary">' . $menu->segment . '</div>' : '<em class="text-warning text-italic">null</em>' !!}
                                                <i class="fa-solid fa-pencil text-warning icon-hover"></i>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="text-left">
                                        @if ($editFieldRowId == $menu->id . '-icon')
                                            <div class="d-flex justify-content-center">
                                                <input wire:blur="ubah('{{ $menu->id }}', 'icon', $event.target.value)"
                                                    wire:keydown.enter="ubah('{{ $menu->id }}', 'icon', $event.target.value)" class="form-control form-control-sm"
                                                    value="{{ $menu->icon }}" @click.outside="$wire.set('editFieldRowId', null)" />
                                            </div>
                                        @else
                                            <div wire:click="editRow('{{ $menu->id }}', 'icon', '{{ $menu->icon }}')" class="edit-icon"
                                                style="cursor: pointer; position: relative;">
                                                <i class="fa {{ $menu->icon }}"></i>&nbsp;
                                                {!! $menu->icon ? '<div class="badge badge-primary">' . $menu->icon . '</div>' : '<em class="text-warning text-italic">null</em>' !!}
                                                <i class="fa-solid fa-pencil text-warning icon-hover"></i>
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($editFieldRowId == $menu->id . '-parent_id')
                                            <div x-data x-init="$nextTick(() => {
                                                $($refs.select).select2({ width: '100%' })
                                                    .on('change', function() {
                                                        @this.ubah('{{ $menu->id }}', 'parent_id', $(this).val());
                                                    });
                                            })">
                                                <select x-ref="select" class="form-control form-control-sm">
                                                    <option value="" {{ is_null($menu->parent_id) ? 'selected' : '' }}>None</option>
                                                    @foreach ($this->menus->whereNull('parent_id') as $m)
                                                        @if ($m->id != $menu->id)
                                                            <option value="{{ $m->id }}" {{ $menu->parent_id == $m->id ? 'selected' : '' }}>
                                                                {{ $m->menu }}
                                                            </option>
                                                        @endif
                                                    @endforeach
                                                </select>
                                            </div>
                                        @else
                                            <div wire:click="editRow('{{ $menu->id }}', 'parent_id', '{{ $menu->parent_id }}')" class="edit-icon"
                                                style="cursor: pointer; position: relative;">
                                                {!! $menu->parent ? '<div class="badge badge-primary">' . $menu->parent->menu . '</div>' : '<em class="text-warning text-italic">null</em>' !!}
                                                <i class="fa-solid fa-pencil text-warning icon-hover" style="position: absolute; right: 0;"></i>
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <button wire:click="konfirmasi({{ $menu->id }}, '{{ addslashes($menu->menu) }}')" type="button" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center"><em>Tidak ditemukan</em></td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    <div class="d-flex justify-content-between align-items-center">

                        <div>
                            Menamplkan
                            {{ $this->menus->firstItem() }}
                            -
                            {{ $this->menus->lastItem() }}
                            dari
                            {{ $this->menus->total() }}
                            data
                        </div>

                        <div>
                            {{ $this->menus->links() }}
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    @teleport('body')
        <x-modal id="modalSettingMenu" title="Tambah Menu" simpan="simpan" ukuran="sm">

            <div class="form-group">
                <label>Menu</label>
                <input wire:model.defer="menu" type="text" class="form-control @error('menu') is-invalid @enderror" placeholder="...">
                @error('menu')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror
            </div>
            <div class="form-group">
                <label>Segment</label>
                <input wire:model.defer="segment" type="text" class="form-control @error('segment') is-invalid @enderror" placeholder="...">
                @error('segment')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <div class="form-group">
                <label>Icon</label>
                <input wire:model.defer="icon" type="text" class="form-control @error('icon') is-invalid @enderror" placeholder="...">
                @error('icon')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror
            </div>


        </x-modal>
    @endteleport
</div>


@push('scripts')
    <script>
        Livewire.on('toast_success', (message) => {
            showToast('success', message);
        });
        Livewire.on('toast_fail', (message) => {
            showToast('error', message);
        });
        Livewire.on('modal', () => {
            $('#modalSettingMenu').modal('show');

            const select = $('#parentSelect');

            if (select.hasClass("select2-hidden-accessible")) {
                select.select2('destroy');
            }

            select.select2({
                width: '100%',
                dropdownParent: $('#modalSettingMenu')
            });

            select.off('change').on('change', function() {
                Livewire.dispatch('setParentId', {
                    value: $(this).val()
                });
            });
        });
        Livewire.on('cleaning', () => {
            $('#modalSettingMenu').modal('hide');
        });
        Livewire.on('konfirmasi', (payload) => {

            const data = payload[0] ?? payload;

            Swal.fire({
                title: 'Konfirmasi hapus',
                text: "Anda yakin ingin menghapus Menu " + data.menu + " ?",
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
        Livewire.hook('message.processed', (el, component) => {
            $(el).find('select.select2').select2({
                width: '100%'
            });
        });
    </script>
@endpush

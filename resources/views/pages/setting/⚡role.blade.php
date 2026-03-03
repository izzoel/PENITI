<?php
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Spatie\Permission\Models\Role;

new class extends Component {
    use WithPagination;
    public $role;

    public $editFieldRowId;

    public $search = '';
    public $perPage = 10;

    protected $paginationTheme = 'bootstrap';
    protected $queryString = ['search', 'perPage'];

    protected $rules = [
        'role' => 'required|string|max:30',
    ];

    protected $messages = [
        'role.required' => 'Nama role wajib diisi.',
    ];

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedPerPage()
    {
        $this->resetPage();
    }

    #[Computed]
    public function roles()
    {
        return Role::when($this->search, function ($query) {
            $query->where('name', 'like', '%' . $this->search . '%');
        })->paginate($this->perPage);
    }

    public function modal()
    {
        $this->reset(['role']);
        $this->dispatch('modal');
    }

    public function konfirmasi($id, $role)
    {
        $this->dispatch('konfirmasi', [
            'id' => $id,
            'role' => $role,
        ]);
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
        $data = Role::find($id);

        if (!$data) {
            return;
        }

        $dbField = $field === 'role' ? 'name' : $field;

        $data->update([
            $dbField => $value,
        ]);

        $this->editFieldRowId = null;

        $displayName = $field === 'role' ? $value : $data->name;
        $this->dispatch('toast_success', 'Role ' . $data->name . ' berhasil diubah.');
    }

    public function simpan()
    {
        $this->validate();

        Role::create([
            'name' => $this->role,
        ]);

        $this->dispatch('toast_success', 'Menu ' . $this->role . ' berhasil ditambahkan.');
        $this->dispatch('cleaning');
        $this->reset('role');

        $total = Role::count();
        $lastPage = (int) ceil($total / $this->perPage);
        $this->setPage($lastPage);
    }

    #[On('hapus')]
    public function hapus($id)
    {
        $role = Role::findOrFail($id);
        $role->delete();
        $this->dispatch('toast_success', 'role ' . $this->role . ' berhasil dihapus.');
    }
};
?>

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
                Role
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
                                <th class="col-1">#</th>
                                <th class="text-left">Role</th>
                                <th class="col-1">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($this->roles as $role)
                                <tr>
                                    <th>
                                        {{ $loop->iteration }}
                                    </th>
                                    <td class="text-left">
                                        @if ($editFieldRowId == $role->id . '-name')
                                            <div class="d-flex justify-content-center">
                                                <input wire:blur="ubah('{{ $role->id }}', 'name', $event.target.value)"
                                                    wire:keydown.enter="ubah('{{ $role->id }}', 'role', $event.target.value)" class="form-control form-control-sm"
                                                    value="{{ $role->name }}" @click.outside="$wire.set('editFieldRowId', null)" />
                                            </div>
                                        @else
                                            <div wire:click="editRow('{{ $role->id }}', 'name', '{{ $role->name }}')" class="edit-icon"
                                                style="cursor: pointer; position: relative;">
                                                {{ $role->name ?? '---' }}
                                                <i class="fa-solid fa-pencil text-warning icon-hover"></i>
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <button wire:click="konfirmasi({{ $role->id }}, '{{ addslashes($role->name) }}')" type="button" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center"><em>Tidak ditemukan</em></td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    <div class="d-flex justify-content-between align-items-center">

                        <div>
                            Menamplkan
                            {{ $this->roles->firstItem() }}
                            -
                            {{ $this->roles->lastItem() }}
                            dari
                            {{ $this->roles->total() }}
                            data
                        </div>

                        <div>
                            {{ $this->roles->links() }}
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    @teleport('body')
        <x-modal id="modalSettingRole" title="Tambah Role" simpan="simpan" ukuran="sm">

            <div class="form-group">
                <label>Role</label>
                <input wire:model.defer="role" type="text" class="form-control @error('role') is-invalid @enderror" placeholder="...">
                @error('role')
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
            $('#modalSettingRole').modal('show');
        });
        Livewire.on('cleaning', () => {
            $('#modalSettingRole').modal('hide');
        });
        Livewire.on('konfirmasi', (payload) => {

            const data = payload[0] ?? payload;

            Swal.fire({
                title: 'Konfirmasi hapus',
                text: "Anda yakin ingin menghapus Role " + data.role + " ?",
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
    </script>
@endpush

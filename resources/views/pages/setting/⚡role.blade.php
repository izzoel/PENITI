<?php
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Spatie\Permission\Models\Role;

new class extends Component {
    use WithPagination;
    public $role;

    public $editFieldRowId, $lastSegment;

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

    public function mount()
    {
        $routeName = request()->route()?->getName();
        $this->lastSegment = $routeName ? collect(explode('.', $routeName))->last() : null;
    }

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
            <a wire:navigate href="{{ route('home.dashboard') }}"><b>Home</b></a>
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
                        <span>Setting Role</span>
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

                        <div class="col-3 offset-8">
                            <input type="text" wire:model.live.debounce.500ms="search" class="form-control" placeholder="ketik sesuatu...">
                        </div>

                    </div>

                    <table class="table table-hover table-bordered table-md text-center">
                        <thead>
                            <tr>
                                <th class="col-1">#</th>
                                <th class="text-left">Role</th>
                                @if (akses('d', $this->lastSegment))
                                    <th class="col-1">Aksi</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($this->roles as $role)
                                <tr>
                                    <td>
                                        {{ $loop->iteration }}
                                    </td>
                                    <td class="text-left">
                                        @if (akses('u', $this->lastSegment))
                                            <x-inline-input-edit :id="$role->id" field="name" :value="$role->name" :edit-field-row-id="$editFieldRowId" />
                                        @else
                                            {{ $role->name ?? '---' }}
                                        @endif
                                    </td>
                                    @if (akses('d', $this->lastSegment))
                                        <td>
                                            <button wire:click="konfirmasi({{ $role->id }}, '{{ addslashes($role->name) }}')" type="button" class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center"><em>Tidak ditemukan</em></td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <div class="d-flex justify-content-end align-items-center">
                        <div>
                            {{ $this->roles->links() }}
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    @if (akses('c', $this->lastSegment))
        @teleport('body')
            <x-modal id="modalSettingRole" title="Tambah Role" simpan="simpan" ukuran="sm">

                <div class="row">
                    <div class="col">
                        <div class="form-group">
                            <label>Role<span class="text-danger">*</span></label>
                            <input wire:model.defer="role" type="text" class="form-control @error('role') is-invalid @enderror" placeholder="...">
                            @error('role')
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

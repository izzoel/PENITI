<?php
use App\Models\Pegawai;
use App\Models\Skpd;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Spatie\Permission\Models\Role;
use Illuminate\Validation\ValidationException;

new class extends Component {
    use WithPagination;
    public $nama, $nip, $password;
    public $userId;

    public $id_role, $id_skpd;

    public $editFieldRowId, $lastSegment;

    public $search = '';
    public $perPage = 10;

    protected $paginationTheme = 'bootstrap';
    protected $queryString = ['search', 'perPage'];

    protected $rules = [
        'nama' => 'required',
        'password' => 'required|min:6',
        'id_role' => 'required',
        'id_skpd' => 'required',
    ];

    protected $messages = [
        'nama.required' => 'Nama wajib diisi.',
        'password.required' => 'Password wajib diisi.',
        'id_role.required' => 'Pilih Role.',
        'id_skpd.required' => 'Pilih SKPD.',
    ];

    public function mount()
    {
        $routeName = request()->route()?->getName();
        $this->lastSegment = $routeName ? collect(explode('.', $routeName))->last() : null;
    }

    #[Computed]
    public function users()
    {
        return User::when($this->search, function ($query) {
            $query->where('nama', 'like', '%' . $this->search . '%')->orWhere('nip', 'like', '%' . $this->search . '%');
        })->paginate($this->perPage);
    }

    #[Computed]
    public function roles()
    {
        return Role::all();
    }

    #[Computed]
    public function pegawais()
    {
        return Pegawai::all();
    }
    #[Computed]
    public function skpds()
    {
        return Skpd::all();
    }

    public function simpan()
    {
        try {
            $role = Role::find($this->id_role);
            $this->validate([
                'nama' => 'required',
                'id_role' => 'required',
                'id_skpd' => 'required',
            ]);
            $this->dispatch('validate', errors: []);

            User::create([
                'nama' => $this->nama,
                'nip' => $this->nip,
                'password' => Hash::make($this->nip),
                'id_role' => $this->id_role,
                'id_skpd' => $this->id_skpd,
            ])->assignRole($role->name);

            $this->dispatch('toast_success', 'User ' . $this->nama . ' berhasil ditambahkan.');
            $this->dispatch('cleaning');
        } catch (ValidationException $e) {
            $this->dispatch('validate', errors: $e->errors());
            return;
        }
    }

    public function modal()
    {
        $this->reset(['nama', 'id_role', 'id_skpd']);
        $this->dispatch('modal');
    }

    public function user($id)
    {
        $this->dispatch('user', [
            'id' => $id,
        ]);
    }

    public function resetPasswordModal($id)
    {
        $this->userId = $id;
        $this->password = null;
        $this->dispatch('password', [
            'id' => $id,
        ]);
    }

    public function konfirmasi($id, $nama)
    {
        $this->dispatch('konfirmasi', [
            'id' => $id,
            'nama' => $nama,
        ]);
    }

    public function editRow($id, $field, $value)
    {
        $this->editFieldRowId = $id . '-' . $field;

        if ($field === 'nama') {
            $this->nama = $value;
        }
    }

    public function ubah($id, $field, $value)
    {
        $data = User::find($id);

        if (!$data) {
            return;
        }
        if ($field === 'nama') {
            $value = empty($value) ? null : $value;
        } elseif ($field === 'role') {
            $data->syncRoles([$value]);
            $field = 'id_role';
            $value = Role::findByName($value)->id ?? null;
        }

        $data->update([
            $field => $value,
        ]);

        $this->editFieldRowId = null;
        $this->dispatch('toast_success', 'User ' . $data->nama . ' berhasil diubah.');
    }

    public function sync()
    {
        DB::transaction(function () {
            $pegawais = Pegawai::all();

            foreach ($pegawais as $pegawai) {
                $user = User::where('id_pegawai', $pegawai->id)->first();

                if (!$user) {
                    User::create([
                        'nip' => $pegawai->nip,
                        'nama' => $pegawai->nama,
                        'password' => Hash::make($pegawai->nip),
                        'id_skpd' => $pegawai->id_skpd,
                        'id_role' => null,
                        'id_pegawai' => $pegawai->id,
                    ]);
                } else {
                    $user->update([
                        'nip' => $pegawai->nip,
                        'nama' => $pegawai->nama,
                        'id_skpd' => $pegawai->id_skpd,
                    ]);
                }
            }
        });

        $this->dispatch('toast_success', 'User berhasil di-sync.');
    }

    public function resetPassword()
    {
        $this->validateOnly('password');

        $user = User::find($this->userId);

        if (!$user) {
            return;
        }

        $user->update([
            'password' => Hash::make($this->password),
        ]);

        $this->reset(['password', 'userId']);
        $this->dispatch('toast_success', 'Password berhasil direset.');
        $this->dispatch('cleaning');
    }

    #[On('hapus')]
    public function hapus($id)
    {
        $user = User::findOrFail($id);
        $user->delete();
        $this->dispatch('toast_success', 'User ' . $user->nama . ' berhasil dihapus.');
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
                User
            </b>
        </li>
    </ol>

    <div class="row mt-1">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="d-flex align-items-center justify-content-between">
                        <span>Setting User</span>
                        @if (akses('c', $this->lastSegment))
                            <a wire:click="modal" class="btn btn-sm btn-primary rounded pt-0 px-2 m-1">
                                <i class="fas fa-plus-circle"></i> Baru
                            </a>
                        @endif
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
                                <th class="text-left">NIP</th>
                                <th>Role</th>
                                <th>SKPD</th>
                                <th class="col-2">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($this->users as $user)
                                <tr>
                                    <td>
                                        {{ $loop->iteration }}
                                    </td>
                                    <td class="text-left">
                                        @if (akses('u', $this->lastSegment))
                                            <x-inline-input-edit :id="$user->id" field="nama" :value="$user->nama" :edit-field-row-id="$editFieldRowId" />
                                        @else
                                            {{ format_nama($user->nama) ?? '---' }}
                                        @endif
                                    </td>
                                    <td class="text-left">
                                        @if (akses('u', $this->lastSegment))
                                            <x-inline-input-edit :id="$user->id" field="nip" :value="$user->nip" :edit-field-row-id="$editFieldRowId" />
                                        @else
                                            {{ $user->nip ?? '---' }}
                                        @endif
                                    </td>
                                    <td>
                                        @if (akses('u', $this->lastSegment))
                                            @if ($editFieldRowId == $user->id . '-role')
                                                <div x-data x-init="$nextTick(() => {
                                                    $($refs.select).select2({ width: '100%' })
                                                        .on('change', function() {
                                                            @this.ubah('{{ $user->id }}', 'role', $(this).val());
                                                        });
                                                })">
                                                    <select x-ref="select" class="form-control form-control-sm">
                                                        <option value="" {{ is_null($user->getRoleNames()->first()) ? 'selected' : '' }}>None</option>
                                                        @foreach ($this->roles as $role)
                                                            <option value="{{ $role->name }}" {{ $user->hasRole($role->name) ? 'selected' : '' }}>
                                                                {{ $role->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            @else
                                                <div wire:click="editRow('{{ $user->id }}', 'role', '{{ $user->role }}')" class="edit-icon"
                                                    style="cursor: pointer; position: relative;">
                                                    {!! $user->getRoleNames()->first() ? '<div class="badge badge-primary">' . $user->getRoleNames()->first() . '</div>' : '<em class="text-warning text-italic">null</em>' !!}
                                                    <i class="fa-solid fa-pencil text-warning icon-hover" style="position: absolute; right: 0;"></i>
                                                </div>
                                            @endif
                                        @else
                                            {!! $user->getRoleNames()->first() ? '<div class="badge badge-primary">' . $user->getRoleNames()->first() . '</div>' : '<em class="text-warning text-italic">null</em>' !!}
                                        @endif
                                    </td>
                                    <td class="text-left">
                                        {{ $user->skpd->nama ?? '---' }}
                                    </td>
                                    <td>
                                        @if (akses('u', $this->lastSegment))
                                            {{-- <button wire:click="user({{ $user->id }})" type="button" class="btn btn-sm btn-warning">
                                                <i class="fas fa-pencil"></i>
                                            </button> --}}
                                            <button wire:click="resetPasswordModal({{ $user->id }})" type="button" class="btn btn-sm btn-primary">
                                                <i class="fas fa-key"></i>
                                            </button>
                                        @endif
                                        @if (akses('d', $this->lastSegment))
                                            <button wire:click="konfirmasi({{ $user->id }}, '{{ format_nama(addslashes($user->nama)) }}')" type="button"
                                                class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        @endif
                                    </td>
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
                            {{ $this->users->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    @if (akses('c', $this->lastSegment))
        @teleport('body')
            <x-modal id="modalSettingUser" title="Tambah User" simpan="simpan" ukuran='md'>

                <div class="row">
                    <div wire:ignore class="col">
                        <div class="form-group">
                            <label>Pegawai<span class="text-danger">*</span></label>
                            <input wire:model="nama" id="nama" class="form-control @error('nama') is-invalid @enderror" placeholder="..."></input>
                            <div id="error-nama"> </div>
                        </div>
                    </div>
                    <div wire:ignore class="col">
                        <div class="form-group">
                            <label>NIP</label>
                            <input wire:model="nip" id="nip" class="form-control" placeholder="..."></input>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div wire:ignore class="col">
                        <div class="form-group">
                            <label>Role<span class="text-danger">*</span></label>
                            <select id="roleSelect" class="form-control form-control-sm roleSelect ">
                                <option value=""></option>
                                @foreach ($this->roles as $role)
                                    <option value="{{ $role->id }}">
                                        {{ $role->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div id="error-role"></div>
                        </div>
                    </div>
                    <div wire:ignore class="col">
                        <div class="form-group">
                            <label>SKPD<span class="text-danger">*</span></label>
                            <select id="skpdSelect" class="form-control form-control-sm skpdSelect">
                                <option value=""></option>
                                @foreach ($this->skpds as $skpd)
                                    <option value="{{ $skpd->id_skpd }}">
                                        {{ $skpd->nama }}
                                    </option>
                                @endforeach
                            </select>
                            <div id="error-skpd"></div>
                        </div>
                    </div>
                </div>

            </x-modal>
        @endteleport
    @endif

    @if (akses('u', $this->lastSegment))
        @teleport('body')
            <x-modal id="modalUser" title="Setting User" simpan="editUser" ukuran='sm'>

                <div class="row">
                    <div class="col">
                        <div class="form-group">
                            <div>{{ auth()->user()->nama }}</div>
                            <em>{{ auth()->user()->nip }}</em>

                        </div>
                    </div>

                </div>
                <div class="row">
                    <div class="col">
                        <div class="form-group">
                            <label>Atasan</label>
                            <input wire:model.defer="password" type="password" class="form-control @error('password') is-invalid @enderror" placeholder="password baru...">
                            @error('password')
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
                            <label>Jenis Kelamin</label>
                            <input wire:model.defer="password" type="password" class="form-control @error('password') is-invalid @enderror" placeholder="password baru...">
                            @error('password')
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
            <x-modal id="modalPassword" title="Reset Password" simpan="resetPassword" ukuran='sm'>

                <div class="row">
                    <div class="col">
                        <div class="form-group">
                            <label>Password</label>
                            <input wire:model.defer="password" type="password" class="form-control @error('password') is-invalid @enderror" placeholder="password baru...">
                            @error('password')
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
        const role = $('#roleSelect');
        const skpd = $('#skpdSelect');

        $('#modalSettingUser').on('shown.bs.modal', function() {

            role.on('change', function() {
                $wire.set('id_role', role.val() ? role.val() : null);
            });

            skpd.on('change', function() {
                $wire.set('id_skpd', skpd.val())
            });

        });
        $('#modalSettingUser').on('hidden.bs.modal', function() {
            $('#nama').val('');
            $('#roleSelect').val(null).trigger('change');
            $('#skpdSelect').val(null).trigger('change');

            $('#nama').css({
                'border': '',
                'box-shadow': ''
            });
            $('#roleSelect').next('.select2').find('.select2-selection')
                .css('border-color', '#e4e6fc');
            $('#skpdSelect').next('.select2').find('.select2-selection')
                .css('border-color', '#e4e6fc');

            $('#error-nama').html('');
            $('#error-role').html('');
            $('#error-skpd').html('');

            $wire.set('nama', null);
            $wire.set('id_role', null);
            $wire.set('id_skpd', null);
        });

        Livewire.on('toast_success', (message) => {
            showToast('success', message);
        });
        Livewire.on('toast_fail', (message) => {
            showToast('error', message);
        });
        Livewire.on('modal', () => {
            role.select2({
                dropdownParent: '#modalSettingUser',
                placeholder: '-- pilih --'
            });
            skpd.select2({
                dropdownParent: '#modalSettingUser',
                placeholder: '-- pilih --'
            });
            $('#modalSettingUser').modal('show');
        });
        Livewire.on('cleaning', () => {
            $('#modalUser').modal('hide');
            $('#modalPassword').modal('hide');
            $('#modalSettingUser').modal('hide');
        });
        Livewire.on('user', () => {
            $('#modalUser').modal('show');
        });
        Livewire.on('password', () => {
            $('#modalPassword').modal('show');
        });
        Livewire.on('konfirmasi', (payload) => {

            const data = payload[0] ?? payload;

            Swal.fire({
                title: 'Konfirmasi hapus',
                text: "Anda yakin ingin menghapus User " + data.nama + " ?",
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
        Livewire.on('validate', (data) => {
            let nama = $('#nama');
            let role = $('#roleSelect').next('.select2').find('.select2-selection');
            let skpd = $('#skpdSelect').next('.select2').find('.select2-selection');
            let namaError = data.errors?.nama?.[0];
            let errorRole = data.errors?.id_role?.[0];
            let errorSkpd = data.errors?.id_skpd?.[0];

            if (namaError) {
                $('#nama').css({
                    'border': '1px solid #dc3545',
                });
                $('#error-nama').html(`<div class="text-danger">${namaError}</div>`);
            } else {
                $('#nama').css({
                    'border': '',
                    'box-shadow': ''
                });
                $('#error-nama').html('');
            }
            if (errorRole) {
                role.css('border-color', '#dc3545');
                $('#error-role').html(`<div class="text-danger">${errorRole}</div>`);
            } else {
                role.css('border-color', '#e4e6fc');
                $('#error-role').html('');
            }
            if (errorSkpd) {
                skpd.css('border-color', '#dc3545');
                $('#error-skpd').html(`<div class="text-danger">${errorSkpd}</div>`);
            } else {
                skpd.css('border-color', '#e4e6fc');
                $('#error-skpd').html('');
            }

        });
    </script>
@endpush

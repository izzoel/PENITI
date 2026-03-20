<?php

use App\Models\Menu;
use Livewire\Component;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

new class extends Component {
    public $permissions = [];
    public $selectedRolesPerMenu = [];
    public $create, $read, $update, $delete;

    public $editFieldRowId;

    public $search = '';
    public $perPage = 10;

    protected $paginationTheme = 'bootstrap';
    protected $queryString = ['search', 'perPage'];

    #[Computed]
    public function menus()
    {
        return Menu::whereNotNull('parent_id')->orderBy('menu')->get();
    }

    #[Computed]
    public function roles()
    {
        return Role::all();
    }

    public function saveRole($menuId, $roleIds)
    {
        $menu = Menu::findOrFail($menuId);
        $permissions = Permission::where('name', 'like', '%_' . strtolower($menu->menu))->get();

        $selectedRoles = $this->roles->whereIn('id', $roleIds);
        foreach ($selectedRoles as $role) {
            $currentPerms = $role->permissions->pluck('name')->intersect($permissions->pluck('name'))->toArray();
            if (count($currentPerms) === $permissions->count()) {
                continue;
            }
            $toAdd = array_diff($permissions->pluck('name')->toArray(), $currentPerms);
            if (!empty($toAdd)) {
                $role->givePermissionTo($toAdd);
            }
        }

        $rolesWithPermission = $this->roles
            ->filter(function ($role) use ($permissions) {
                return $role->permissions->pluck('name')->intersect($permissions->pluck('name'))->isNotEmpty();
            })
            ->pluck('id')
            ->toArray();

        $toAdd = array_diff($roleIds, $rolesWithPermission);

        foreach ($this->roles->whereIn('id', $toAdd) as $role) {
            $role->givePermissionTo($permissions);
        }

        $toRemove = array_diff($rolesWithPermission, $roleIds);
        foreach ($this->roles->whereIn('id', $toRemove) as $role) {
            $role->revokePermissionTo($permissions);
        }

        $this->dispatch('toast_success', 'Role berhasil diupdate!');
    }

    public function toggleGroupPermission($roleIds, $permissionName)
    {
        $code = explode('_', $permissionName)[0];
        $menu = explode('_', $permissionName)[1];

        $map = [
            'c' => 'Create',
            'r' => 'Read',
            'u' => 'Update',
            'd' => 'Delete',
        ];

        $akses = $map[$code] ?? $code;
        foreach ($roleIds as $roleId) {
            $role = Role::find($roleId);
            if ($role->hasPermissionTo($permissionName)) {
                $role->revokePermissionTo($permissionName);
            } else {
                $role->givePermissionTo($permissionName);
            }
        }
        $this->dispatch('toast_success', "$akses $menu diperbarui");
    }

    public function groupRolesByPermissions($menu, $roles)
    {
        $groups = [];

        foreach ($roles as $role) {
            $perms = collect(['c', 'r', 'u', 'd'])
                ->mapWithKeys(function ($code) use ($role, $menu) {
                    $permName = $code . '_' . strtolower($menu->menu);
                    return [$code => $role->hasPermissionTo($permName)];
                })
                ->toArray();

            $hash = implode('-', array_map(fn($v) => $v ? 1 : 0, $perms));

            if (isset($groups[$hash])) {
                $groups[$hash]['roles'][] = $role;
            } else {
                $groups[$hash] = [
                    'permissions' => $perms,
                    'roles' => [$role],
                ];
            }
        }

        return array_values($groups);
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
                Akses
            </b>
        </li>
    </ol>

    <div class="row mt-1">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="d-flex align-items-center justify-content-between">
                        <span>Setting Akses</span>
                        {{-- <a wire:click="modal" class="btn btn-sm btn-primary rounded pt-0 px-2 m-1">
                            <i class="fas fa-plus-circle"></i> Baru
                        </a> --}}
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
                            <tr class="text-center">
                                <th class="col-auto text-left">Menu</th>
                                <th class="col-auto text-left">Role</th>
                                <th class="col-1">Create</th>
                                <th class="col-1">Read</th>
                                <th class="col-1">Update</th>
                                <th class="col-1">Delete</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($this->menus as $menu)
                                @php
                                    $groups = $this->groupRolesByPermissions($menu, $this->roles);
                                @endphp

                                @foreach ($groups as $group)
                                    <tr>
                                        <td class="text-left">{{ ucfirst($menu->menu) }}</td>
                                        <td class="text-left" x-data="{ editing: false }" @click.outside="editing = false">

                                            <div x-show="!editing" @click="editing = true" style="cursor:pointer; position:relative;" class="edit-icon">
                                                {{ collect($group['roles'])->pluck('name')->implode(', ') ?: '---' }}
                                                <i class="fa-solid fa-pencil text-warning icon-hover"></i>
                                            </div>

                                            <div x-show="editing" x-cloak wire:ignore class="flex items-center gap-2">
                                                <div class="d-flex align-items-center w-100" style="border: 1px solid #ced4da; border-radius: .25rem; overflow: hidden;">
                                                    <select class="roles form-select select2" data-menu-id="{{ $menu->id }}" multiple="multiple" style="width:100%;"
                                                        x-ref="select">
                                                        @foreach ($this->roles as $role)
                                                            <option value="{{ $role->id }}" @selected(collect($group['roles'])->pluck('id')->contains($role->id))>
                                                                {{ $role->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <div class="input-group-append" style="cursor:pointer;"
                                                        @click="editing = false;$wire.saveRole({{ $menu->id }}, [...$refs.select.selectedOptions].map(o => o.value));">
                                                        <div class="input-group-text">
                                                            <i class="fa fa-check text-primary">
                                                            </i>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>

                                        @foreach (['c' => 'Create', 'r' => 'Read', 'u' => 'Update', 'd' => 'Delete'] as $code => $label)
                                            @php
                                                $permissionName = $code . '_' . strtolower($menu->menu);
                                            @endphp
                                            <td class="text-center">
                                                <input type="checkbox" value="{{ $permissionName }}"
                                                    wire:click="toggleGroupPermission({{ collect($group['roles'])->pluck('id') }}, '{{ $permissionName }}')"
                                                    @checked($group['permissions'][$code])>
                                            </td>
                                        @endforeach

                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>

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

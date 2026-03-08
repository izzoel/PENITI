<?php

use App\Models\Menu;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Artisan;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Spatie\Permission\Models\Permission;
use Symfony\Component\Process\Process;

new class extends Component {
    use WithPagination;
    public $urutan, $menu, $segment, $icon, $parent_id;

    public $editFieldRowId, $lastSegment;

    public $search = '';
    public $perPage = 10;

    protected $paginationTheme = 'bootstrap';
    protected $queryString = ['search', 'perPage'];

    protected $rules = [
        'urutan' => 'integer|nullable',
        'menu' => 'required|string|max:30',
        'segment' => 'nullable|string|max:30',
        'icon' => 'nullable',
        'parent_id' => 'nullable|exists:menus,id',
    ];

    protected $messages = [
        'menu.required' => 'Nama menu wajib diisi.',
        'segment.required' => 'Alamat segment wajib diisi.',
    ];

    private function artisanLivewire($segmentPath, $namaSegment)
    {
        $php = $this->directoryPhp();

        $process = new Process([$php, base_path('artisan'), 'make:livewire', $segmentPath], base_path());

        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException("Gagal menjalankan make:livewire.\n" . 'Exit Code: ' . $process->getExitCode() . "\n" . 'Output: ' . $process->getOutput() . "\n" . 'Error Output: ' . $process->getErrorOutput());
        }

        $routeFile = base_path('routes/web.php');
        $fileContents = File::get($routeFile);

        $routeUrl = '/' . str_replace('.', '/', $this->segment);
        $routeLine = "Route::livewire('{$routeUrl}', '{$segmentPath}')->name('{$namaSegment}');";

        if (!Str::contains($fileContents, $routeLine)) {
            $search = '=Dynamic Routes=';
            $pos = strpos($fileContents, $search);
            $indent = Str::repeat(' ', 4);

            if ($pos !== false) {
                $insertPos = strpos($fileContents, "\n", $pos) + 1;
                $fileContents = substr_replace($fileContents, $indent . $routeLine . "\n", $insertPos, 0);

                File::put($routeFile, $fileContents);
            } else {
                File::append($routeFile, "\n" . $routeLine);
            }
        }
    }

    private function directoryPhp()
    {
        $candidates = ['/usr/bin/php', '/usr/local/bin/php', '/opt/homebrew/bin/php', exec('which php')];

        foreach ($candidates as $php) {
            if ($php && file_exists($php) && is_executable($php)) {
                return $php;
            }
        }

        throw new \RuntimeException('PHP CLI binary not found.');
    }

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
    public function menus()
    {
        return Menu::with(['children', 'parent'])
            ->whereNull('parent_id')
            ->when($this->search, function ($query) {
                $query
                    ->where('menu', 'like', '%' . $this->search . '%')
                    ->orWhere('segment', 'like', '%' . $this->search . '%')
                    ->orWhere('icon', 'like', '%' . $this->search . '%');
            })
            ->orderBy('urutan')
            ->paginate($this->perPage);
    }

    #[Computed]
    public function totalMenus()
    {
        return Menu::count();
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

        $this->editFieldRowId = null;
        $this->dispatch('toast_success', 'Menu ' . $data->menu . ' berhasil diubah.');
    }

    public function simpan()
    {
        $namaSegment = $this->segment;
        $folder =
            'pages::' .
            collect(explode('/', $this->segment))
                ->slice(0, -1)
                ->map(fn($s) => Str::studly($s))
                ->implode('/');

        $segmentPath = $folder ? $folder . $namaSegment : $namaSegment;

        $this->validate();

        Menu::create([
            'urutan' => $this->urutan ?? 0,
            'menu' => $this->menu,
            'segment' => $this->segment,
            'icon' => $this->icon,
            'parent_id' => $this->parent_id,
        ]);

        if (!empty($this->segment)) {
            $this->artisanLivewire($segmentPath, $namaSegment);
        }

        $permissionCodes = ['c', 'r', 'u', 'd'];

        $permissionNames = collect($permissionCodes)->map(fn($code) => $code . '_' . Str::slug($this->menu, '_'))->toArray();

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

        if (!empty($segment)) {
            $namaSegment = $menu->segment;

            $segmentPath = 'pages::' . $menu->segment;
            $segmentSlash = str_replace('.', '/', $menu->segment);

            $before = Str::beforeLast($segmentSlash, '/');
            $after = Str::afterLast($segmentSlash, '/');

            $modified = $before ? $before . '/⚡' . $after : '⚡' . $after;

            $viewPath = resource_path('views/pages/' . $modified . '.blade.php');
            if (File::exists($viewPath)) {
                // File::delete($viewPath);
            }

            $routeFile = base_path('routes/web.php');
            $routeUrl = '/' . str_replace('.', '/', $this->segment);
            $routeLine = "Route::livewire('{$routeUrl}', '{$segmentPath}')->name('{$namaSegment}');";

            if (File::exists($routeFile)) {
                $contents = File::get($routeFile);
                $escapedUrl = preg_quote($routeUrl, '#');
                $escapedView = preg_quote($segmentPath, '#');
                $escapedName = preg_quote($namaSegment, '#');

                $pattern = "/Route::livewire\(\s*'{$escapedUrl}'\s*,\s*'{$escapedView}'\s*\)\s*->name\(\s*'{$escapedName}'\s*\);\s*/";
                // $contents = preg_replace($pattern, '', $contents);
                File::put($routeFile, $contents);

                dd($contents, $escapedUrl, $escapedView, $escapedName);
            }
        }

        $menu->delete();

        $this->dispatch('toast_success', 'Menu ' . $this->menu . ' berhasil dihapus.');
    }
};
?>

@push('style')
    <style>
        .row-parent {
            background-color: #DDE2FF;
        }

        .row-child {}
    </style>
@endpush

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
                        @if (akses('c', $this->lastSegment))
                            <a x-data @click="$wire.modal()" class="btn btn-sm btn-primary rounded pt-0 px-2 m-1">
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

                    <table class="table table-hover table-bordered text-center">
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
                                @if (akses('d', $this->lastSegment))
                                    <th class="col-1">Aksi</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($this->menus as $menu)
                                <tr class="{{ is_null($menu->parent_id) ? 'row-parent' : 'row-child' }}">
                                    <td>
                                        @if (akses('u', $this->lastSegment))
                                            <x-inline-input-edit :id="$menu->id" field="urutan" :value="$menu->urutan" :edit-field-row-id="$editFieldRowId" />
                                        @else
                                            {{ $menu->urutan ?? '---' }}
                                        @endif

                                    </td>
                                    <td class="text-left">
                                        @if (akses('u', $this->lastSegment))
                                            <x-inline-input-edit :id="$menu->id" field="menu" :value="$menu->menu" :edit-field-row-id="$editFieldRowId" />
                                        @else
                                            {{ $menu->menu ?? '---' }}
                                        @endif
                                    </td>
                                    <td class="text-left">
                                        @if (akses('u', $this->lastSegment))
                                            <x-inline-input-edit :id="$menu->id" field="segment" :value="$menu->segment" :edit-field-row-id="$editFieldRowId" />
                                        @else
                                            {!! $menu->segment ? '<div class="badge badge-primary">' . $menu->segment . '</div>' : '<em class="text-warning text-italic">null</em>' !!}
                                        @endif
                                    </td>
                                    <td class="text-left">
                                        @if (akses('u', $this->lastSegment))
                                            <x-inline-input-edit :id="$menu->id" field="icon" :value="$menu->icon" :edit-field-row-id="$editFieldRowId" />
                                        @else
                                            {!! $menu->icon ? '<div class="badge badge-primary">' . $menu->icon . '</div>' : '<em class="text-warning text-italic">null</em>' !!}
                                        @endif
                                    </td>
                                    <td>
                                        @if (akses('u', $this->lastSegment))
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
                                        @else
                                            {!! $menu->parent ? '<div class="badge badge-primary">' . $menu->parent->menu . '</div>' : '<em class="text-warning text-italic">null</em>' !!}
                                        @endif
                                    </td>
                                    @if (akses('d', $this->lastSegment))
                                        <td>
                                            <button wire:click="konfirmasi({{ $menu->id }}, '{{ addslashes($menu->menu) }}')" type="button" class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    @endif
                                </tr>
                                @foreach ($menu->children as $child)
                                    <tr>
                                        <td>
                                            @if (akses('u', $this->lastSegment))
                                                <x-inline-input-edit :id="$child->id" field="urutan" :value="$child->urutan" :edit-field-row-id="$editFieldRowId" child />
                                            @else
                                                &rdsh;&nbsp;{{ $child->urutan ?? '---' }}
                                            @endif
                                        </td>
                                        <td class="text-left">
                                            @if (akses('u', $this->lastSegment))
                                                <x-inline-input-edit :id="$child->id" field="menu" :value="$child->menu" :edit-field-row-id="$editFieldRowId" />
                                            @else
                                                {{ $child->menu ?? '---' }}
                                            @endif
                                        </td>
                                        <td class="text-left">
                                            @if (akses('u', $this->lastSegment))
                                                <x-inline-input-edit :id="$child->id" field="segment" :value="$child->segment" :edit-field-row-id="$editFieldRowId" />
                                            @else
                                                {!! $child->segment ? '<div class="badge badge-primary">' . $child->segment . '</div>' : '<em class="text-warning text-italic">null</em>' !!}
                                            @endif
                                        </td>
                                        <td class="text-left">
                                            @if (akses('u', $this->lastSegment))
                                                <x-inline-input-edit :id="$child->id" field="icon" :value="$child->icon" :edit-field-row-id="$editFieldRowId" :i='true' :icon="$child->icon"
                                                    :badge />
                                            @else
                                                <i class="fa {{ $child->icon }}"></i>&nbsp; {!! $child->icon ? '<div class="badge badge-primary">' . $child->icon . '</div>' : '<em class="text-warning text-italic">null</em>' !!}
                                            @endif
                                        </td>
                                        <td>
                                            @if (akses('u', $this->lastSegment))
                                                @if ($editFieldRowId == $child->id . '-parent_id')
                                                    <div x-data x-init="$nextTick(() => {
                                                        $($refs.select).select2({ width: '100%' })
                                                            .on('change', function() {
                                                                @this.ubah('{{ $child->id }}', 'parent_id', $(this).val());
                                                            });
                                                    })">
                                                        <select x-ref="select" class="form-control form-control-sm">
                                                            <option value="" {{ is_null($child->parent_id) ? 'selected' : '' }}>None</option>
                                                            @foreach ($this->menus->whereNull('parent_id') as $m)
                                                                @if ($m->id != $child->id)
                                                                    <option value="{{ $m->id }}" {{ $menu->parent_id == $m->id ? 'selected' : '' }}>
                                                                        {{ $m->menu }}
                                                                    </option>
                                                                @endif
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                @else
                                                    <div wire:click="editRow('{{ $child->id }}', 'parent_id', '{{ $child->parent_id }}')" class="edit-icon"
                                                        style="cursor: pointer; position: relative;">
                                                        {!! $child->parent ? '<div class="badge badge-primary">' . $child->parent->menu . '</div>' : '<em class="text-warning text-italic">null</em>' !!}
                                                        <i class="fa-solid fa-pencil text-warning icon-hover" style="position: absolute; right: 0;"></i>
                                                    </div>
                                                @endif
                                            @else
                                                {!! $child->parent ? '<div class="badge badge-primary">' . $child->parent->menu . '</div>' : '<em class="text-warning text-italic">null</em>' !!}
                                            @endif
                                        </td>
                                        @if (akses('d', $this->lastSegment))
                                            <td>
                                                <button wire:click="konfirmasi({{ $child->id }}, '{{ addslashes($child->menu) }}')" type="button"
                                                    class="btn btn-sm btn-danger">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center"><em>Tidak ditemukan</em></td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <div class="d-flex justify-content-end align-items-center">
                        <div>
                            {{ $this->menus->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if (akses('c', $this->lastSegment))
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
                <small class="form-text text-muted" style="line-height: 13px;font-size: 10px">
                    <em class="text-warning">*Setelah menambahkan Menu, agar mencentang akses <mark>Read</mark> pada menu <mark>Akses</mark></em>
                </small>

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
            $('#modalSettingMenu').modal('show');
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

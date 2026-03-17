@props(['id', 'title' => 'Modal', 'simpan' => null, 'ukuran', 'tombol' => null])

{{-- <div wire:ignore.self x-data="{ open: @entangle($attributes->wire('model')).live }" x-show="open" x-cloak class="modal fade show" style="display: block;" tabindex="-1" role="dialog"> --}}
<div wire:ignore.self class="modal fade show" id="{{ $id }}" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-{{ $ukuran }}" role="document">
        <div class="modal-content">

            <div class="modal-header bg-whitesmoke">
                <h5 class="modal-title">{{ $title }}</h5>
                @if ($tombol == 'masal')
                    <a wire:click="masal" class="btn btn-sm btn-primary rounded pt-0 px-2 m-0 ml-1">
                        <i class="fas fa-plus-circle"></i> Masal
                    </a>
                @endif
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>

            <div class="modal-body">
                {{ $slot }}
            </div>

            <div class="modal-footer bg-whitesmoke">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                @if ($simpan)
                    <button wire:click="{{ $simpan }}" type="submit" class="btn btn-primary">
                        Simpan
                    </button>
                @endif
            </div>

        </div>
    </div>
</div>

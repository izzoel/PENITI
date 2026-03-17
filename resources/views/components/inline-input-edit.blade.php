@props(['id', 'field', 'value', 'editFieldRowId', 'child' => false])

@if ($editFieldRowId == $id . '-' . $field)

    <div class="d-flex justify-content-center">
        <input wire:blur="ubah('{{ $id }}', '{{ $field }}', $event.target.value)"
            wire:keydown.enter="ubah('{{ $id }}', '{{ $field }}', $event.target.value)" class="form-control form-control-sm" value="{{ $value }}"
            @click.outside="$wire.set('editFieldRowId', null)" />
    </div>
@else
    <div wire:click="editRow('{{ $id }}', '{{ $field }}', '{{ $value }}')" class="edit-icon" style="cursor: pointer; position: relative;">
        @if ($field == 'urutan')
            @if (!$child)
                {{ $value ?? '---' }}
            @else
                &rdsh;&nbsp;{{ $value ?? '---' }}
            @endif
        @elseif ($field == 'icon')
            @if ($value)
                <i class="fa {{ $value }}"></i>
                <div class="badge badge-primary">{{ $value }} </div>
            @else
                <em class="text-warning fst-italic">null</em>
            @endif
        @elseif ($field == 'segment')
            @if ($value)
                <div class="badge badge-primary">{{ $value }} </div>
            @else
                <em class="text-warning fst-italic">null</em>
            @endif
        @elseif ($field == 'nama')
            @if ($value)
                {{ format_nama($value) }}
            @endif
        @elseif($field == 'role')
            @if ($value)
                {{-- {{ $user->getRoleNames()->first() }} --}}
            @endif
        @else
            {{ $value ?? '---' }}
        @endif

        <i class="fa-solid fa-pencil text-warning icon-hover"></i>
    </div>
@endif

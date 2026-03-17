@props([
    'id', // id record
    'field', // nama field, misal 'parent_id'
    'value', // value saat ini
    'editFieldRowId', // prop dari Livewire
    'options', // array / collection option untuk select
    'displayField' => 'menu', // kolom yang ditampilkan di option
    'nullable' => true, // apakah boleh null
])

@if ($editFieldRowId == $id . '-' . $field)
    <div x-data x-init="$nextTick(() => {
        $($refs.select).select2({ width: '100%' })
            .on('change', function() {
                @this.ubah('{{ $id }}', '{{ $field }}', $(this).val());
            });
    })">
        <select x-ref="select" class="form-control form-control-sm">
            @if ($nullable)
                <option value="" {{ is_null($value) ? 'selected' : '' }}>None</option>
            @endif

            @foreach ($options as $option)
                @if ($option->id != $id)
                    <option value="{{ $option->id }}" {{ $value == $option->id ? 'selected' : '' }}>
                        {{ $option->{$displayField} }}
                    </option>
                @endif
            @endforeach
        </select>
    </div>
@else
    <div wire:click="editRow('{{ $id }}', '{{ $field }}', @js($value))" class="edit-icon" style="cursor: pointer; position: relative;">
        @if ($value)
            <div class="badge bg-primary">
                {{ $options->firstWhere('id', $value)?->{$displayField} ?? $value }}
            </div>
        @else
            <em class="text-warning fst-italic">null</em>
        @endif
        <i class="fa-solid fa-pencil text-warning icon-hover" style="position: absolute; right: 0;"></i>
    </div>
@endif

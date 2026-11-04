<x-chip-group
    :label="$getLabel()"
    :options="$getOptions()"
    :multiple="$isMultiple()"
    :state-path="$getStatePath()"
    :binding="$applyStateBindingModifiers('wire:model')"
/>

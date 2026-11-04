<x-chip-group
    :label="$getLabel()"
    :options="$getOptions()"
    :selected="$getState()"
    :multiple="$isMultiple()"
    :state-path="$getStatePath()"
    :binding="$applyStateBindingModifiers('wire:model')"
/>

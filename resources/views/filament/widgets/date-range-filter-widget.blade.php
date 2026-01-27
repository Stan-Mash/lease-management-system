<x-filament-widgets::widget>
    <x-filament::section>
        <form wire:submit.prevent="applyFilters">
            {{ $this->schema }}

            <div class="flex gap-3 mt-4">
                <x-filament::button type="submit" size="sm">
                    Apply Filters
                </x-filament::button>

                <x-filament::button type="button" color="gray" size="sm" wire:click="resetFilters">
                    Reset
                </x-filament::button>
            </div>
        </form>
    </x-filament::section>
</x-filament-widgets::widget>

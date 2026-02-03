<x-filament-panels::page>
    @if(count($this->getHeaderWidgets()) > 0)
        <x-filament-widgets::widgets
            :widgets="$this->getHeaderWidgets()"
            :columns="$this->getHeaderWidgetsColumns()"
        />
    @endif

    {{ $this->table }}
</x-filament-panels::page>

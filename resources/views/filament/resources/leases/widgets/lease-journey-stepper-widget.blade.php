@php
    $leaseId = $this->getViewData()['leaseId'] ?? 0;
@endphp
@if ($leaseId > 0)
    <livewire:lease-journey-stepper :lease-id="$leaseId" :wire:key="'stepper-' . $leaseId" />
@endif

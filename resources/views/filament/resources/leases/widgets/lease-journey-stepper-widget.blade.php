@php
    $leaseId = $this->getViewData()['leaseId'] ?? 0;
@endphp
@if ($leaseId > 0)
    @livewire(\App\Livewire\LeaseJourneyStepper::class, ['leaseId' => $leaseId])
@endif

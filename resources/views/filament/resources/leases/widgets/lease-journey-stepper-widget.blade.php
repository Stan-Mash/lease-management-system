@php
    $data           = $this->getViewData();
    $macroSteps     = $data['macroSteps'];
    $detailSteps    = $data['detailSteps'];
    $progress       = $data['progress'];
    $currentLabel   = $data['currentStateLabel'];
    $currentColor   = $data['currentStateColor'];
    $health         = $data['health'];
@endphp

@if ($this->record !== null)
    @include('livewire.lease-journey-stepper', [
        'macroSteps'     => $macroSteps,
        'detailSteps'    => $detailSteps,
        'progress'       => $progress,
        'currentLabel'   => $currentLabel,
        'currentColor'   => $currentColor,
        'health'         => $health,
    ])
@endif

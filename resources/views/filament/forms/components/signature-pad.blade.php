@php
    $statePath = $getStatePath();
    $id        = 'sig-pad-' . str_replace(['.', '[', ']'], '-', $statePath);
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">

    {{-- Canvas area --}}
    <div
        id="{{ $id }}-wrapper"
        style="border:2px solid #d1d5db;border-radius:0.5rem;background:#fff;position:relative;touch-action:none;cursor:crosshair;"
    >
        <canvas
            id="{{ $id }}-canvas"
            width="560"
            height="180"
            style="display:block;width:100%;height:180px;"
        ></canvas>
    </div>

    {{-- Hidden input that Filament reads as the field value --}}
    <input
        type="hidden"
        id="{{ $id }}-input"
        x-ref="{{ $id }}"
        wire:model.live="{{ $statePath }}"
    >

    <div style="display:flex;align-items:center;gap:12px;margin-top:6px;">
        <button
            type="button"
            onclick="document.getElementById('{{ $id }}-instance').clear();
                     document.getElementById('{{ $id }}-input').value='';
                     @this.set('{{ $statePath }}', '');"
            style="font-size:12px;color:#6b7280;text-decoration:underline;background:none;border:none;cursor:pointer;padding:0;"
        >Clear</button>
        <span style="font-size:11px;color:#9ca3af;">Draw your signature in the box above</span>
    </div>

    <script>
    (function () {
        var canvasId  = '{{ $id }}-canvas';
        var inputId   = '{{ $id }}-input';
        var statePath = '{{ $statePath }}';

        function initPad() {
            var canvas = document.getElementById(canvasId);
            if (!canvas || canvas.__sigPadInit) return;
            canvas.__sigPadInit = true;

            // Expose instance for the Clear button
            document.getElementById('{{ $id }}-instance') || Object.defineProperty(document.getElementById('{{ $id }}-wrapper'), '__sigpad', {});
            var pad = new SignaturePad(canvas, {
                minWidth: 1,
                maxWidth: 3,
                penColor: '#1a1a1a',
            });

            // Store instance reference so Clear button can reach it
            document.getElementById('{{ $id }}-instance') || (function(){
                var fakeEl       = document.createElement('span');
                fakeEl.id        = '{{ $id }}-instance';
                fakeEl.style.display = 'none';
                fakeEl.clear     = function(){ pad.clear(); };
                document.body.appendChild(fakeEl);
            })();

            // Resize canvas on wrapper resize to stay sharp
            function resizeCanvas() {
                var ratio  = Math.max(window.devicePixelRatio || 1, 1);
                var wrapper = canvas.parentElement;
                canvas.width  = wrapper.offsetWidth  * ratio;
                canvas.height = 180 * ratio;
                canvas.getContext('2d').scale(ratio, ratio);
                pad.clear();
            }

            // On every stroke end, push the data URI into the hidden input + Livewire state
            pad.addEventListener('endStroke', function () {
                if (pad.isEmpty()) return;
                var dataUrl = pad.toDataURL('image/png');
                document.getElementById(inputId).value = dataUrl;
                // Notify Livewire
                if (window.Livewire) {
                    window.Livewire.find(
                        canvas.closest('[wire\\:id]')?.getAttribute('wire:id')
                    )?.set(statePath, dataUrl);
                }
            });
        }

        // Init immediately if DOM ready, otherwise wait
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initPad);
        } else {
            // Give Filament a moment to render the modal canvas
            setTimeout(initPad, 100);
        }

        // Re-init when Filament opens a modal (Livewire v3 / Alpine events)
        document.addEventListener('filament-modal-opened', function () {
            setTimeout(initPad, 150);
        });
        document.addEventListener('modal-opened', function () {
            setTimeout(initPad, 150);
        });
    })();
    </script>

    {{-- Load signature_pad library if not already loaded --}}
    @once
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>
    @endonce

</x-dynamic-component>

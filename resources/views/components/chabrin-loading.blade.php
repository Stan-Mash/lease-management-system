{{-- Minimal loading overlay --}}
<div
    x-data="{ loading: false }"
    x-on:page-loading.window="loading = true"
    x-on:page-loaded.window="loading = false"
    x-show="loading"
    x-transition.opacity.duration.150ms
    class="fixed inset-0 z-50 flex items-center justify-center bg-white/80"
    style="display: none;"
>
    <div class="flex flex-col items-center gap-3">
        <img src="{{ asset('images/Chabrin-Logo-background.png') }}" alt="" class="h-10 w-10 animate-pulse" />
        <div class="flex gap-1">
            <span class="h-1.5 w-1.5 rounded-full bg-amber-500 animate-bounce [animation-delay:-0.3s]"></span>
            <span class="h-1.5 w-1.5 rounded-full bg-amber-500 animate-bounce [animation-delay:-0.15s]"></span>
            <span class="h-1.5 w-1.5 rounded-full bg-amber-500 animate-bounce"></span>
        </div>
    </div>
</div>

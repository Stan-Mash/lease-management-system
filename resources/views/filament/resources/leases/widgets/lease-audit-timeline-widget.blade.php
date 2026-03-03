<div style="border-radius:12px; background:linear-gradient(135deg,#faf8f4 0%,#fff9e8 100%); border:1.5px solid rgba(218,165,32,0.35); border-left:5px solid #DAA520; padding:20px;">

    <div style="display:flex; align-items:center; gap:10px; margin-bottom:16px;">
        <span style="display:inline-block; width:12px; height:12px; border-radius:3px; background:#DAA520;"></span>
        <span style="font-size:9pt; font-weight:700; text-transform:uppercase; letter-spacing:0.12em; color:#b8960a;">
            Activity Timeline
        </span>
    </div>

    @forelse ($logs as $log)
        <div style="display:flex; gap:12px; position:relative; padding-bottom:{{ $loop->last ? '0' : '20px' }};">
            @if (!$loop->last)
                <span style="position:absolute; left:11px; top:24px; width:1px; height:100%; background:linear-gradient(to bottom,rgba(218,165,32,0.4),rgba(218,165,32,0.08));"></span>
            @endif
            <span style="flex-shrink:0; width:24px; height:24px; border-radius:50%; background:rgba(218,165,32,0.12); color:#92700a; display:flex; align-items:center; justify-content:center;">
                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
            </span>
            <div style="min-width:0; flex:1; padding-top:2px;">
                <div style="font-size:13px; font-weight:500; color:#1a365d;">{{ $log->description ?? $log->formatted_description ?? '' }}</div>
                <div style="font-size:11px; color:#b8960a; margin-top:2px;">
                    {{ $log->user?->name ?? 'System' }} &middot; {{ $log->created_at->format('j M Y, g:i A') }}
                </div>
            </div>
        </div>
    @empty
        <div style="font-size:13px; color:#92700a;">No activity recorded yet.</div>
    @endforelse

</div>

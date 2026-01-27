<div class="space-y-4">
    <div class="bg-warning-50 border border-warning-200 rounded-lg p-4">
        <div class="flex items-center gap-2">
            <svg class="w-5 h-5 text-warning-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
            </svg>
            <p class="text-sm text-warning-800">
                <strong>Preview Note:</strong> This shows the template structure with sample data. Actual lease PDFs will use real data.
            </p>
        </div>
    </div>

    <div class="bg-gray-100 p-6 rounded-lg">
        <div class="bg-white shadow-lg mx-auto" style="max-width: 210mm; min-height: 297mm; padding: 20mm;">
            <div class="prose max-w-none">
                <h3 class="text-lg font-semibold mb-4">Template: {{ $template->name }}</h3>

                <div class="border-t border-gray-200 pt-4">
                    <h4 class="text-sm font-semibold text-gray-600 mb-2">Blade Content:</h4>
                    <div class="bg-gray-50 p-4 rounded border border-gray-200 overflow-x-auto">
                        <pre class="text-xs font-mono whitespace-pre-wrap">{{ Str::limit($template->blade_content, 2000) }}</pre>
                    </div>
                </div>

                @if($template->css_styles)
                <div class="border-t border-gray-200 pt-4 mt-4">
                    <h4 class="text-sm font-semibold text-gray-600 mb-2">CSS Styles:</h4>
                    <div class="bg-gray-50 p-4 rounded border border-gray-200">
                        <ul class="text-xs space-y-1">
                            @foreach($template->css_styles as $key => $value)
                                <li><span class="font-semibold">{{ $key }}:</span> {{ $value }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                @endif

                @if($template->available_variables)
                <div class="border-t border-gray-200 pt-4 mt-4">
                    <h4 class="text-sm font-semibold text-gray-600 mb-2">Available Variables:</h4>
                    <div class="flex flex-wrap gap-2">
                        @foreach($template->available_variables as $variable)
                            <span class="inline-flex items-center px-2 py-1 text-xs font-mono bg-blue-50 text-blue-700 rounded border border-blue-200">
                                @{{ '$' . $variable }}
                            </span>
                        @endforeach
                    </div>
                </div>
                @endif

                <div class="border-t border-gray-200 pt-4 mt-4">
                    <h4 class="text-sm font-semibold text-gray-600 mb-2">Template Info:</h4>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-gray-500">Type:</span>
                            <span class="font-medium ml-2">{{ ucfirst(str_replace('_', ' ', $template->template_type)) }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Source:</span>
                            <span class="font-medium ml-2">{{ ucfirst(str_replace('_', ' ', $template->source_type)) }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Version:</span>
                            <span class="font-medium ml-2">v{{ $template->version_number }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Leases Using:</span>
                            <span class="font-medium ml-2">{{ $template->leases()->count() }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="flex justify-end gap-3">
        <a href="{{ \App\Filament\Resources\LeaseTemplateResource::getUrl('edit', ['record' => $template]) }}"
           class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
            Edit Template
        </a>
    </div>
</div>

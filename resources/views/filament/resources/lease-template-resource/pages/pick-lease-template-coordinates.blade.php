<x-filament-panels::page>
    <style>
        #pdf-container { display: block !important; }
        #pdf-canvas {
            width: 100% !important;
            max-width: none !important;
            height: auto !important;
            display: block !important;
        }
    </style>
    @if (! $pdfUrl)
        <div class="rounded-xl border border-danger-200 bg-danger-50 dark:border-danger-800 dark:bg-danger-900/20 p-6">
            <p class="text-danger-700 dark:text-danger-400">PDF file could not be loaded. Ensure the file exists in storage.</p>
            <a href="{{ \App\Filament\Resources\LeaseTemplateResource::getUrl('edit', ['record' => $record]) }}"
               class="mt-4 inline-flex items-center text-sm font-medium text-primary-600 hover:text-primary-500">
                ← Back to Edit Template
            </a>
        </div>
    @else
        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 overflow-hidden">
            <div class="bg-primary-50 dark:bg-primary-900/20 px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-sm font-semibold text-primary-700 dark:text-primary-400">
                    Pick field positions on your PDF so generated leases look exactly like the original
                </h3>
                <p class="mt-1 text-xs text-primary-600 dark:text-primary-500">
                    1) Select a field below 2) Click on the PDF where that value should appear 3) Save when done
                </p>
            </div>

            <div class="p-4 flex flex-col lg:flex-row gap-4 min-h-[calc(100vh-8rem)]" x-data="coordinatePicker()">
                {{-- Field list --}}
                <div class="lg:w-64 flex-shrink-0 space-y-2 overflow-y-auto max-h-[calc(100vh-10rem)]">
                    <p class="text-xs font-semibold text-gray-500 uppercase">Text fields</p>
                    @foreach ($textFields as $key => $label)
                        <button type="button"
                                @click="selectField('{{ $key }}', false)"
                                :class="{ 'ring-2 ring-primary-500': selectedField === '{{ $key }}' }"
                                class="w-full text-left px-3 py-2 rounded-lg text-sm border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                            <span class="font-medium">{{ $label }}</span>
                            <span class="block text-xs text-gray-500" x-show="coordinates['{{ $key }}']">
                                Page <span x-text="coordinates['{{ $key }}']?.page || 1"></span>,
                                <span x-text="Math.round(coordinates['{{ $key }}']?.x || 0)"></span>,
                                <span x-text="Math.round(coordinates['{{ $key }}']?.y || 0)"></span>
                            </span>
                        </button>
                    @endforeach
                    <p class="text-xs font-semibold text-gray-500 uppercase mt-4">Signature areas</p>
                    @foreach ($signatureFields as $key => $label)
                        <button type="button"
                                @click="selectField('{{ $key }}', true)"
                                :class="{ 'ring-2 ring-primary-500': selectedField === '{{ $key }}' }"
                                class="w-full text-left px-3 py-2 rounded-lg text-sm border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                            <span class="font-medium">{{ $label }}</span>
                            <span class="block text-xs text-gray-500" x-show="coordinates['{{ $key }}']">
                                Page <span x-text="coordinates['{{ $key }}']?.page || 1"></span>,
                                <span x-text="Math.round(coordinates['{{ $key }}']?.x || 0)"></span>×<span x-text="Math.round(coordinates['{{ $key }}']?.y || 0)"></span>
                            </span>
                        </button>
                    @endforeach
                    <div class="pt-4 space-y-2">
                        <button type="button"
                                @click="saveMap()"
                                class="w-full px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg font-semibold text-sm transition">
                            Save coordinate map
                        </button>
                        <a href="{{ \App\Filament\Resources\LeaseTemplateResource::getUrl('edit', ['record' => $record]) }}"
                           class="block w-full text-center px-4 py-2 text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100 text-sm">
                            ← Cancel
                        </a>
                    </div>
                </div>

                {{-- PDF display --}}
                <div class="flex-1 min-w-0 flex flex-col min-h-0">
                    <div class="flex gap-2 mb-2 flex-shrink-0">
                        <button type="button" @click="prevPage()" :disabled="currentPage <= 1"
                                class="px-3 py-1 text-sm border rounded disabled:opacity-50">← Prev</button>
                        <span class="py-1 text-sm" x-text="'Page ' + currentPage + ' of ' + totalPages"></span>
                        <button type="button" @click="nextPage()" :disabled="currentPage >= totalPages"
                                class="px-3 py-1 text-sm border rounded disabled:opacity-50">Next →</button>
                    </div>
                    <div id="pdf-container" class="w-full h-[85vh] overflow-auto bg-gray-200 block">
                        <canvas id="pdf-canvas"
                                @click="onCanvasClick($event)"
                                class="cursor-crosshair w-full h-auto block shadow-2xl"
                                :style="selectedField ? 'cursor: crosshair;' : 'cursor: default;'"></canvas>
                    </div>
                    <p class="mt-2 text-xs text-gray-500" x-show="selectedField">
                        Click on the PDF to place <strong x-text="selectedField"></strong>
                    </p>
                </div>
            </div>
        </div>

        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
        <script nonce="{{ $cspNonce ?? '' }}">
            if (typeof pdfjsLib !== 'undefined') {
                pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
            }

            function coordinatePicker() {
                var pdfDocRef = null;
                var viewportRef = null;
                return {
                    pdfUrl: @js($pdfUrl),
                    currentPage: 1,
                    totalPages: 1,
                    viewportSize: { width: 0, height: 0 },
                    unscaledViewport: null,
                    selectedField: null,
                    isSignature: false,
                    coordinates: @js($record->pdf_coordinate_map ?? []),

                    init() {
                        this.loadPdf();
                    },

                    async loadPdf() {
                        if (typeof pdfjsLib === 'undefined') {
                            alert('PDF viewer library could not be loaded. Check your connection or try again.');
                            return;
                        }
                        try {
                            const r = await fetch(this.pdfUrl, { method: 'GET', credentials: 'include' });
                            if (!r.ok) {
                                throw new Error('Server returned ' + r.status);
                            }
                            const contentType = (r.headers.get('Content-Type') || '').toLowerCase();
                            if (!contentType.includes('pdf')) {
                                throw new Error('Server did not return a PDF (Content-Type: ' + (contentType || 'none') + '). You may need to re-upload the PDF for this template.');
                            }
                            const arrayBuffer = await r.arrayBuffer();
                            if (arrayBuffer.byteLength === 0) {
                                throw new Error('PDF file is empty.');
                            }
                            const loadingTask = pdfjsLib.getDocument({ data: arrayBuffer });
                            pdfDocRef = await loadingTask.promise;
                            this.totalPages = pdfDocRef.numPages;
                            await this.renderPage();
                        } catch (e) {
                            console.error('PDF load failed:', e);
                            const msg = e.message || ('Could not load PDF. ' + (e.toString?.() ?? 'Check browser console.'));
                            alert(msg);
                        }
                    },

                    async renderPage() {
                        if (!pdfDocRef) return;
                        await new Promise(function (r) { setTimeout(r, 100); });
                        const page = await pdfDocRef.getPage(this.currentPage);
                        const container = document.getElementById('pdf-container');
                        const unscaledViewport = page.getViewport({ scale: 1.0 });
                        this.unscaledViewport = { width: unscaledViewport.width, height: unscaledViewport.height };
                        const scale = (container ? container.clientWidth - 40 : 800) / unscaledViewport.width;
                        viewportRef = page.getViewport({ scale: scale });
                        this.viewportSize = { width: viewportRef.width, height: viewportRef.height };
                        const canvas = document.getElementById('pdf-canvas');
                        canvas.width = viewportRef.width;
                        canvas.height = viewportRef.height;
                        canvas.style.width = '100%';
                        canvas.style.height = 'auto';
                        const ctx = canvas.getContext('2d');
                        await page.render({ canvasContext: ctx, viewport: viewportRef }).promise;
                    },

                    async prevPage() {
                        if (this.currentPage > 1) {
                            this.currentPage--;
                            await this.renderPage();
                        }
                    },

                    async nextPage() {
                        if (this.currentPage < this.totalPages) {
                            this.currentPage++;
                            await this.renderPage();
                        }
                    },

                    selectField(key, isSignature) {
                        this.selectedField = key;
                        this.isSignature = isSignature;
                    },

                    onCanvasClick(ev) {
                        if (!this.selectedField || !this.unscaledViewport) return;
                        const canvas = document.getElementById('pdf-canvas');
                        const rect = canvas.getBoundingClientRect();
                        const unscaledViewport = this.unscaledViewport;

                        const screenX = ev.clientX - rect.left;
                        const screenY = ev.clientY - rect.top;
                        const percentX = screenX / rect.width;
                        const percentY = screenY / rect.height;
                        const ptX = percentX * unscaledViewport.width;
                        const ptY = percentY * unscaledViewport.height;
                        const finalX = ptX * (25.4 / 72);
                        const finalY = ptY * (25.4 / 72);

                        this.coordinates[this.selectedField] = {
                            page: this.currentPage,
                            x: Math.round(finalX * 100) / 100,
                            y: Math.round(finalY * 100) / 100
                        };
                        if (this.isSignature) {
                            this.coordinates[this.selectedField].width = 80;
                            this.coordinates[this.selectedField].height = 30;
                        }
                        this.selectedField = null;
                    },

                    saveMap() {
                        if (Object.keys(this.coordinates).length === 0) {
                            alert('Place at least one field on the PDF.');
                            return;
                        }
                        @this.saveCoordinates(this.coordinates);
                    }
                };
            }
        </script>
    @endif
</x-filament-panels::page>

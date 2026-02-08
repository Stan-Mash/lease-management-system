<?php

return [

    /*
    |--------------------------------------------------------------------------
    | DomPDF Configuration - Chabrin Lease System
    |--------------------------------------------------------------------------
    |
    | Configuration for barryvdh/laravel-dompdf v3.x used to generate
    | lease agreement PDFs, signing documents, and verification pages.
    |
    | @see https://github.com/barryvdh/laravel-dompdf
    | @see https://github.com/dompdf/dompdf/wiki/Usage
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Settings
    |--------------------------------------------------------------------------
    |
    | All DomPDF-specific options are nested under the 'defines' key as
    | required by barryvdh/laravel-dompdf v3.x. Options outside 'defines'
    | are handled by the Laravel wrapper.
    |
    */

    'show_warnings' => false,

    'public_path' => null,

    /*
    |--------------------------------------------------------------------------
    | Rendering Backend
    |--------------------------------------------------------------------------
    |
    | Supported: "CPDF", "GD"
    |
    | CPDF is the default and recommended backend for generating high-quality
    | PDF documents. It supports fonts, images, and complex CSS layouts
    | needed for lease agreements.
    |
    */

    'pdf_backend' => 'CPDF',

    /*
    |--------------------------------------------------------------------------
    | Paper Size & Orientation
    |--------------------------------------------------------------------------
    |
    | Default paper size and orientation for all generated lease documents.
    | Individual documents can override these values at render time.
    |
    | Supported sizes: 'letter', 'legal', 'A4', etc.
    | Supported orientations: 'portrait', 'landscape'
    |
    */

    'default_paper_size' => 'A4',

    'default_paper_orientation' => 'portrait',

    /*
    |--------------------------------------------------------------------------
    | Default Font
    |--------------------------------------------------------------------------
    |
    | The default font family used when no font is specified in the HTML/CSS.
    | Arial/sans-serif provides clean readability for lease documents.
    |
    */

    'default_font' => 'sans-serif',

    /*
    |--------------------------------------------------------------------------
    | DomPDF Internal Defines
    |--------------------------------------------------------------------------
    |
    | These settings map directly to DomPDF's internal configuration options.
    | They control font handling, image loading, security, and rendering.
    |
    */

    'defines' => [

        /*
        |----------------------------------------------------------------------
        | Font Directory
        |----------------------------------------------------------------------
        |
        | The directory where DomPDF will look for custom fonts. Using
        | storage/fonts keeps fonts outside the public directory and
        | persists them across deployments.
        |
        */

        'DOMPDF_FONT_DIR' => storage_path('fonts/'),

        /*
        |----------------------------------------------------------------------
        | Font Cache Directory
        |----------------------------------------------------------------------
        |
        | Directory used by DomPDF to cache compiled font metrics. This
        | significantly speeds up repeated PDF generation for lease
        | documents that use the same fonts.
        |
        */

        'DOMPDF_FONT_CACHE' => storage_path('fonts/'),

        /*
        |----------------------------------------------------------------------
        | Temp Directory
        |----------------------------------------------------------------------
        |
        | Temporary file directory used during PDF generation. Falls back
        | to the system temp directory via sys_get_temp_dir().
        |
        */

        'DOMPDF_TEMP_DIR' => sys_get_temp_dir(),

        /*
        |----------------------------------------------------------------------
        | Chroot / Security Path Restriction
        |----------------------------------------------------------------------
        |
        | Restricts DomPDF's file access to these directories. This is a
        | critical security measure that prevents PDF templates from
        | accessing files outside the allowed paths.
        |
        | We allow access to:
        |   - public/ : for logos, images, and static assets
        |   - storage/ : for generated QR codes, signatures, and fonts
        |
        */

        'DOMPDF_CHROOT' => [
            realpath(base_path('public')),
            realpath(base_path('storage')),
        ],

        /*
        |----------------------------------------------------------------------
        | Unicode Support
        |----------------------------------------------------------------------
        |
        | Enable Unicode character support. Required for proper rendering
        | of tenant/landlord names that may contain non-ASCII characters.
        |
        */

        'DOMPDF_UNICODE_ENABLED' => true,

        /*
        |----------------------------------------------------------------------
        | Remote Content (Images & CSS)
        |----------------------------------------------------------------------
        |
        | Enable loading of remote images and CSS files. This is needed
        | for loading the Chabrin logo and any other assets referenced
        | via URL in lease document templates.
        |
        | WARNING: Only enable this if you trust the content being rendered.
        | All lease templates in this system are internally controlled.
        |
        */

        'DOMPDF_ENABLE_REMOTE' => true,

        /*
        |----------------------------------------------------------------------
        | Image DPI
        |----------------------------------------------------------------------
        |
        | The DPI (dots per inch) for rendered images and the overall
        | document. 150 DPI provides good quality for printed lease
        | documents while keeping file sizes reasonable.
        |
        | Common values:
        |   72  - Screen quality (small files)
        |   96  - Standard web quality
        |   150 - Good print quality (recommended for leases)
        |   300 - High-quality print (large files)
        |
        */

        'DOMPDF_DPI' => 150,

        /*
        |----------------------------------------------------------------------
        | CSS Float Support
        |----------------------------------------------------------------------
        |
        | Enable CSS float support for layout rendering. This is needed
        | for lease document layouts that use floated elements (e.g.,
        | side-by-side signature blocks, header layouts with logos).
        |
        */

        'DOMPDF_ENABLE_CSS_FLOAT' => true,

        /*
        |----------------------------------------------------------------------
        | HTML5 Parser
        |----------------------------------------------------------------------
        |
        | Enable the HTML5 parser for improved handling of modern HTML.
        | The HTML5 parser is more forgiving of markup quirks and handles
        | Blade-rendered HTML templates more reliably.
        |
        */

        'DOMPDF_ENABLE_HTML5PARSER' => true,

        /*
        |----------------------------------------------------------------------
        | PHP Execution in Templates
        |----------------------------------------------------------------------
        |
        | SECURITY: Disable PHP code execution within PDF templates.
        | This prevents any injected PHP from being executed during
        | PDF rendering. All dynamic content should be handled by
        | Blade templates before being passed to DomPDF.
        |
        */

        'DOMPDF_ENABLE_PHP' => false,

        /*
        |----------------------------------------------------------------------
        | JavaScript Support
        |----------------------------------------------------------------------
        |
        | Disable JavaScript execution in PDF documents. JavaScript is
        | not needed for lease document rendering and disabling it
        | reduces the attack surface.
        |
        */

        'DOMPDF_ENABLE_JAVASCRIPT' => false,

        /*
        |----------------------------------------------------------------------
        | Default Media Type
        |----------------------------------------------------------------------
        |
        | The CSS media type used when rendering. 'print' is appropriate
        | for lease documents as it matches print stylesheets and
        | excludes screen-only styles (navigation, buttons, etc.).
        |
        */

        'DOMPDF_DEFAULT_MEDIA_TYPE' => 'print',

        /*
        |----------------------------------------------------------------------
        | Default Font
        |----------------------------------------------------------------------
        |
        | The default font used when no font-family is specified in CSS.
        | 'sans-serif' maps to Arial/Helvetica for clean, professional
        | lease document rendering.
        |
        */

        'DOMPDF_DEFAULT_FONT' => 'sans-serif',

        /*
        |----------------------------------------------------------------------
        | Log Output File
        |----------------------------------------------------------------------
        |
        | Path to a file where DomPDF will write debug/error output.
        | Useful for diagnosing PDF rendering issues with lease
        | templates during development.
        |
        */

        'DOMPDF_LOG_OUTPUT_FILE' => storage_path('logs/dompdf.html'),

        /*
        |----------------------------------------------------------------------
        | Font Height Ratio
        |----------------------------------------------------------------------
        |
        | Adjustment factor for font height calculations. The default
        | value of 1.1 provides good line spacing for body text in
        | lease agreement documents.
        |
        */

        'DOMPDF_FONT_HEIGHT_RATIO' => 1.1,

        /*
        |----------------------------------------------------------------------
        | Allowed Protocols
        |----------------------------------------------------------------------
        |
        | Restrict which protocols DomPDF can use to fetch resources.
        | Only file:// and http(s):// are needed for lease documents.
        |
        */

        'DOMPDF_ALLOWED_PROTOCOLS' => [
            'file://'  => ['rules' => []],
            'http://'  => ['rules' => []],
            'https://' => ['rules' => []],
        ],

        /*
        |----------------------------------------------------------------------
        | Allowed Remote Hosts
        |----------------------------------------------------------------------
        |
        | When DOMPDF_ENABLE_REMOTE is true, this restricts which remote
        | hosts can be accessed. null allows all hosts. For production,
        | consider restricting to your application's domain.
        |
        */

        'DOMPDF_ALLOWED_REMOTE_HOSTS' => null,

    ],

    /*
    |--------------------------------------------------------------------------
    | Debug Settings
    |--------------------------------------------------------------------------
    |
    | When APP_DEBUG is enabled, DomPDF will display warnings and include
    | additional debug information in generated PDFs. This is useful for
    | troubleshooting template rendering issues during development.
    |
    */

    'debug' => env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Memory & Execution Limits
    |--------------------------------------------------------------------------
    |
    | PDF generation for large lease documents (multi-page agreements with
    | embedded images, QR codes, and signature blocks) can be memory and
    | time intensive. These limits ensure the process completes without
    | hitting PHP defaults.
    |
    | memory_limit: 256MB handles multi-page leases with embedded images.
    | time_limit:   300 seconds (5 minutes) allows for complex documents.
    |
    | Set to 0 for no limit (not recommended in production).
    |
    */

    'memory_limit' => env('DOMPDF_MEMORY_LIMIT', '256M'),

    'time_limit' => env('DOMPDF_TIME_LIMIT', 300),

    /*
    |--------------------------------------------------------------------------
    | Remote Content Access
    |--------------------------------------------------------------------------
    |
    | Top-level convenience flag for enabling remote content access.
    | This is required for loading logos and images from public_path()
    | or external URLs in lease document templates.
    |
    | This mirrors DOMPDF_ENABLE_REMOTE inside 'defines' and is used
    | by the barryvdh/laravel-dompdf wrapper.
    |
    */

    'isRemoteEnabled' => true,

    /*
    |--------------------------------------------------------------------------
    | PHP Execution in Templates
    |--------------------------------------------------------------------------
    |
    | Top-level convenience flag. SECURITY: Keep this disabled. All dynamic
    | content should be prepared via Blade templates, not inline PHP in
    | the HTML passed to DomPDF.
    |
    */

    'isPhpEnabled' => false,

    /*
    |--------------------------------------------------------------------------
    | Chroot (Top-Level)
    |--------------------------------------------------------------------------
    |
    | Top-level chroot configuration used by the barryvdh/laravel-dompdf
    | wrapper. Restricts file system access to the project root.
    |
    */

    'chroot' => base_path(),

];

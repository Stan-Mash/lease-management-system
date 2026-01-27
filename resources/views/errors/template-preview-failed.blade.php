<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Template Preview Error</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .error-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 600px;
            width: 100%;
            padding: 40px;
            text-align: center;
        }

        .error-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }

        h1 {
            color: #333;
            font-size: 24px;
            margin-bottom: 12px;
        }

        .error-message {
            color: #666;
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 24px;
        }

        .error-details {
            background: #f8f9fa;
            border-left: 4px solid #dc3545;
            padding: 16px;
            text-align: left;
            border-radius: 4px;
            margin-bottom: 24px;
        }

        .error-details-title {
            font-weight: 600;
            color: #dc3545;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .error-details-content {
            font-family: 'Courier New', monospace;
            font-size: 13px;
            color: #495057;
            word-wrap: break-word;
        }

        .template-info {
            background: #e7f3ff;
            border-radius: 4px;
            padding: 16px;
            margin-bottom: 24px;
            text-align: left;
        }

        .template-info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .template-info-label {
            font-weight: 600;
            color: #495057;
        }

        .template-info-value {
            color: #6c757d;
        }

        .actions {
            display: flex;
            gap: 12px;
            justify-content: center;
        }

        .btn {
            padding: 12px 24px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(108, 117, 125, 0.4);
        }

        .support-info {
            margin-top: 32px;
            padding-top: 24px;
            border-top: 1px solid #e9ecef;
            font-size: 13px;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">📄❌</div>

        <h1>Template Preview Failed</h1>

        <p class="error-message">
            We encountered an error while trying to generate a preview of your template.
            This could be due to invalid Blade syntax or missing variables.
        </p>

        @if(isset($template))
        <div class="template-info">
            <div class="template-info-row">
                <span class="template-info-label">Template Name:</span>
                <span class="template-info-value">{{ $template->name }}</span>
            </div>
            <div class="template-info-row">
                <span class="template-info-label">Template Type:</span>
                <span class="template-info-value">{{ ucwords(str_replace('_', ' ', $template->template_type)) }}</span>
            </div>
            <div class="template-info-row">
                <span class="template-info-label">Version:</span>
                <span class="template-info-value">v{{ $template->version_number }}</span>
            </div>
        </div>
        @endif

        @if(isset($error))
        <div class="error-details">
            <div class="error-details-title">Error Details:</div>
            <div class="error-details-content">{{ $error }}</div>
        </div>
        @endif

        <div class="actions">
            <a href="javascript:history.back()" class="btn btn-primary">
                ← Go Back & Edit
            </a>
            <a href="{{ route('filament.admin.resources.lease-templates.index') }}" class="btn btn-secondary">
                View All Templates
            </a>
        </div>

        <div class="support-info">
            <strong>Common Issues:</strong>
            <ul style="text-align: left; margin-top: 12px; padding-left: 20px; line-height: 1.8;">
                <li>Check for unclosed Blade tags: <code>{{ '{{' }} ... }}</code></li>
                <li>Ensure all variables are properly formatted: <code>{{ '{{ $variable }}' }}</code></li>
                <li>Verify CSS syntax in style tags</li>
                <li>Check for missing closing HTML tags</li>
            </ul>
        </div>
    </div>
</body>
</html>

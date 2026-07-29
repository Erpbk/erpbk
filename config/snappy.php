<?php

/**
 * Resolve a wkhtmltopdf/wkhtmltoimage binary path.
 *
 * The path is handed to a shell, so anything containing spaces (such as the
 * default Windows "C:\Program Files\..." location) has to be quoted or the
 * shell splits it into separate arguments. Falls back to the binary name and
 * lets the system PATH resolve it.
 */
$wkhtmlBinary = static function (string $envKey, string $name): string {
    $binary = env($envKey);

    if (! is_string($binary) || trim($binary) === '') {
        return PHP_OS_FAMILY === 'Windows' ? $name.'.exe' : $name;
    }

    $binary = trim($binary);

    if (str_contains($binary, ' ') && ! str_starts_with($binary, '"')) {
        return '"'.$binary.'"';
    }

    return $binary;
};

return [

    /*
    |--------------------------------------------------------------------------
    | Snappy PDF / Image Configuration
    |--------------------------------------------------------------------------
    |
    | This option contains settings for PDF generation.
    |
    | Enabled:
    |    
    |    Whether to load PDF / Image generation.
    |
    | Binary:
    |    
    |    The file path of the wkhtmltopdf / wkhtmltoimage executable.
    |
    | Timout:
    |    
    |    The amount of time to wait (in seconds) before PDF / Image generation is stopped.
    |    Setting this to false disables the timeout (unlimited processing time).
    |
    | Options:
    |
    |    The wkhtmltopdf command options. These are passed directly to wkhtmltopdf.
    |    See https://wkhtmltopdf.org/usage/wkhtmltopdf.txt for all options.
    |
    | Env:
    |
    |    The environment variables to set while running the wkhtmltopdf process.
    |
    */

    'pdf' => [
        'enabled' => true,
        'binary'  => $wkhtmlBinary('WKHTML_PDF_BINARY', 'wkhtmltopdf'),
        'timeout' => false,
        'options' => [],
        'env'     => [],
    ],

    'image' => [
        'enabled' => true,
        'binary'  => $wkhtmlBinary('WKHTML_IMG_BINARY', 'wkhtmltoimage'),
        'timeout' => false,
        'options' => [],
        'env'     => [],
    ],

];

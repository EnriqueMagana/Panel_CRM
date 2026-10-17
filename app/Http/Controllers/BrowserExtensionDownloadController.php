<?php

namespace App\Http\Controllers;

use App\Models\BrowserExtension;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BrowserExtensionDownloadController extends Controller
{
    public function __invoke(Request $request, BrowserExtension $browserExtension): StreamedResponse
    {
        abort_unless(
            $browserExtension->is_active || $request->user()->can('browser_extensions.manage'),
            404,
        );
        abort_unless(Storage::disk('public')->exists($browserExtension->zip_path), 404);

        return Storage::disk('public')->download(
            $browserExtension->zip_path,
            $browserExtension->slug.'-'.$browserExtension->version.'.zip',
            ['Content-Type' => 'application/zip'],
        );
    }
}

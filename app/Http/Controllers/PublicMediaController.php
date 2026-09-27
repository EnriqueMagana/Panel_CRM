<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PublicMediaController extends Controller
{
    public function __invoke(string $path): BinaryFileResponse
    {
        abort_if(Str::contains($path, ['..', '\\']), 404);

        $disk = Storage::disk('public');
        abort_unless($disk->exists($path), 404);

        return response()
            ->file($disk->path($path))
            ->setPrivate()
            ->setMaxAge(31_536_000)
            ->setAutoEtag()
            ->setAutoLastModified()
            ->setImmutable();
    }
}

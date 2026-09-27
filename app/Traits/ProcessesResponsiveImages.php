<?php

namespace App\Traits;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

trait ProcessesResponsiveImages
{
    /**
     * Store optimized WebP variants. Animated GIFs may also keep their original
     * file so chat playback is not flattened to a single frame.
     *
     * @return array<string, string>
     */
    protected function storeResponsiveImage(
        UploadedFile $image,
        string $directory,
        bool $square = false,
        bool $preserveAnimatedGif = false,
    ): array {
        abort_unless(extension_loaded('gd') && function_exists('imagewebp'), 500, 'GD con soporte WebP no está disponible.');

        $contents = file_get_contents($image->getRealPath());
        $source = $contents === false ? false : @imagecreatefromstring($contents);

        if ($source === false) {
            throw new RuntimeException('No se pudo procesar la imagen recibida.');
        }

        $source = $this->orientImage($source, $image);
        $disk = Storage::disk('public');
        $directory = trim($directory, '/');
        $basename = (string) Str::uuid();
        $storedPaths = [];
        $variants = [];

        try {
            foreach (['small' => 320, 'medium' => 960, 'large' => 1920] as $name => $maximum) {
                $resized = $this->resizeImage($source, $maximum, $square);
                $path = "{$directory}/{$basename}-{$name}.webp";

                ob_start();
                $encoded = imagewebp($resized, null, $name === 'large' ? 82 : 78);
                $webp = ob_get_clean();
                imagedestroy($resized);

                if (! $encoded || ! is_string($webp) || ! $disk->put($path, $webp)) {
                    throw new RuntimeException('No se pudo guardar una variante WebP.');
                }

                $variants[$name] = $path;
                $storedPaths[] = $path;
            }

            if ($preserveAnimatedGif && $image->getMimeType() === 'image/gif') {
                $originalPath = "{$directory}/{$basename}-original.gif";

                if (! $disk->put($originalPath, $contents)) {
                    throw new RuntimeException('No se pudo conservar el GIF animado.');
                }

                $variants['original'] = $originalPath;
                $storedPaths[] = $originalPath;
            }
        } catch (\Throwable $exception) {
            $disk->delete($storedPaths);
            throw $exception;
        } finally {
            imagedestroy($source);
        }

        return $variants;
    }

    /**
     * @param  array<string, string>|null  $variants
     */
    protected function deleteResponsiveImages(?array $variants, ?string $legacyPath = null): void
    {
        $paths = collect($variants ?? [])
            ->push($legacyPath)
            ->filter(fn ($path) => is_string($path) && $path !== '')
            ->unique()
            ->values()
            ->all();

        if ($paths !== []) {
            Storage::disk('public')->delete($paths);
        }
    }

    private function resizeImage(\GdImage $source, int $maximum, bool $square): \GdImage
    {
        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);

        if ($square) {
            $crop = min($sourceWidth, $sourceHeight);
            $sourceX = (int) floor(($sourceWidth - $crop) / 2);
            $sourceY = (int) floor(($sourceHeight - $crop) / 2);
            $targetSize = min($maximum, $crop);
            $target = $this->transparentCanvas($targetSize, $targetSize);
            imagecopyresampled($target, $source, 0, 0, $sourceX, $sourceY, $targetSize, $targetSize, $crop, $crop);

            return $target;
        }

        $scale = min(1, $maximum / max($sourceWidth, $sourceHeight));
        $targetWidth = max(1, (int) round($sourceWidth * $scale));
        $targetHeight = max(1, (int) round($sourceHeight * $scale));
        $target = $this->transparentCanvas($targetWidth, $targetHeight);
        imagecopyresampled($target, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $sourceWidth, $sourceHeight);

        return $target;
    }

    private function transparentCanvas(int $width, int $height): \GdImage
    {
        $canvas = imagecreatetruecolor($width, $height);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
        imagefilledrectangle($canvas, 0, 0, $width, $height, $transparent);

        return $canvas;
    }

    private function orientImage(\GdImage $source, UploadedFile $image): \GdImage
    {
        if ($image->getMimeType() !== 'image/jpeg' || ! function_exists('exif_read_data')) {
            return $source;
        }

        $exif = @exif_read_data($image->getRealPath());
        $orientation = (int) ($exif['Orientation'] ?? 1);

        if (in_array($orientation, [2, 4, 5, 7], true)) {
            imageflip($source, $orientation === 2 ? IMG_FLIP_HORIZONTAL : IMG_FLIP_VERTICAL);
        }

        $angle = match ($orientation) {
            3, 4 => 180,
            5, 6 => -90,
            7, 8 => 90,
            default => 0,
        };

        if ($angle === 0) {
            return $source;
        }

        $rotated = imagerotate($source, $angle, imagecolorallocatealpha($source, 0, 0, 0, 127));
        if ($rotated === false) {
            return $source;
        }

        imagedestroy($source);

        return $rotated;
    }
}

<?php

namespace App\Console\Commands;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\User;
use App\Traits\ProcessesResponsiveImages;
use Illuminate\Console\Command;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class OptimizeExistingImages extends Command
{
    use ProcessesResponsiveImages;

    protected $signature = 'images:optimize-existing';

    protected $description = 'Genera variantes WebP responsivas para imágenes existentes';

    public function handle(): int
    {
        $converted = 0;
        $failed = 0;

        ChatConversation::query()
            ->whereNotNull('image_path')
            ->whereNull('image_variants')
            ->eachById(function (ChatConversation $conversation) use (&$converted, &$failed): void {
                $this->convert(
                    $conversation->image_path,
                    'chat/group-images',
                    square: true,
                    persist: function (array $variants) use ($conversation): void {
                        $legacyPath = $conversation->image_path;
                        $conversation->update([
                            'image_path' => $variants['medium'],
                            'image_variants' => $variants,
                        ]);
                        $this->deleteResponsiveImages(null, $legacyPath);
                    },
                    converted: $converted,
                    failed: $failed,
                );
            });

        ChatMessage::query()
            ->whereIn('type', ['image', 'gif'])
            ->whereNotNull('attachment_path')
            ->whereNull('attachment_variants')
            ->eachById(function (ChatMessage $message) use (&$converted, &$failed): void {
                $this->convert(
                    $message->attachment_path,
                    'chat/attachments',
                    preserveAnimatedGif: $message->type === 'gif',
                    persist: function (array $variants) use ($message): void {
                        $legacyPath = $message->attachment_path;
                        $message->update([
                            'attachment_path' => $variants[$message->type === 'gif' ? 'original' : 'medium'],
                            'attachment_variants' => $variants,
                        ]);
                        $this->deleteResponsiveImages(null, $legacyPath);
                    },
                    converted: $converted,
                    failed: $failed,
                );
            });

        User::query()
            ->whereNotNull('profile_photo_path')
            ->whereNull('profile_photo_variants')
            ->eachById(function (User $user) use (&$converted, &$failed): void {
                $this->convert(
                    $user->profile_photo_path,
                    'profile-photos',
                    square: true,
                    persist: function (array $variants) use ($user): void {
                        $legacyPath = $user->profile_photo_path;
                        $user->forceFill([
                            'profile_photo_path' => $variants['medium'],
                            'profile_photo_variants' => $variants,
                        ])->save();
                        $this->deleteResponsiveImages(null, $legacyPath);
                    },
                    converted: $converted,
                    failed: $failed,
                );
            });

        $this->info("Imágenes convertidas: {$converted}. Errores: {$failed}.");

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }

    /**
     * @param  callable(array<string, string>): void  $persist
     */
    private function convert(
        string $path,
        string $directory,
        callable $persist,
        int &$converted,
        int &$failed,
        bool $square = false,
        bool $preserveAnimatedGif = false,
    ): void {
        $disk = Storage::disk('public');

        if (! $disk->exists($path)) {
            $this->warn("No existe: {$path}");
            $failed++;

            return;
        }

        try {
            $fullPath = $disk->path($path);
            $upload = new UploadedFile(
                $fullPath,
                basename($path),
                $disk->mimeType($path) ?: null,
                null,
                true,
            );
            $variants = $this->storeResponsiveImage($upload, $directory, $square, $preserveAnimatedGif);

            try {
                $persist($variants);
                $converted++;
            } catch (\Throwable $exception) {
                $this->deleteResponsiveImages($variants);
                throw $exception;
            }
        } catch (\Throwable $exception) {
            report($exception);
            $this->warn("No se pudo convertir {$path}: {$exception->getMessage()}");
            $failed++;
        }
    }
}

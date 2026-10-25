<?php

namespace App\Domain\Profile\Services;

use App\Models\ProfileImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class ProfileImageProcessor
{
    /**
     * Legacy profile service sizes (including the historical `orginal` spelling).
     *
     * @var list<string>
     */
    public const SIZES = ['', 'orginal', 'thumb'];

    public function storeResized(UploadedFile $file, string $directory = 'profile-images'): string
    {
        if (! function_exists('imagecreatefromstring')) {
            return $file->store($directory, 'local');
        }

        $binary = file_get_contents($file->getRealPath());
        if ($binary === false) {
            throw new RuntimeException('Unable to read uploaded profile image.');
        }

        $source = @imagecreatefromstring($binary);
        if ($source === false) {
            throw new RuntimeException('Uploaded profile image could not be decoded.');
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $max = 2000;
        $scale = min(1, $max / max($width, $height));
        $targetWidth = max(1, (int) floor($width * $scale));
        $targetHeight = max(1, (int) floor($height * $scale));

        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

        $path = trim($directory, '/').'/'.Str::uuid().'.jpg';
        $absolute = Storage::disk('local')->path($path);
        Storage::disk('local')->makeDirectory(dirname($path));
        imagejpeg($canvas, $absolute, 90);
        imagedestroy($canvas);
        imagedestroy($source);

        return $path;
    }

    /**
     * Stream a profile avatar the way the legacy profile microservice did:
     * binary JPEG suitable for <img src="/api/profile/.../image/...">.
     */
    public function stream(ProfileImage $image, ?string $size = null): Response
    {
        $normalized = $this->normalizeSize($size);
        if ($normalized === null) {
            return response()->json(['error' => 'Size not supported'], 404);
        }

        $sourcePath = (string) ($image->path ?? '');
        abort_if($sourcePath === '' || ! Storage::disk('local')->exists($sourcePath), 404);

        if ($normalized === 'orginal') {
            return Storage::disk('local')->response($sourcePath, null, [
                'Content-Type' => 'image/jpeg',
                'Cache-Control' => 'private, max-age=3600',
            ]);
        }

        $cachePath = $this->cachePath($image, $normalized);
        if (! Storage::disk('local')->exists($cachePath)) {
            $this->renderDerivative($sourcePath, $cachePath, $normalized, $image->crop ?? []);
        }

        return Storage::disk('local')->response($cachePath, null, [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    public function normalizeSize(?string $size): ?string
    {
        $size ??= '';
        if ($size === 'original') {
            $size = 'orginal';
        }

        return in_array($size, self::SIZES, true) ? $size : null;
    }

    /**
     * @param  array<string, mixed>  $crop
     */
    private function renderDerivative(string $sourcePath, string $cachePath, string $size, array $crop): void
    {
        if (! function_exists('imagecreatefromstring')) {
            Storage::disk('local')->copy($sourcePath, $cachePath);

            return;
        }

        $binary = Storage::disk('local')->get($sourcePath);
        $source = @imagecreatefromstring($binary);
        if ($source === false) {
            throw new RuntimeException('Stored profile image could not be decoded.');
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $x = (int) floor($width * (float) ($crop['x'] ?? 0));
        $y = (int) floor($height * (float) ($crop['y'] ?? 0));
        $cropWidth = max(1, (int) floor($width * (float) ($crop['width'] ?? 1)));
        $cropHeight = max(1, (int) floor($height * (float) ($crop['height'] ?? 1)));
        $rotation = 360 - (int) ($crop['rotation'] ?? 0);

        $cropped = imagecreatetruecolor($cropWidth, $cropHeight);
        imagecopy($cropped, $source, 0, 0, $x, $y, $cropWidth, $cropHeight);
        imagedestroy($source);

        if ($rotation % 360 !== 0) {
            $rotated = imagerotate($cropped, $rotation, 0);
            if ($rotated !== false) {
                imagedestroy($cropped);
                $cropped = $rotated;
            }
        }

        $fitted = match ($size) {
            'thumb' => $this->fit($cropped, 100, 150),
            default => $this->fit($cropped, 500, 750),
        };

        Storage::disk('local')->makeDirectory(dirname($cachePath));
        imagejpeg($fitted, Storage::disk('local')->path($cachePath), 90);
        imagedestroy($fitted);
    }

    /**
     * @param  \GdImage  $source
     * @return \GdImage
     */
    private function fit($source, int $targetWidth, int $targetHeight)
    {
        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $scale = max($targetWidth / $sourceWidth, $targetHeight / $sourceHeight);
        $resizedWidth = max(1, (int) ceil($sourceWidth * $scale));
        $resizedHeight = max(1, (int) ceil($sourceHeight * $scale));

        $resized = imagecreatetruecolor($resizedWidth, $resizedHeight);
        imagecopyresampled(
            $resized,
            $source,
            0,
            0,
            0,
            0,
            $resizedWidth,
            $resizedHeight,
            $sourceWidth,
            $sourceHeight
        );
        imagedestroy($source);

        $out = imagecreatetruecolor($targetWidth, $targetHeight);
        $offsetX = (int) floor(($resizedWidth - $targetWidth) / 2);
        $offsetY = (int) floor(($resizedHeight - $targetHeight) / 2);
        imagecopy($out, $resized, 0, 0, $offsetX, $offsetY, $targetWidth, $targetHeight);
        imagedestroy($resized);

        return $out;
    }

    private function cachePath(ProfileImage $image, string $size): string
    {
        $fingerprint = md5((string) $image->path.'|'.json_encode($image->crop ?? []));

        return 'profile-images/cache/'.$image->id.'-'.$size.'-'.$fingerprint.'.jpg';
    }
}

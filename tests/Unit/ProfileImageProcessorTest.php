<?php

use App\Domain\Profile\Services\ProfileImageProcessor;
use App\Models\ProfileImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('profile image processor normalizes legacy size spellings', function () {
    $processor = app(ProfileImageProcessor::class);

    expect($processor->normalizeSize(null))->toBe('');
    expect($processor->normalizeSize(''))->toBe('');
    expect($processor->normalizeSize('thumb'))->toBe('thumb');
    expect($processor->normalizeSize('orginal'))->toBe('orginal');
    expect($processor->normalizeSize('original'))->toBe('orginal');
    expect($processor->normalizeSize('huge'))->toBeNull();
});

test('profile image processor stores a resized jpeg and streams size variants', function () {
    Storage::fake('local');

    // Storage::fake replaces the disk root; rebuild processor against it.
    $processor = app(ProfileImageProcessor::class);
    $path = $processor->storeResized(UploadedFile::fake()->image('avatar.jpg', 400, 600));

    Storage::disk('local')->assertExists($path);

    $image = new ProfileImage([
        'id' => 42,
        'profile_id' => 1,
        'path' => $path,
        'approval_state' => 'new',
        'crop' => ['x' => 0.1, 'y' => 0.1, 'width' => 0.8, 'height' => 0.8],
    ]);
    $image->id = 42;

    $unsupported = $processor->stream($image, 'huge');
    expect($unsupported->getStatusCode())->toBe(404);
    expect($unsupported->getContent())->toContain('Size not supported');

    $thumb = $processor->stream($image, 'thumb');
    expect($thumb->getStatusCode())->toBe(200);
    expect($thumb->headers->get('content-type'))->toStartWith('image/');
});

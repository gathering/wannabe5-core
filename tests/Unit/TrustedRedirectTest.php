<?php

use App\Domain\Shared\Support\TrustedRedirect;

beforeEach(function () {
    config()->set('backend_compat.allowed_redirect_hosts', ['localhost', 'wannabe5.gathering.org']);
});

test('trusted redirect allows relative paths and allowlisted hosts', function () {
    expect(TrustedRedirect::sanitize('/welcome'))->toBe('/welcome');
    expect(TrustedRedirect::sanitize('https://localhost/home'))->toBe('https://localhost/home');
    expect(TrustedRedirect::sanitize('https://wannabe5.gathering.org/x'))->toBe('https://wannabe5.gathering.org/x');
});

test('trusted redirect rejects open redirects and empty values', function () {
    expect(TrustedRedirect::sanitize(null))->toBe('/');
    expect(TrustedRedirect::sanitize(''))->toBe('/');
    expect(TrustedRedirect::sanitize('https://evil.example/phish'))->toBe('/');
    expect(TrustedRedirect::sanitize('//evil.example/phish'))->toBe('/');
    expect(TrustedRedirect::sanitize('https://evil.example/phish', '/safe'))->toBe('/safe');
});

test('trusted redirect falls back when allowlist is empty', function () {
    config()->set('backend_compat.allowed_redirect_hosts', []);

    expect(TrustedRedirect::sanitize('https://localhost/home', '/fallback'))->toBe('/fallback');
});

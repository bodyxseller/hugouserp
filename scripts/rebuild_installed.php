#!/usr/bin/env php
<?php

/**
 * Rebuild Composer's installed.php from composer.lock
 * 
 * This utility helps in offline environments where composer install/update
 * cannot reach external repositories but composer.lock is available.
 * It reconstructs vendor/composer/installed.php to restore autoloading
 * metadata for installed packages.
 * 
 * Usage: php scripts/rebuild_installed.php
 */

declare(strict_types=1);

$basePath = dirname(__DIR__);
$lockFile = $basePath . '/composer.lock';
$installedFile = $basePath . '/vendor/composer/installed.php';

if (!file_exists($lockFile)) {
    echo "Error: composer.lock not found at {$lockFile}\n";
    exit(1);
}

$lockData = json_decode(file_get_contents($lockFile), true);
if (!$lockData || !isset($lockData['packages'])) {
    echo "Error: Invalid composer.lock file\n";
    exit(1);
}

// Ensure vendor/composer directory exists
$vendorComposerDir = dirname($installedFile);
if (!is_dir($vendorComposerDir)) {
    mkdir($vendorComposerDir, 0755, true);
}

$packages = [];
$devPackages = [];

// Process production packages
foreach ($lockData['packages'] as $package) {
    $packages[] = buildPackageArray($package, $basePath);
}

// Process dev packages if present
if (isset($lockData['packages-dev'])) {
    foreach ($lockData['packages-dev'] as $package) {
        $devPackages[] = buildPackageArray($package, $basePath);
    }
}

$installedPhp = '<?php return ' . var_export([
    'root' => buildRootPackage($basePath),
    'versions' => buildVersionsArray(array_merge($lockData['packages'], $lockData['packages-dev'] ?? []), $basePath),
], true) . ';' . "\n";

if (file_put_contents($installedFile, $installedPhp) === false) {
    echo "Error: Could not write to {$installedFile}\n";
    exit(1);
}

echo "Successfully rebuilt {$installedFile}\n";
echo "Processed " . count($packages) . " production packages and " . count($devPackages) . " dev packages\n";

function buildPackageArray(array $package, string $basePath): array
{
    return [
        'name' => $package['name'] ?? '',
        'version' => $package['version'] ?? '',
        'version_normalized' => $package['version'] ?? '',
        'source' => $package['source'] ?? null,
        'dist' => $package['dist'] ?? null,
        'require' => $package['require'] ?? [],
        'type' => $package['type'] ?? 'library',
        'autoload' => $package['autoload'] ?? [],
        'notification-url' => 'https://packagist.org/downloads/',
        'license' => $package['license'] ?? [],
        'authors' => $package['authors'] ?? [],
        'description' => $package['description'] ?? '',
        'install-path' => '../' . str_replace('/', '-', $package['name'] ?? ''),
    ];
}

function buildRootPackage(string $basePath): array
{
    $composerJson = json_decode(file_get_contents($basePath . '/composer.json'), true);
    
    return [
        'name' => $composerJson['name'] ?? 'root/root',
        'pretty_version' => 'dev-main',
        'version' => 'dev-main',
        'reference' => null,
        'type' => 'project',
        'install_path' => $basePath,
        'aliases' => [],
        'dev' => true,
    ];
}

function buildVersionsArray(array $packages, string $basePath): array
{
    $versions = [];
    
    foreach ($packages as $package) {
        $name = $package['name'] ?? '';
        if ($name) {
            $versions[$name] = [
                'pretty_version' => $package['version'] ?? '',
                'version' => $package['version'] ?? '',
                'reference' => $package['source']['reference'] ?? null,
                'type' => $package['type'] ?? 'library',
                'install_path' => $basePath . '/vendor/' . str_replace('/', '-', $name),
                'aliases' => [],
                'dev_requirement' => false,
            ];
        }
    }
    
    return $versions;
}

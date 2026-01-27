#!/usr/bin/env php
<?php

declare(strict_types=1);

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

if (! file_exists(__DIR__ . '/../vendor/autoload.php')) {
    throw new RuntimeException('Please first run "composer install" (in the root directory) to install dependencies.');
}

require_once __DIR__ . '/../vendor/autoload.php';

$rootComposer = json_decode(file_get_contents(__DIR__ . '/../composer.json'), true);

if (! isset($rootComposer['require'])) {
    throw new RuntimeException('Root composer.json does not contain a "require" section.');
}

$finder = new Symfony\Component\Finder\Finder();
$finder
    ->in(__DIR__ . '/../lib')
    ->exclude('Tests')
    ->exclude('vendor')
    ->name('composer.json')
    ->files()
;

$messages = [];

foreach ($finder as $file) {
    $composerFile = json_decode(file_get_contents($file->getPathname()), true);
    $messages[$fileName = $file->getRelativePathname()] = [];

    if (! isset($composerFile['require'])) {
        $messages[$fileName][] = sprintf('Composer file "%s" does not contain a "require" section.', $file->getRelativePathname());

        continue;
    }

    $packages = array_merge($composerFile['require'], $composerFile['require-dev'] ?? []);

    foreach ($packages as $package => $version) {
        if (str_starts_with($package, 'rollerworks/search')) {
            continue;
        }

        if (! isset($rootComposer['require'][$package]) && ! isset($rootComposer['require-dev'][$package])) {
            $messages[$fileName][] = sprintf('Package "%s" is missing in root composer.json.', $package);

            continue;
        }

        $rootPackageVersion = $rootComposer['require'][$package] ?? $rootComposer['require-dev'][$package];

        if ($rootPackageVersion !== $version) {
            $messages[$fileName][] = sprintf(
                'Package "%s" has different constraints than root composer.json ("%s" vs "%s").',
                $package,
                $version,
                $rootPackageVersion,
            );
        }
    }
}

$hasErrors = false;

echo \PHP_EOL . 'Validating if split-package composer.json files are in-sync with root composer.json...' . \PHP_EOL;
echo '======================================================================================' . \PHP_EOL . \PHP_EOL;

foreach ($messages as $fileName => $errors) {

    echo sprintf('Composer file "lib/%s"', $fileName);

    if ($errors) {
        $hasErrors = true;

        echo ' [FAIL]' . \PHP_EOL . ' - ' . implode(\PHP_EOL . ' - ', $errors) . \PHP_EOL;
    } else {
        echo ' [OK]' . \PHP_EOL;
    }

    echo \PHP_EOL;
}

exit($hasErrors ? 1 : 0);

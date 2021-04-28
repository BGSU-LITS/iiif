<?php

declare(strict_types=1);

namespace Lits\Data;

use Lits\Config\PresentationConfig;
use Lits\Data;

use function Safe\chdir;
use function Safe\file_get_contents;
use function Safe\glob;
use function Safe\json_decode;
use function Safe\ksort;

final class FileData extends Data
{
    /** @return array<string, mixed> */
    public function json(string ...$path): array
    {
        /** @var array<string, mixed> */
        return json_decode($this->read(...$path), true);
    }

    /** @return array<string, ManifestData> */
    public function list(string ...$path): array
    {
        $this->chdir();

        $list = [];

        /** @var string $file */
        foreach (glob(\implode(\DIRECTORY_SEPARATOR, $path)) as $file) {
            $index = \basename($file);

            if ($index === 'manifest') {
                $index = \dirname($file);
            }

            $list[$index] = new ManifestData(
                $index,
                $this->json($file),
                $this->settings
            );
        }

        ksort($list);

        return $list;
    }

    public function manifest(string $index): ManifestData
    {
        $path = \explode('/', $index);
        $path[] = 'manifest';

        return new ManifestData(
            $index,
            $this->json(...$path),
            $this->settings
        );
    }

    public function read(string ...$path): string
    {
        $this->chdir();

        return file_get_contents(\implode(\DIRECTORY_SEPARATOR, $path));
    }

    private function chdir(): void
    {
        \assert($this->settings['presentation'] instanceof PresentationConfig);

        if (\is_string($this->settings['presentation']->path)) {
            chdir($this->settings['presentation']->path);
        }
    }
}

<?php

declare(strict_types=1);

namespace Lits\Data;

use Lits\Config\PresentationConfig;
use Lits\Data;
use Safe\Exceptions\DirException;
use Safe\Exceptions\FilesystemException;
use Safe\Exceptions\JsonException;

use function Safe\chdir;
use function Safe\file_get_contents;
use function Safe\glob;
use function Safe\json_decode;

final class FileData extends Data
{
    /**
     * @return array<array-key, mixed>
     * @throws DirException
     * @throws FilesystemException
     * @throws JsonException
     */
    public function json(string ...$path): array
    {
        return (array) json_decode($this->read(...$path), true);
    }

    /**
     * @return array<string, ManifestData>
     * @throws DirException
     * @throws FilesystemException
     */
    public function list(string ...$path): array
    {
        $this->chdir();

        $list = [];

        foreach (glob(\implode(\DIRECTORY_SEPARATOR, $path)) as $file) {
            \assert(\is_string($file));
            $index = \basename($file);

            if ($index === 'manifest') {
                $index = \dirname($file);
            }

            $list[$index] = new ManifestData(
                $index,
                $this->json($file),
                $this->settings,
            );
        }

        \ksort($list);

        return $list;
    }

    /**
     * @throws DirException
     * @throws FilesystemException
     * @throws JsonException
     */
    public function manifest(string $index): ManifestData
    {
        $path = \explode('/', $index);
        $path[] = 'manifest';

        return new ManifestData(
            $index,
            $this->json(...$path),
            $this->settings,
        );
    }

    /**
     * @throws DirException
     * @throws FilesystemException
     */
    public function read(string ...$path): string
    {
        $this->chdir();

        return file_get_contents(\implode(\DIRECTORY_SEPARATOR, $path));
    }

    /** @throws DirException */
    private function chdir(): void
    {
        \assert($this->settings['presentation'] instanceof PresentationConfig);

        if (\is_string($this->settings['presentation']->path)) {
            chdir($this->settings['presentation']->path);
        }
    }
}

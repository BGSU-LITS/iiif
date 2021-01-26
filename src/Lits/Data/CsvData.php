<?php

declare(strict_types=1);

namespace Lits\Data;

use League\Csv\Reader;
use Lits\Config\CsvConfig;
use Lits\Data;

final class CsvData extends Data
{
    /** @var list<PresentationData> */
    public array $presentations = [];

    public function load(string $file): void
    {
        \assert($this->settings['csv'] instanceof CsvConfig);

        $csv = Reader::createFromPath($file);

        if (!\is_null($this->settings['csv']->header)) {
            $csv->setHeaderOffset($this->settings['csv']->header);
        }

        /** @var array<string, string> $row */
        foreach ($csv as $row) {
            $current = null;
            $index = \trim($row[$this->settings['csv']->index]);

            foreach ($this->presentations as $presentation) {
                if ($presentation->index === $index) {
                    $current = $presentation;
                }
            }

            if (\is_null($current)) {
                $current = new PresentationData($index, $this->settings);

                foreach (
                    $this->settings['csv']->metadata as $field => $element
                ) {
                    $current->addMetadata($element, $row[$field] ?? '');
                }

                $this->presentations[] = $current;
            }

            $current->addPage(
                $row[$this->settings['csv']->sort],
                $row[$this->settings['csv']->id],
                $row[$this->settings['csv']->label],
                $row[$this->settings['csv']->path]
            );
        }
    }
}

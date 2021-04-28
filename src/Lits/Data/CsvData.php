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

            $title = \trim($row[$this->settings['csv']->title]);
            $collection = \trim($row[$this->settings['csv']->collection]);

            foreach ($this->presentations as $presentation) {
                if (
                    $presentation->title === $title &&
                    $presentation->collection === $collection
                ) {
                    $current = $presentation;
                }
            }

            $name = ($collection === '' ? '' : $collection . ': ') . $title;

            if (\is_null($current)) {
                echo 'Creating ' . $name . \PHP_EOL;

                $current = new PresentationData(
                    $title,
                    $collection,
                    $this->settings
                );

                foreach (
                    $this->settings['csv']->metadata as $field => $element
                ) {
                    $current->addMetadata($element, $row[$field] ?? '');
                }

                $this->presentations[] = $current;
            }

            $page = $row[$this->settings['csv']->sort];

            if ($row[$this->settings['csv']->label] !== '') {
                $page .= ' (' . $row[$this->settings['csv']->label] . ')';
            }

            echo 'Adding ' . $page . ' to ' . $name . \PHP_EOL;

            $current->addPage(
                $row[$this->settings['csv']->sort],
                $row[$this->settings['csv']->id],
                $row[$this->settings['csv']->label],
                $row[$this->settings['csv']->path]
            );
        }
    }
}

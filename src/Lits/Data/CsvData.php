<?php

declare(strict_types=1);

namespace Lits\Data;

use League\Csv\Exception as CsvException;
use League\Csv\Reader;
use Lits\Command;
use Lits\Config\CsvConfig;
use Lits\Data;

final class CsvData extends Data
{
    /** @var list<PresentationData> */
    public array $presentations = [];

    /** @throws CsvException */
    public function load(string $file): void
    {
        \assert($this->settings['csv'] instanceof CsvConfig);

        $csv = Reader::createFromPath($file);

        if (!\is_null($this->settings['csv']->header)) {
            $csv->setHeaderOffset($this->settings['csv']->header);
        }

        /** @var array<string, string> $row */
        foreach ($csv as $row) {
            $this->loadRow($row);
        }
    }

    private function current(
        string $title,
        string $collection,
    ): ?PresentationData {
        foreach ($this->presentations as $presentation) {
            if (
                $presentation->title === $title &&
                $presentation->collection === $collection
            ) {
                return $presentation;
            }
        }

        return null;
    }

    /** @param array<string, string> $row */
    private function loadRow(array $row): void
    {
        \assert($this->settings['csv'] instanceof CsvConfig);

        $title = \trim($row[$this->settings['csv']->title]);
        $collection = \trim($row[$this->settings['csv']->collection]);

        $name = ($collection === '' ? '' : $collection . ': ') . $title;
        $current = $this->current($title, $collection);

        if (\is_null($current)) {
            Command::output('Creating ' . $name . \PHP_EOL);

            $current = new PresentationData(
                $title,
                $collection,
                $this->settings,
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

        Command::output('Adding ' . $page . ' to ' . $name . \PHP_EOL);

        $current->addPage(
            $row[$this->settings['csv']->sort],
            $row[$this->settings['csv']->id],
            $row[$this->settings['csv']->label],
            $row[$this->settings['csv']->path],
        );
    }
}

<?php

declare(strict_types=1);

namespace Lits\Data;

use Lits\Data;
use Lits\Settings;

final class ManifestData extends Data
{
    public string $title;
    public ?string $collection = null;

    /** @param array<array-key, mixed> $json */
    public function __construct(
        public string $index,
        array $json,
        Settings $settings,
    ) {
        parent::__construct($settings);

        $this->title = (string) ($json['label'] ?? $index);

        if (!isset($json['metadata']) || !\is_array($json['metadata'])) {
            return;
        }

        $this->collection($json['metadata']);
    }

    /** @param array<mixed> $metadata */
    private function collection(array $metadata): void
    {
        /** @psalm-var mixed $data */
        foreach ($metadata as $data) {
            if (
                isset($data['label']) &&
                isset($data['value']) &&
                $data['label'] === 'Is Part Of'
            ) {
                $this->collection = (string) $data['value'];

                break;
            }
        }
    }
}

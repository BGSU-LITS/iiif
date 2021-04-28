<?php

declare(strict_types=1);

namespace Lits\Data;

use Lits\Data;
use Lits\Settings;

final class ManifestData extends Data
{
    public string $index;
    public string $title;
    public ?string $collection = null;

    /** @param array<string, mixed> $json */
    public function __construct(string $index, array $json, Settings $settings)
    {
        $this->index = $index;
        $this->title = (string) ($json['label'] ?? $index);

        if (\is_array($json['metadata'])) {
            foreach ($json['metadata'] as $metadata) {
                if ($metadata['label'] === 'Is Part Of') {
                    $this->collection = $metadata['value'];

                    break;
                }
            }
        }

        parent::__construct($settings);
    }
}

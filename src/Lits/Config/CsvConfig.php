<?php

declare(strict_types=1);

namespace Lits\Config;

use Lits\Config;

final class CsvConfig extends Config
{
    public ?int $header = 0;

    public string $id = 'Asset ID(s)';
    public string $index = 'Title';
    public string $label = 'Caption';
    public string $path = 'ZDam Path';
    public string $sort = 'Original filename';

    /** @var array<string, string> */
    public array $metadata = [
        'Title' => 'Title',
        'Alternative Title' => 'Alternative Title',
        'Rights' => 'Rights',
        'Description' => 'Description',
        'Date' => 'Date',
        'Date (Free Form)' => 'Date',
        'Created' => 'Date Created',
        'Created (free form)' => 'Date Created',
        'Modified' => 'Date Modified',
        'Subject' => 'Subject',
        'Creator' => 'Creator',
        'Contributor' => 'Contributor',
        'Rights Holder' => 'Rights Holder',
        'Publisher' => 'Publisher',
        'File Format' => 'Format',
        'Format' => 'Format',
        'Type' => 'Type',
        'Extent' => 'Extent',
        'Medium' => 'Medium',
        'Spatial Coverage' => 'Spatial Coverage',
        'Coverage' => 'Coverage',
        'Temporal Coverage' => 'Temporal Coverage',
        'Language' => 'Language',
        'Identifier' => 'Identifier',
        'Source' => 'Source',
        'Is Part Of' => 'Is Part Of',
        'Is Referenced By' => 'Is Referenced By',
        'Relation' => 'Relation',
        'Provenance' => 'Provenance',
        'References' => 'References',
    ];
}

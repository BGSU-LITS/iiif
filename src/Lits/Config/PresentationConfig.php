<?php

declare(strict_types=1);

namespace Lits\Config;

use Lits\Config;

final class PresentationConfig extends Config
{
    public ?string $path = null;
    public ?string $url = null;
    public ?string $image = null;
    public ?string $key = null;
    public ?string $ext = null;
    public ?string $size = null;

    public string $label = 'Title';
    public string $description = 'Description';
    public string $rights = 'Rights';
}

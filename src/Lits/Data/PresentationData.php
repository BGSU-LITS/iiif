<?php

declare(strict_types=1);

namespace Lits\Data;

use Cocur\Slugify\Slugify;
use IIIF\Generator;
use IIIF\PresentationAPI\Links\Service;
use IIIF\PresentationAPI\Metadata\Metadata;
use IIIF\PresentationAPI\Properties\Thumbnail;
use IIIF\PresentationAPI\Resources\Annotation;
use IIIF\PresentationAPI\Resources\Canvas;
use IIIF\PresentationAPI\Resources\Manifest;
use IIIF\PresentationAPI\Resources\Sequence;
use Lits\Command;
use Lits\Config\PresentationConfig;
use Lits\Data;
use Lits\Exception\InvalidConfigException;
use Lits\Settings;

use function Safe\file_put_contents;
use function Safe\mkdir;
use function Safe\usort;

final class PresentationData extends Data
{
    public string $title;
    public string $collection;

    /** @var array<string, string> */
    public array $metadata = [];

    /** @var list<PageData> */
    public array $pages = [];

    public string $viewingDirection = 'left-to-right';
    public string $viewingHint = 'paged';

    public function __construct(
        string $title,
        string $collection,
        Settings $settings
    ) {
        $this->title = $title;
        $this->collection = $collection;

        parent::__construct($settings);
    }

    public function addPage(
        string $index,
        string $id,
        string $label,
        string $path
    ): void {
        $this->pages[] = new PageData(
            \trim($index),
            \trim($id),
            \trim($label),
            \trim($path),
            $this->settings
        );
    }

    public function addMetadata(string $element, string $data): void
    {
        $data = \trim($data);

        if ($data !== '') {
            $this->metadata[$element] = $data;
        }
    }

    public function save(): void
    {
        \assert($this->settings['presentation'] instanceof PresentationConfig);

        if (
            !\is_string($this->settings['presentation']->path) ||
            !\is_writable($this->settings['presentation']->path)
        ) {
            throw new InvalidConfigException(
                'The presentation path must be set to a writable directory.',
            );
        }

        $path = self::separator($this->settings['presentation']->path) .
            self::separator($this->slug());

        if (!\file_exists($path)) {
            mkdir($path, 0777, true);
        }

        $generator = new Generator();
        $manifest = $this->manifest();

        Command::output('Saving ' . $path . 'manifest' . \PHP_EOL);

        file_put_contents(
            $path . 'manifest',
            $generator->generate($manifest)
        );

        $path .= self::separator('canvas');

        if (!\file_exists($path)) {
            mkdir($path);
        }

        /** @var Sequence */
        $sequence = $manifest->getSequences()[0];

        /** @var Canvas $canvas */
        foreach ($sequence->getCanvases() as $canvas) {
            $canvas->addContext($canvas->getDefaultContext());
            $base = \basename($canvas->getID());

            Command::output('Saving ' . $path . $base . \PHP_EOL);

            file_put_contents(
                $path . $base,
                $generator->generate($canvas)
            );
        }
    }

    public function slug(string $separator = \DIRECTORY_SEPARATOR): string
    {
        if ($this->title === '') {
            return '';
        }

        $slugify = new Slugify();
        $collection = '';

        if ($this->collection !== '') {
            $collection = self::separator(
                $slugify->slugify($this->collection),
                $separator
            );
        }

        return $collection . $slugify->slugify($this->title);
    }

    private function manifest(): Manifest
    {
        \assert($this->settings['presentation'] instanceof PresentationConfig);

        if (!\is_string($this->settings['presentation']->url)) {
            throw new InvalidConfigException(
                'The presentation URL must be specified.',
            );
        }

        $manifest = new Manifest(true);
        $manifest->setID(
            self::separator($this->settings['presentation']->url, '/') .
            $this->slug('/') . '/manifest'
        );

        $manifest->addLabel(
            $this->metadata[$this->settings['presentation']->label]
        );

        $manifest->addDescription(
            $this->metadata[$this->settings['presentation']->description]
        );

        if ($this->metadata[$this->settings['presentation']->rights] !== '') {
            $rights = $this->metadata[$this->settings['presentation']->rights];

            if (\filter_var($rights, \FILTER_VALIDATE_URL) !== false) {
                $manifest->addLicense($rights);
            } else {
                $manifest->addAttribution($rights);
            }
        }

        $manifest->setViewingDirection($this->viewingDirection);
        $manifest->addViewingHint($this->viewingHint);
        $manifest->setMetadata($this->metadata());

        $sequence = $this->sequence();
        $manifest->addSequence($sequence);
        $manifest->addThumbnail($this->thumbnail($sequence));

        return $manifest;
    }

    private function metadata(): Metadata
    {
        $metadata = new Metadata();

        foreach ($this->metadata as $label => $value) {
            $metadata->addLabelValue($label, $value);
        }

        return $metadata;
    }

    private function sequence(): Sequence
    {
        $pages = $this->pages;

        usort(
            $pages,
            fn (PageData $a, PageData $b) => $a->index <=> $b->index
        );

        $sequence = new Sequence();

        foreach ($this->pages as $count => $page) {
            $sequence->addCanvas($page->canvas($count, $this->slug('/')));
        }

        return $sequence;
    }

    private function thumbnail(Sequence $sequence): Thumbnail
    {
        $thumbnail = new Thumbnail();

        /** @var list<Canvas> */
        $canvases = $sequence->getCanvases();

        /** @var Annotation */
        $image = $canvases[0]->getImages()[0];

        /** @var Service */
        $service = $image->getContent()->getServices()[0];

        $thumbnail->setID(
            $service->getID() .
            '/full/120,/90/default.jpg'
        );

        $thumbnail->setService($service);

        return $thumbnail;
    }
}

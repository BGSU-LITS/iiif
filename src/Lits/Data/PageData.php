<?php

declare(strict_types=1);

namespace Lits\Data;

use IIIF\PresentationAPI\Links\Service;
use IIIF\PresentationAPI\Resources\Annotation;
use IIIF\PresentationAPI\Resources\Canvas;
use IIIF\PresentationAPI\Resources\Content;
use Lits\Command;
use Lits\Config\PresentationConfig;
use Lits\Data;
use Lits\Exception\InvalidConfigException;
use Lits\Settings;

use function Safe\file_get_contents;
use function Safe\json_decode;

final class PageData extends Data
{
    public string $index;
    public string $id;
    public string $label;
    public string $path;

    public function __construct(
        string $index,
        string $id,
        string $label,
        string $path,
        Settings $settings
    ) {
        $this->index = $index;
        $this->id = $id;
        $this->label = $label;
        $this->path = $path;

        parent::__construct($settings);
    }

    public function canvas(int $count, string $slug): Canvas
    {
        \assert($this->settings['presentation'] instanceof PresentationConfig);

        if (!\is_string($this->settings['presentation']->url)) {
            throw new InvalidConfigException(
                'The presentation URL must be specified.',
            );
        }

        $canvas = new Canvas();
        $canvas->setID(
            self::separator($this->settings['presentation']->url, '/') .
            self::separator($slug, '/') .
            self::separator('canvas', '/') .
            $count
        );

        Command::output(
            'Fetching ' . $this->index .
            ($this->label === '' ? '' : ' (' . $this->label . ')') . \PHP_EOL
        );

        $annotation = $this->annotation();
        $annotation->setOn($canvas->getID());
        $canvas->addImage($annotation);

        $content = $annotation->getContent();
        $canvas->setWidth($content->getWidth());
        $canvas->setHeight($content->getHeight());

        if ($this->label !== '') {
            $canvas->addLabel($this->label);
        } else {
            $canvas->addLabel($count);
        }

        return $canvas;
    }

    public function extension(): string
    {
        if ($this->path !== '') {
            $pathinfo = \pathinfo($this->path);

            if (isset($pathinfo['extension'])) {
                return $pathinfo['extension'];
            }
        }

        return '';
    }

    private function annotation(): Annotation
    {
        $annotation = new Annotation();
        $annotation->setContent($this->content());

        return $annotation;
    }

    private function content(): Content
    {
        $file = file_get_contents($this->uri());

        /** @var array<string, mixed> */
        $info = json_decode($file, true);

        $content = new Content();

        if (isset($info['@id']) && \is_string($info['@id'])) {
            $content->setID($info['@id'] . '/full/full/0/default.jpg');
            $content->addService($this->service($info['@id']));
        }

        $content->setType('dctypes:Image');
        $content->setFormat('image/jpeg');

        if (isset($info['width'])) {
            $content->setWidth((int) $info['width']);
        }

        if (isset($info['height'])) {
            $content->setHeight((int) $info['height']);
        }

        return $content;
    }

    private function service(string $id): Service
    {
        $service = new Service();
        $service->setID($id);
        $service->setProfile('http://iiif.io/api/image/2/level2.json');

        return $service;
    }

    private function uri(): string
    {
        \assert($this->settings['presentation'] instanceof PresentationConfig);

        if (!\is_string($this->settings['presentation']->image)) {
            throw new InvalidConfigException(
                'The presentation image URL must be specified.',
            );
        }

        $uri = $this->settings['presentation']->image . '/ref=' . $this->id;

        if (\is_string($this->settings['presentation']->key)) {
            $uri .= '&k=' . $this->settings['presentation']->key;
        }

        if (\is_string($this->settings['presentation']->size)) {
            $uri .= '&size=' . $this->settings['presentation']->size;
        }

        $pathinfo = \pathinfo($this->path);

        $ext = '';

        if (isset($pathinfo['extension'])) {
            $ext = $pathinfo['extension'];
        }

        $file = '';

        if (isset($pathinfo['basename'])) {
            $file = $pathinfo['basename'];
        }

        if (\is_string($this->settings['presentation']->ext)) {
            if ($ext !== $this->settings['presentation']->ext) {
                $ext = $this->settings['presentation']->ext;
                $file .= '.' . $ext;
            }
        }

        if ($ext !== '') {
            $uri .= '&ext=' . $ext;
        }

        if ($file !== '') {
            $uri .= '&file=' . $file;
        }

        return $uri . '/info.json';
    }
}

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
use Safe\Exceptions\FilesystemException;
use Safe\Exceptions\JsonException;

use function Safe\file_get_contents;
use function Safe\json_decode;

final class PageData extends Data
{
    public function __construct(
        public string $index,
        public string $id,
        public string $label,
        public string $path,
        Settings $settings,
    ) {
        parent::__construct($settings);
    }

    /**
     * @throws FilesystemException
     * @throws InvalidConfigException
     * @throws JsonException
     */
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
            (string) $count,
        );

        Command::output(
            'Fetching ' . $this->index .
            ($this->label === '' ? '' : ' (' . $this->label . ')') . \PHP_EOL,
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

    /**
     * @throws FilesystemException
     * @throws InvalidConfigException
     * @throws JsonException
     */
    private function annotation(): Annotation
    {
        $annotation = new Annotation();
        $annotation->setContent($this->content());

        return $annotation;
    }

    /**
     * @throws FilesystemException
     * @throws InvalidConfigException
     * @throws JsonException
     */
    private function content(): Content
    {
        $file = file_get_contents($this->uri());

        /** @var array<string, mixed> $info */
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

    /** @throws InvalidConfigException */
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

        $uri .= $this->uriExt();
        $uri .= $this->uriFile();

        return $uri . '/info.json';
    }

    private function uriExt(): string
    {
        \assert($this->settings['presentation'] instanceof PresentationConfig);

        $pathinfo = \pathinfo($this->path);

        $ext = '';

        if (isset($pathinfo['extension'])) {
            $ext = $pathinfo['extension'];
        }

        if (\is_string($this->settings['presentation']->ext)) {
            if ($ext !== $this->settings['presentation']->ext) {
                $ext = $this->settings['presentation']->ext;
            }
        }

        if ($ext !== '') {
            return '&ext=' . $ext;
        }

        return '';
    }

    private function uriFile(): string
    {
        \assert($this->settings['presentation'] instanceof PresentationConfig);

        $pathinfo = \pathinfo($this->path);

        $ext = '';

        if (isset($pathinfo['extension'])) {
            $ext = $pathinfo['extension'];
        }

        $file = $pathinfo['basename'];

        if (\is_string($this->settings['presentation']->ext)) {
            if ($ext !== $this->settings['presentation']->ext) {
                $file .= '.' . $this->settings['presentation']->ext;
            }
        }

        if ($file !== '') {
            return '&file=' . $file;
        }

        return '';
    }
}

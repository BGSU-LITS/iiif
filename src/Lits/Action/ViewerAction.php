<?php

declare(strict_types=1);

namespace Lits\Action;

use Lits\Action;
use Lits\Data\FileData;
use Safe\Exceptions\DirException;
use Safe\Exceptions\FilesystemException;
use Safe\Exceptions\JsonException;
use Slim\Exception\HttpInternalServerErrorException;
use Slim\Exception\HttpNotFoundException;

abstract class ViewerAction extends Action
{
    public function action(): void
    {
        if (!isset($this->data['index'])) {
            throw new HttpInternalServerErrorException($this->request);
        }

        $file = new FileData($this->settings);

        try {
            $json = $file->json($this->data['index'], 'manifest');
        } catch (DirException | FilesystemException $exception) {
            throw new HttpNotFoundException(
                $this->request,
                null,
                $exception
            );
        } catch (JsonException $exception) {
            throw new HttpInternalServerErrorException(
                $this->request,
                null,
                $exception
            );
        }

        if (!isset($json['label']) || !isset($json['@id'])) {
            throw new HttpInternalServerErrorException($this->request);
        }

        $this->render($this->template(), [
            'title' => $json['label'],
            'id' => $json['@id'],
        ]);
    }
}

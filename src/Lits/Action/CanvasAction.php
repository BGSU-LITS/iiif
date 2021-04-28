<?php

declare(strict_types=1);

namespace Lits\Action;

use Lits\Action;
use Lits\Data\FileData;
use Safe\Exceptions\ArrayException;
use Safe\Exceptions\DirException;
use Safe\Exceptions\FilesystemException;
use Safe\Exceptions\JsonException;
use Slim\Exception\HttpInternalServerErrorException;
use Slim\Exception\HttpNotFoundException;

final class CanvasAction extends Action
{
    public function action(): void
    {
        if (!isset($this->data['index'])) {
            throw new HttpInternalServerErrorException($this->request);
        }

        $file = new FileData($this->settings);

        if (isset($this->data['canvas'])) {
            try {
                $this->response->getBody()->write(
                    $file->read(
                        $this->data['index'],
                        'canvas',
                        $this->data['canvas']
                    )
                );
            } catch (DirException | FilesystemException $exception) {
                throw new HttpNotFoundException(
                    $this->request,
                    null,
                    $exception
                );
            }

            $this->cors();
            $this->json();

            return;
        }

        try {
            $context = [
                'manifest' => $file->manifest($this->data['index']),
                'canvases' => $file->list(
                    $this->data['index'],
                    'canvas',
                    '*'
                ),
            ];
        } catch (DirException | FilesystemException $exception) {
            throw new HttpNotFoundException(
                $this->request,
                null,
                $exception
            );
        } catch (ArrayException | JsonException $exception) {
            throw new HttpInternalServerErrorException(
                $this->request,
                null,
                $exception
            );
        }

        $this->render($this->template(), $context);
    }
}

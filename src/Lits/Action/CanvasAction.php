<?php

declare(strict_types=1);

namespace Lits\Action;

use Lits\Action;
use Lits\Data\FileData;
use Safe\Exceptions\DirException;
use Safe\Exceptions\FilesystemException;
use Slim\Exception\HttpInternalServerErrorException;
use Slim\Exception\HttpNotFoundException;

final class CanvasAction extends Action
{
    /**
     * @throws HttpInternalServerErrorException
     * @throws HttpNotFoundException
     */
    public function action(): void
    {
        if (!isset($this->data['index'])) {
            throw new HttpInternalServerErrorException($this->request);
        }

        if (isset($this->data['canvas'])) {
            $this->canvas($this->data['index'], $this->data['canvas']);

            return;
        }

        $file = new FileData($this->settings);

        try {
            $context = [
                'manifest' => $file->manifest($this->data['index']),
                'canvases' => $file->list(
                    $this->data['index'],
                    'canvas',
                    '*',
                ),
            ];

            $this->render($this->template(), $context);
        } catch (DirException | FilesystemException $exception) {
            throw new HttpNotFoundException(
                $this->request,
                null,
                $exception,
            );
        } catch (\Throwable $exception) {
            throw new HttpInternalServerErrorException(
                $this->request,
                null,
                $exception,
            );
        }
    }

    /**
     * @throws HttpInternalServerErrorException
     * @throws HttpNotFoundException
     */
    private function canvas(string $index, string $canvas): void
    {
        $file = new FileData($this->settings);

        try {
            $this->response->getBody()->write(
                $file->read($index, 'canvas', $canvas),
            );

            $this->cors();
            $this->json();
        } catch (DirException | FilesystemException $exception) {
            throw new HttpNotFoundException(
                $this->request,
                null,
                $exception,
            );
        } catch (\Throwable $exception) {
            throw new HttpInternalServerErrorException(
                $this->request,
                null,
                $exception,
            );
        }
    }
}

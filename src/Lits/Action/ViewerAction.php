<?php

declare(strict_types=1);

namespace Lits\Action;

use Lits\Action;
use Lits\Data\FileData;
use Lits\Exception\InvalidDataException;
use Safe\Exceptions\DirException;
use Safe\Exceptions\FilesystemException;
use Slim\Exception\HttpInternalServerErrorException;
use Slim\Exception\HttpNotFoundException;

abstract class ViewerAction extends Action
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

        $file = new FileData($this->settings);

        try {
            $json = $file->json($this->data['index'], 'manifest');

            if (!isset($json['label']) || !isset($json['@id'])) {
                throw new InvalidDataException(
                    'Manifest did not include required data.',
                );
            }

            $this->render($this->template(), [
                'title' => $json['label'],
                'id' => $json['@id'],
            ]);
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

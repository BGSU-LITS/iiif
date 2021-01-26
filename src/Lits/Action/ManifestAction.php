<?php

declare(strict_types=1);

namespace Lits\Action;

use Lits\Action;
use Lits\Data\FileData;
use Safe\Exceptions\DirException;
use Safe\Exceptions\FilesystemException;
use Slim\Exception\HttpInternalServerErrorException;
use Slim\Exception\HttpNotFoundException;

final class ManifestAction extends Action
{
    public function action(): void
    {
        if (!isset($this->data['index'])) {
            throw new HttpInternalServerErrorException($this->request);
        }

        $file = new FileData($this->settings);

        try {
            $this->response->getBody()->write(
                $file->read($this->data['index'], 'manifest')
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
    }
}

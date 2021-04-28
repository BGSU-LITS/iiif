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

final class IndexAction extends Action
{
    public function action(): void
    {
        $context = [];
        $file = new FileData($this->settings);

        try {
            if (isset($this->data['index'])) {
                $context['manifest'] = $file->manifest($this->data['index']);
                $context['title'] = $context['manifest']->title;
            } else {
                $context['manifests'] = $file->list('*', 'manifest');
                $context['manifests'] += $file->list('*', '*', 'manifest');
            }
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

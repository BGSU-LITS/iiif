<?php

declare(strict_types=1);

use Lits\Action\CanvasAction;
use Lits\Action\IndexAction;
use Lits\Action\ManifestAction;
use Lits\Action\Viewer\DivaViewerAction;
use Lits\Action\Viewer\MiradorViewerAction;
use Lits\Action\Viewer\TifyViewerAction;
use Lits\Action\Viewer\UvViewerAction;
use Lits\Command\ProcessCommand;
use Lits\Framework;

return function (Framework $framework): void {
    $framework->app()->get('/process', ProcessCommand::class);

    $index = '{index:(?:[^/]+/)?[^/]+}';

    $framework->app()
        ->get('/' . $index . '/manifest', ManifestAction::class)
        ->setName('manifest');

    $framework->app()
        ->get('/' . $index . '/canvas[/{canvas}]', CanvasAction::class)
        ->setName('canvas');

    $framework->app()
        ->get('/' . $index . '/diva', DivaViewerAction::class)
        ->setName('diva');

    $framework->app()
        ->get('/' . $index . '/mirador', MiradorViewerAction::class)
        ->setName('mirador');

    $framework->app()
        ->get('/' . $index . '/tify', TifyViewerAction::class)
        ->setName('tify');

    $framework->app()
        ->get('/' . $index . '/uv', UvViewerAction::class)
        ->setName('uv');

    $framework->app()
        ->get('/[' . $index . ']', IndexAction::class)
        ->setName('index');
};

<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use Abstract\AbstractCore;
use Abstract\Emitter\HtmlEmitter;
use Abstract\Emitter\JsxEmitter;
use Abstract\Mapper\HtmlElementMapping;
use Abstract\Mapper\HtmlMapper;
use Abstract\Mapper\ReactComponent;
use Abstract\Mapper\ReactMapper;
use Abstract\Render\RenderTarget;

$tree = (new AbstractCore())->parseJsonFile(example_path('11-custom-render-targets.source.json'));

$defaultCore = new AbstractCore();
$customCore = AbstractCore::default()
    ->withRenderTarget('html', RenderTarget::make(
        HtmlMapper::make()->element('input', HtmlElementMapping::tag('x-input')),
        new HtmlEmitter(),
    ))
    ->withRenderTarget('jsx', RenderTarget::make(
        ReactMapper::make()->component('input', ReactComponent::imported(
            source: '@headlessui/react',
            export: 'Input',
            as: 'HeadlessInput',
        )),
        new JsxEmitter(),
    ));

$defaultHtml = $defaultCore->renderHtml($tree);
$defaultJsx = $defaultCore->renderJsx($tree);
$customHtml = $customCore->renderHtml($tree);
$customJsx = $customCore->renderJsx($tree);

example_write_output('11-custom-render-targets.default.html', $defaultHtml);
example_write_output('11-custom-render-targets.default.jsx', $defaultJsx);
example_write_output('11-custom-render-targets.custom.html', $customHtml);
example_write_output('11-custom-render-targets.custom.jsx', $customJsx);

example_print('Default HTML', $defaultHtml);
example_print('Default JSX', $defaultJsx);
example_print('Custom HTML', $customHtml);
example_print('Custom JSX', $customJsx);

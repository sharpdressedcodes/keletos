<?php

namespace Keletos\Component\Rendering;

interface IRenderer {

    public function getConfig() : array;

    public function render(array $params = []);

    public function renderPartial(string $view, array $viewParams = []) : string;

    public function renderWidget(\Keletos\Widget\Widget $widget);
}

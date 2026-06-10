<?php

namespace GestContratos\Core;

final class View
{
    public static function render(string $view, array $data = [], string $layout = 'layouts/app'): void
    {
        extract($data, EXTR_SKIP);
        $viewPath = base_path('app/Views/' . $view . '.php');
        if (! file_exists($viewPath)) {
            throw new \RuntimeException("View {$view} nao encontrada.");
        }

        ob_start();
        require $viewPath;
        $content = ob_get_clean();

        if ($layout === '') {
            echo $content;
            return;
        }

        require base_path('app/Views/' . $layout . '.php');
    }
}

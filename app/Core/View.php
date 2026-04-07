<?php
declare(strict_types=1);

class View
{
    public static function render(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        $flash = Flash::get();
        $currentUser = Auth::user();
        $viewPath = __DIR__ . '/../views/' . $view . '.php';

        ob_start();
        require $viewPath;
        $content = ob_get_clean();

        require __DIR__ . '/../views/layouts/app.php';
    }
}

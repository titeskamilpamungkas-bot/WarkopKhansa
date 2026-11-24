<?php

/**
 * Merender view: isi halaman dimasukkan ke dalam layout.
 */
class View
{
    /**
     * @param string $view   nama file di app/Views, tanpa .php (misal "barang/index")
     * @param array  $data   variabel yang tersedia di view dan layout
     * @param string $layout "app" (dengan sidebar) atau "blank" (login, struk)
     */
    public static function render(string $view, array $data = [], string $layout = 'app'): void
    {
        extract($data, EXTR_SKIP);

        ob_start();
        require APP_PATH . '/Views/' . $view . '.php';
        $content = ob_get_clean();

        require APP_PATH . '/Views/layouts/' . $layout . '.php';
    }
}

<?php

/**
 * Kelas dasar seluruh controller.
 */
abstract class Controller
{
    protected function view(string $view, array $data = [], string $layout = 'app'): void
    {
        View::render($view, $data, $layout);
    }

    protected function redirect(string $path): void
    {
        redirect($path);
    }

    /** Akun yang login. Route ber-akses role pasti sudah lolos pemeriksaan Router. */
    protected function user(): array
    {
        return Auth::user();
    }

    /** Ambil nilai string dari $_POST dengan aman. */
    protected function post(string $kunci, string $default = ''): string
    {
        $nilai = $_POST[$kunci] ?? $default;

        return is_string($nilai) ? trim($nilai) : $default;
    }

    /** Ambil nilai string dari $_GET dengan aman. */
    protected function get(string $kunci, string $default = ''): string
    {
        $nilai = $_GET[$kunci] ?? $default;

        return is_string($nilai) ? trim($nilai) : $default;
    }

    /** Ambil bilangan bulat dari $_POST atau $_GET. */
    protected function angka(string $sumber, string $kunci, int $default = 0): int
    {
        $data = $sumber === 'post' ? $_POST : $_GET;
        $nilai = $data[$kunci] ?? null;

        return is_numeric($nilai) ? (int) $nilai : $default;
    }
}

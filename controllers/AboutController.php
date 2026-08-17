<?php

class AboutController extends Controller
{
    public function index(): void
    {
        auth_require(); // redirige a /login si no hay sesión; guarda la URL para volver tras loguear

        $this->view('about/index', [
            'title'       => t('about.title') . ' — ' . env('APP_NAME', 'Tanuki App'),
            'description' => t('about.description'),
        ]);
    }
}
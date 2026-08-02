<?php

class AboutController extends Controller
{
    public function index(): void
    {
        $this->view('about/index', [
            'title'       => t('about.title') . ' — ' . env('APP_NAME', 'Tanuki App'),
            'description' => t('about.description'),
        ]);
    }
}
<?php

class HomeController extends Controller
{
    public function index(): void
    {
        $this->view('home/index', [
            'title'   => t('home.title') . ' — ' . env('APP_NAME', 'Tanuki App'),
            'message' => t('home.message'),
        ]);
    }
}
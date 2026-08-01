<?php

class AboutController extends Controller
{
    public function index(): void
    {
        $this->view('about/index', [
            'title'       => 'About — ' . env('APP_NAME', 'Tanuki App'),
            'description' => 'Tanuki is a lightweight, minimalist PHP framework with MVC architecture.',
        ]);
    }
}
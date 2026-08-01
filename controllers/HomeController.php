<?php

class HomeController extends Controller
{
    public function index(): void
    {
        $this->view('home/index', [
            'title'   => 'Home — ' . env('APP_NAME', 'Tanuki App'),
            'message' => 'Welcome to Tanuki Framework. A lightweight PHP MVC.',
        ]);
    }
}
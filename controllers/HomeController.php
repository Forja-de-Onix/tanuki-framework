<?php

class HomeController {
    public function index() {
        $title = "Inicio";
        $message = "Bienvenido a nuestra página de inicio.";
        view('home', compact('title', 'message'));
    }
}

<?php

class AboutController {
    public function show() {
        $title = "Acerca de";
        $description = "Somos una empresa dedicada a la innovación.";
        view('about', compact('title', 'description'));
    }
}

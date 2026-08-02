<?php

/**
 * Tanuki Framework — Navigation menu entries
 *
 * 'label' is a translation key (see lang/en.json, lang/es.json),
 * resolved through t() at render time — not a literal string.
 */

return [
    ['label' => 'nav.home',  'href' => '/',      'match' => '/'],
    ['label' => 'nav.about', 'href' => '/about', 'match' => '/about'],
    ['label' => 'nav.todo',  'href' => '/todo',  'match' => '/todo'],
];
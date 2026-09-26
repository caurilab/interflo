<?php

declare(strict_types=1);

// Vérifications de scaffolding du panel Filament « admin » — aucune logique métier.

it('redirige les invités du panel admin vers la page de connexion', function (): void {
    $this->get('/admin')->assertRedirect('/admin/login');
});

it('affiche la page de connexion du panel admin', function (): void {
    $this->get('/admin/login')->assertSuccessful();
});

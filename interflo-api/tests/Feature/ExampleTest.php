<?php

declare(strict_types=1);

it('retourne une réponse 200 sur la page d’accueil', function (): void {
    $this->get('/')->assertSuccessful();
});

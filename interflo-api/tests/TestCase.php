<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Purge l'état chaud Redis du jeu entre les tests (D-002 §4.3) :
        // RefreshDatabase ne rafraîchit que PostgreSQL, pas Redis, et les ids
        // de manche/joueur sont réinitialisés — sans purge, une réponse d'un
        // test fuirait dans le suivant (HSETNX → ALREADY_ANSWERED intempestif).
        \Illuminate\Support\Facades\Redis::flushdb();
    }
}

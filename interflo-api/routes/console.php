<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Rotation des codes d'appairage (I-18). ⚠️ Granularité scheduler = 1 minute,
// insuffisante pour la cadence de 15-30 s visée : mécanique de secours en
// attendant l'architecture temps réel (docs/04-architecture.md §5).
Schedule::command('interflo:rotate-pairing-codes')->everyMinute();

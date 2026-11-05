<?php

use App\Models\User;
use App\Services\SrpCsvImporter;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Validation\ValidationException;

Artisan::command('users:admin {email} {--revoke : Remove admin rights instead}', function (string $email) {
    $user = User::query()->where('email', $email)->first();

    if ($user === null) {
        $this->error("No user with email {$email}.");

        return 1;
    }

    $user->forceFill(['is_admin' => ! $this->option('revoke')])->save();
    $user->tokens()->delete();

    $this->info($user->is_admin ? "{$email} is now an admin." : "{$email} is no longer an admin.");
    $this->line('Existing tokens were revoked; the user needs to log in again.');

    return 0;
})->purpose('Grant or revoke admin rights for a user');

Artisan::command('srps:import {path} {--effective-date= : Date for rows without an effective_date (YYYY-MM-DD)}', function (SrpCsvImporter $importer, string $path) {
    if (! is_file($path)) {
        $this->error("File not found: {$path}");

        return 1;
    }

    try {
        $result = $importer->import($path, $this->option('effective-date') ?: null);
    } catch (ValidationException $e) {
        $this->error('Nothing was imported:');
        foreach ($e->errors()['file'] ?? [] as $message) {
            $this->line("  {$message}");
        }

        return 1;
    }

    $this->info("Imported SRP bulletin: {$result['created']} created, {$result['updated']} updated.");

    return 0;
})->purpose('Import an SRP bulletin from a CSV file');

Schedule::command('sanctum:prune-expired --hours=24')->daily();

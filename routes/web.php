<?php

use Illuminate\Support\Facades\Route;

Route::view("/", "welcome")->name("home");

Route::middleware(["auth", "verified", "role:admin"])->group(function () {
    Route::view("dashboard", "dashboard")->name("dashboard");

    // Permissions
    Route::livewire("permissions", "pages::permissions.page")->name(
        "permissions",
    );
});

require __DIR__ . "/settings.php";

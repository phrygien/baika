<?php

use Illuminate\Support\Facades\Route;

Route::view("/", "welcome")->name("home");

Route::middleware(["auth", "verified"])->group(function () {
    Route::view("dashboard", "dashboard")->name("dashboard");

    // Permissions
    Route::livewire("permissions", "pages::permissions.page")->name(
        "permissions",
    );

    // Roles
    Route::livewire("roles", "pages::roles.page")->name("roles");

    // Users
    Route::livewire("users", "pages::users.page")->name("users");

    // Country
    Route::livewire("country", "pages::country.page")->name("country");

    // State
    Route::livewire("state", "pages::states.page")->name("states");
});

require __DIR__ . "/settings.php";

<?php

use Illuminate\Support\Facades\Route;

Route::view("/", "welcome")->name("home");

Route::middleware(["auth", "verified", "role:admin"])->group(function () {
    Route::view("dashboard", "dashboard")->name("dashboard");

    // Permissions
    Route::livewire("permissions", "pages::permissions.page")->name(
        "permissions",
    );

    // Roles
    Route::livewire("roles", "pages::roles.page")->name("roles");

    // Users
    Route::livewire("users", "pages::users.page")->name("users");
});

require __DIR__ . "/settings.php";

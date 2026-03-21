<?php

use Illuminate\Support\Facades\Route;

Route::view("/", "shared.home")->name("home");

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

    // Supliers
    Route::livewire("suppliers", "pages::suppliers.page")->name("suppliers");

    // Categories
    Route::livewire("categories", "pages::categories.page")->name("categories");

    // Brands
    Route::livewire("brands", "pages::brands.page")->name("brands");

    // Products
    Route::livewire("products", "pages::products.page")->name("products");
});

// Public routes
Route::view("products", "shared.product")->name("products");

require __DIR__ . "/settings.php";

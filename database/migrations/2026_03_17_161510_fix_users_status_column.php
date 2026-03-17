<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // SQLite ne supporte pas ALTER COLUMN, il faut recréer
        DB::statement(
            "UPDATE users SET status = 'active' WHERE status NOT IN ('active', 'inactive', 'pending', 'banned')",
        );

        DB::statement("CREATE TABLE users_new AS SELECT * FROM users");
        // Ou plus proprement :
        Schema::table("users", function (Blueprint $table) {
            $table->string("status")->default("active")->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};

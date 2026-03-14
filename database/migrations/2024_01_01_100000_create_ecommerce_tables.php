<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ═══════════════════════════════════════════════════════════════════════════
 *  MIGRATION UNIQUE — PLATEFORME E-COMMERCE MULTI-ACTEURS
 *  Style : Temu / Jumia adapté Madagascar
 * ═══════════════════════════════════════════════════════════════════════════
 *
 *  ⚠️  Ce fichier suppose que les migrations par défaut de Laravel ont déjà
 *      été exécutées (0001_01_01_000000 à 0001_01_01_000002).
 *      Ces tables NE sont PAS recréées ici :
 *        - users                    ← modifiée via ALTER plus bas
 *        - password_reset_tokens
 *        - sessions
 *        - personal_access_tokens
 *        - notifications            ← déjà créée par Laravel
 *        - cache / cache_locks
 *        - jobs / failed_jobs / job_batches
 *
 *  ORDRE DE CRÉATION (respecte toutes les FK) :
 *   1.  roles
 *   2.  permissions + role_permissions
 *   3.  user_roles             (FK → users, roles)
 *   4.  ALTER users            (ajout colonnes métier)
 *   5.  countries + states + cities
 *   6.  addresses
 *   7.  suppliers + documents + bank_accounts + payouts
 *   8.  transporters + documents + vehicles + delivery_zones
 *             + transporter_zones + shipping_rates + bank_accounts
 *   9.  categories
 *   10. brands + attributes + attribute_values + category_attributes
 *   11. products + product_images + product_tags + product_tag
 *   12. product_variants + variant_attribute_values + inventories
 *             + inventory_movements
 *   13. coupons + coupon_usages
 *   14. promotions + promotion_products
 *   15. flash_sales + slots + products + purchase_limits + waitlists
 *             + notifications (flash) + cart_reservations + analytics
 *             + supplier_requests
 *   16. carts + cart_items          (FK → coupons déjà créée)
 *   17. orders + order_items + order_status_histories
 *   18. payment_methods_config + transactions + wallets + wallet_transactions
 *             + commissions
 *   19. shipments + shipment_items + shipment_trackings + shipment_ratings
 *   20. return_policies + returns + return_items + return_shipments
 *             + return_status_histories + return_refunds
 *   21. disputes + dispute_evidences + dispute_messages
 *             + dispute_status_histories + dispute_escalations
 *             + dispute_resolutions
 *   22. reviews + review_media + review_votes + review_reports
 *             + review_reminders + supplier_reviews + transporter_reviews
 *             + product_qa + product_qa_answers + product_qa_votes
 *             + review_summary_stats
 *   23. customer_profiles + loyalty_transactions + wishlists + wishlist_items
 *             + product_views + recently_viewed + product_comparisons
 *   24. push_subscriptions + email_logs + sms_logs
 *   25. banners + pages + faqs + announcements
 *   26. settings + audit_logs + activity_logs + platform_reports
 *             + support_tickets + support_ticket_messages + tax_rates + currencies
 *   27. search_logs + referrals + store_credits + newsletters + media
 */
return new class extends Migration
{
    // =========================================================================
    //  UP
    // =========================================================================
    public function up(): void
    {
        // ─────────────────────────────────────────────────────────────────────
        // 1. EXTENSION DE LA TABLE users
        //
        //  Les migrations Laravel par défaut créent déjà :
        //    0001_01_01_000000 → users (id, name, email, password, remember_token, timestamps)
        //                        password_reset_tokens, sessions
        //    0001_01_01_000001 → cache, cache_locks
        //    0001_01_01_000002 → jobs, job_batches, failed_jobs
        //
        //  La migration Fortify/Jetstream gère déjà :
        //    2025_08_14_170933 → two_factor_secret, two_factor_recovery_codes,
        //                        two_factor_confirmed_at
        //
        //  Ce fichier s'exécute APRÈS les 0001_* et AVANT le 2025_08_14_*,
        //  donc on ajoute uniquement les colonnes métier manquantes.
        // ─────────────────────────────────────────────────────────────────────
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name')->nullable()->after('id');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('phone')->unique()->nullable()->after('email');
            $table->timestamp('phone_verified_at')->nullable()->after('email_verified_at');
            $table->string('avatar')->nullable()->after('password');
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('locale', 10)->default('fr');
            $table->string('currency', 10)->default('MGA');
            $table->enum('status', ['active', 'suspended', 'banned', 'pending'])->default('pending');
            $table->string('referral_code', 20)->unique()->nullable();
            $table->foreignId('referred_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('two_factor_enabled')->default(false);
            // ⚠️  two_factor_secret / two_factor_recovery_codes / two_factor_confirmed_at
            //     sont ajoutés par 2025_08_14_170933_add_two_factor_columns_to_users_table
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();
            $table->softDeletes();
        });

        // ─────────────────────────────────────────────────────────────────────
        // 2. TABLES LARAVEL NON INCLUSES DANS LES MIGRATIONS PAR DÉFAUT
        //    (Sanctum + Notifications)
        // ─────────────────────────────────────────────────────────────────────
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        // ─────────────────────────────────────────────────────────────────────
        // 2. RÔLES
        // ─────────────────────────────────────────────────────────────────────
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();        // admin, supplier, customer, transporter
            $table->string('display_name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // ─────────────────────────────────────────────────────────────────────
        // 3. PERMISSIONS
        // ─────────────────────────────────────────────────────────────────────
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();        // products.create, orders.view…
            $table->string('display_name');
            $table->string('group')->nullable();     // products, orders, users…
            $table->timestamps();
        });

        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['role_id', 'permission_id']);
        });

        // ─────────────────────────────────────────────────────────────────────
        // 4. RÔLES UTILISATEUR (table dédiée multi-rôles)
        //    FK → users ✓ (créée ci-dessus) et roles ✓
        // ─────────────────────────────────────────────────────────────────────
        Schema::create('user_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();

            $table->foreignId('assigned_by')
                  ->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('revoked_by')
                  ->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamp('expires_at')->nullable();   // NULL = permanent
            $table->timestamp('revoked_at')->nullable();

            $table->enum('status', ['active', 'expired', 'revoked', 'suspended'])
                  ->default('active');
            $table->boolean('is_primary')->default(false);
            $table->string('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'role_id'], 'user_role_unique');
            $table->index(['user_id', 'status']);
            $table->index(['role_id', 'status']);
            $table->index('expires_at');
        });

        // ─────────────────────────────────────────────────────────────────────
        // 5. GÉOGRAPHIE
        // ─────────────────────────────────────────────────────────────────────
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 3)->unique();             // ISO 3166-1 alpha-2
            $table->string('dial_code', 10)->nullable();     // +261, +33…
            $table->string('currency_code', 10)->nullable(); // MGA, EUR…
            $table->string('currency_symbol', 10)->nullable();
            $table->string('flag_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 10)->nullable();
            $table->timestamps();
        });

        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained()->cascadeOnDelete();
            $table->foreignId('state_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('postal_code', 20)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // ─────────────────────────────────────────────────────────────────────
        // 6. ADRESSES (polymorphique : user, supplier, transporter)
        // ─────────────────────────────────────────────────────────────────────
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->morphs('addressable');
            $table->foreignId('country_id')->constrained()->restrictOnDelete();
            $table->foreignId('state_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('city_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('type', ['billing', 'shipping', 'warehouse', 'office', 'pickup'])
                  ->default('shipping');
            $table->string('label')->nullable();             // "Maison", "Bureau"
            $table->string('first_name');
            $table->string('last_name');
            $table->string('company_name')->nullable();
            $table->string('address_line_1');
            $table->string('address_line_2')->nullable();
            $table->string('city_name');
            $table->string('postal_code', 20)->nullable();
            $table->string('phone')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // ─────────────────────────────────────────────────────────────────────
        // 7. FOURNISSEURS
        // ─────────────────────────────────────────────────────────────────────
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('country_id')->constrained()->restrictOnDelete();
            $table->string('shop_name')->unique();
            $table->string('slug')->unique();
            $table->string('logo')->nullable();
            $table->string('banner')->nullable();
            $table->text('description')->nullable();
            $table->string('business_type')->nullable();
            $table->string('registration_number')->nullable();
            $table->string('tax_number')->nullable();
            $table->string('website')->nullable();
            $table->enum('status', ['pending', 'approved', 'suspended', 'rejected', 'under_review'])
                  ->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->decimal('commission_rate', 5, 2)->default(10.00);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_verified')->default(false);
            $table->decimal('average_rating', 3, 2)->default(0.00);
            $table->unsignedInteger('total_reviews')->default(0);
            $table->unsignedInteger('total_sales')->default(0);
            $table->unsignedInteger('total_products')->default(0);
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('supplier_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->enum('document_type', [
                'identity_card', 'passport', 'business_license',
                'tax_certificate', 'bank_statement', 'address_proof', 'other',
            ]);
            $table->string('file_path');
            $table->string('original_name');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('supplier_bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->string('bank_name');
            $table->string('account_holder_name');
            $table->string('account_number');
            $table->string('iban')->nullable();
            $table->string('swift_bic')->nullable();
            $table->string('routing_number')->nullable();
            $table->string('currency', 10)->default('MGA');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_verified')->default(false);
            $table->timestamps();
        });

        Schema::create('supplier_payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bank_account_id')
                  ->nullable()->constrained('supplier_bank_accounts')->nullOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 10)->default('MGA');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'cancelled'])
                  ->default('pending');
            $table->string('reference')->unique();
            $table->string('payment_method')->nullable();    // virement, mvola…
            $table->text('notes')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // ─────────────────────────────────────────────────────────────────────
        // 8. TRANSPORTEURS
        // ─────────────────────────────────────────────────────────────────────
        Schema::create('transporters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('country_id')->constrained()->restrictOnDelete();
            $table->string('company_name');
            $table->string('slug')->unique();
            $table->string('logo')->nullable();
            $table->text('description')->nullable();
            $table->enum('type', ['local', 'national', 'international', 'express', 'standard'])
                  ->default('local');
            $table->string('registration_number')->nullable();
            $table->string('tax_number')->nullable();
            $table->string('website')->nullable();
            $table->enum('status', ['pending', 'approved', 'suspended', 'rejected'])
                  ->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->decimal('commission_rate', 5, 2)->default(5.00);
            $table->integer('max_weight_kg')->default(30);
            $table->integer('max_volume_cm3')->nullable();
            $table->boolean('handles_fragile')->default(false);
            $table->boolean('handles_cold_chain')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_verified')->default(false);
            $table->decimal('average_rating', 3, 2)->default(0.00);
            $table->unsignedInteger('total_reviews')->default(0);
            $table->unsignedInteger('total_deliveries')->default(0);
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('transporter_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transporter_id')->constrained()->cascadeOnDelete();
            $table->enum('document_type', [
                'identity_card', 'passport', 'driver_license', 'vehicle_registration',
                'insurance', 'business_license', 'tax_certificate', 'other',
            ]);
            $table->string('file_path');
            $table->string('original_name');
            $table->date('expiry_date')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'expired'])
                  ->default('pending');
            $table->text('notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('transporter_vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transporter_id')->constrained()->cascadeOnDelete();
            $table->enum('vehicle_type', ['moto', 'voiture', 'utilitaire', 'camion', 'velo', 'autre']);
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->string('plate_number')->unique()->nullable();
            $table->year('year')->nullable();
            $table->decimal('max_weight_kg', 8, 2)->nullable();
            $table->decimal('max_volume_cm3', 10, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('delivery_zones', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('country_id')->constrained()->restrictOnDelete();
            $table->foreignId('state_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('city_id')->nullable()->constrained()->nullOnDelete();
            $table->string('postal_codes')->nullable();
            $table->decimal('latitude_center', 10, 7)->nullable();
            $table->decimal('longitude_center', 10, 7)->nullable();
            $table->decimal('radius_km', 8, 2)->nullable();
            $table->json('polygon_coordinates')->nullable();   // Zone GeoJSON
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('transporter_zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transporter_id')->constrained()->cascadeOnDelete();
            $table->foreignId('delivery_zone_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_pickup_available')->default(true);
            $table->boolean('is_delivery_available')->default(true);
            $table->timestamps();
            $table->unique(['transporter_id', 'delivery_zone_id']);
        });

        Schema::create('shipping_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transporter_id')->constrained()->cascadeOnDelete();
            $table->foreignId('origin_zone_id')
                  ->nullable()->constrained('delivery_zones')->nullOnDelete();
            $table->foreignId('destination_zone_id')
                  ->nullable()->constrained('delivery_zones')->nullOnDelete();
            $table->string('name');
            $table->enum('calculation_type', ['fixed', 'per_kg', 'per_km', 'mixed'])
                  ->default('fixed');
            $table->decimal('base_price', 10, 2);
            $table->decimal('price_per_kg', 10, 2)->default(0.00);
            $table->decimal('price_per_km', 10, 2)->default(0.00);
            $table->decimal('free_shipping_threshold', 12, 2)->nullable();
            $table->integer('estimated_days_min')->default(1);
            $table->integer('estimated_days_max')->default(7);
            $table->decimal('max_weight_kg', 8, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('transporter_bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transporter_id')->constrained()->cascadeOnDelete();
            $table->string('bank_name');
            $table->string('account_holder_name');
            $table->string('account_number');
            $table->string('iban')->nullable();
            $table->string('swift_bic')->nullable();
            $table->string('currency', 10)->default('MGA');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_verified')->default(false);
            $table->timestamps();
        });

        // ─────────────────────────────────────────────────────────────────────
        // 9. CATÉGORIES (auto-référentielle, N niveaux)
        // ─────────────────────────────────────────────────────────────────────
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')
                  ->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->string('icon')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description')->nullable();
            $table->string('meta_keywords')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->decimal('commission_rate', 5, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('depth')->default(0);
            $table->string('path')->nullable();              // "1/5/12" hiérarchie des IDs
            $table->timestamps();
        });

        // ─────────────────────────────────────────────────────────────────────
        // 10. MARQUES & ATTRIBUTS
        // ─────────────────────────────────────────────────────────────────────
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->string('logo')->nullable();
            $table->string('website')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->timestamps();
        });

        Schema::create('attributes', function (Blueprint $table) {
            $table->id();
            $table->string('name');                          // Couleur, Taille…
            $table->string('slug')->unique();
            $table->enum('type', ['color', 'size', 'text', 'number', 'boolean', 'select'])
                  ->default('select');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_filterable')->default(true);
            $table->boolean('is_visible')->default(true);
            $table->boolean('is_variation')->default(true);
            $table->boolean('is_required')->default(false);
            $table->timestamps();
        });

        Schema::create('attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attribute_id')->constrained()->cascadeOnDelete();
            $table->string('value');
            $table->string('slug')->nullable();
            $table->string('color_hex', 7)->nullable();
            $table->string('image')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['attribute_id', 'value']);
        });

        Schema::create('category_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attribute_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['category_id', 'attribute_id']);
        });

        // ─────────────────────────────────────────────────────────────────────
        // 11. PRODUITS
        // ─────────────────────────────────────────────────────────────────────
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('sku')->unique()->nullable();
            $table->text('short_description')->nullable();
            $table->longText('description')->nullable();
            $table->decimal('base_price', 12, 2);
            $table->decimal('compare_at_price', 12, 2)->nullable();
            $table->decimal('cost_price', 12, 2)->nullable();
            $table->string('currency', 10)->default('MGA');
            $table->decimal('weight_kg', 8, 3)->nullable();
            $table->decimal('length_cm', 8, 2)->nullable();
            $table->decimal('width_cm', 8, 2)->nullable();
            $table->decimal('height_cm', 8, 2)->nullable();
            $table->boolean('requires_shipping')->default(true);
            $table->boolean('is_digital')->default(false);
            $table->string('digital_file')->nullable();
            $table->enum('status', ['draft', 'pending_review', 'approved', 'rejected', 'archived'])
                  ->default('draft');
            $table->text('rejection_reason')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('track_inventory')->default(true);
            $table->integer('low_stock_threshold')->default(5);
            $table->string('origin_country', 3)->nullable();
            $table->string('hs_code')->nullable();
            $table->string('barcode')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description')->nullable();
            $table->string('meta_keywords')->nullable();
            $table->decimal('average_rating', 3, 2)->default(0.00);
            $table->unsignedInteger('total_reviews')->default(0);
            $table->unsignedInteger('total_sold')->default(0);
            $table->unsignedInteger('total_views')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
            $table->index(['status', 'is_active']);
            $table->index(['category_id', 'status']);
            $table->index(['supplier_id', 'status']);
            $table->index('average_rating');
            $table->index('total_sold');
        });

        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('image_path');
            $table->string('alt_text')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });

        Schema::create('product_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('product_tag', function (Blueprint $table) {
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_tag_id')->constrained()->cascadeOnDelete();
            $table->primary(['product_id', 'product_tag_id']);
        });

        // ─────────────────────────────────────────────────────────────────────
        // 12. VARIANTES & INVENTAIRE
        // ─────────────────────────────────────────────────────────────────────
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('sku')->unique();
            $table->string('name')->nullable();              // "Rouge / XL"
            $table->decimal('price', 12, 2)->nullable();    // NULL = prix parent
            $table->decimal('compare_at_price', 12, 2)->nullable();
            $table->decimal('cost_price', 12, 2)->nullable();
            $table->decimal('weight_kg', 8, 3)->nullable();
            $table->string('barcode')->nullable();
            $table->string('image')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('product_variant_attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attribute_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attribute_value_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['product_variant_id', 'attribute_id'], 'variant_attr_unique');
        });

        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')
                  ->nullable()->unique()->constrained()->cascadeOnDelete();
            $table->integer('quantity_in_stock')->default(0);
            $table->integer('quantity_reserved')->default(0);
            $table->integer('quantity_sold')->default(0);
            $table->integer('quantity_returned')->default(0);
            $table->boolean('allow_backorder')->default(false);
            $table->timestamp('last_restocked_at')->nullable();
            $table->timestamps();
            $table->index('quantity_in_stock');
        });

        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['in', 'out', 'adjustment', 'reserved', 'unreserved', 'return'])
                  ->default('in');
            $table->integer('quantity');
            $table->integer('quantity_before');
            $table->integer('quantity_after');
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('reason')->nullable();
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // ─────────────────────────────────────────────────────────────────────
        // 13. COUPONS
        //     ⚠️  Doit être créée AVANT carts (FK coupon_id sur carts)
        // ─────────────────────────────────────────────────────────────────────
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('discount_type', ['fixed', 'percentage', 'free_shipping', 'buy_x_get_y'])
                  ->default('percentage');
            $table->decimal('discount_value', 10, 2);
            $table->decimal('minimum_order_amount', 12, 2)->nullable();
            $table->decimal('maximum_discount_amount', 12, 2)->nullable();
            $table->integer('usage_limit')->nullable();
            $table->integer('usage_limit_per_user')->default(1);
            $table->integer('used_count')->default(0);
            $table->boolean('applies_to_sale_items')->default(false);
            $table->boolean('first_order_only')->default(false);
            $table->boolean('requires_account')->default(true);
            $table->json('applicable_categories')->nullable();
            $table->json('applicable_products')->nullable();
            $table->json('excluded_products')->nullable();
            $table->json('excluded_categories')->nullable();
            $table->json('applicable_user_tiers')->nullable();
            $table->json('restricted_countries')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['is_active', 'starts_at', 'expires_at']);
        });

        // ─────────────────────────────────────────────────────────────────────
        // 14. PROMOTIONS
        // ─────────────────────────────────────────────────────────────────────
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('banner_image_desktop')->nullable();
            $table->string('banner_image_mobile')->nullable();
            $table->enum('type', [
                'seasonal', 'bundle', 'buy_x_get_y',
                'category_discount', 'volume_discount', 'loyalty_exclusive',
            ]);
            $table->enum('discount_type', ['fixed', 'percentage'])->default('percentage');
            $table->decimal('discount_value', 10, 2);
            $table->integer('buy_quantity')->nullable();
            $table->integer('get_quantity')->nullable();
            $table->decimal('get_discount_percentage', 5, 2)->nullable();
            $table->json('volume_tiers')->nullable();
            $table->decimal('minimum_order_amount', 12, 2)->nullable();
            $table->decimal('maximum_discount_amount', 12, 2)->nullable();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('promotion_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promotion_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->cascadeOnDelete();
            $table->decimal('discount_override', 10, 2)->nullable();
            $table->timestamps();
        });

        // ─────────────────────────────────────────────────────────────────────
        // 15. VENTES FLASH
        // ─────────────────────────────────────────────────────────────────────
        Schema::create('flash_sales', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('banner_image_desktop')->nullable();
            $table->string('banner_image_mobile')->nullable();
            $table->string('thumbnail_image')->nullable();
            $table->string('background_color', 7)->nullable();
            $table->string('text_color', 7)->nullable();
            $table->string('badge_text')->nullable();
            $table->enum('type', [
                'flash', 'daily_deal', 'weekly_deal', 'lightning',
                'midnight_sale', 'clearance', 'exclusive_members', 'new_user',
            ])->default('flash');
            $table->enum('scope', ['platform', 'supplier', 'collaborative'])->default('platform');
            $table->foreignId('organizer_supplier_id')
                  ->nullable()->constrained('suppliers')->nullOnDelete();
            $table->timestamp('teaser_starts_at')->nullable();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->boolean('is_recurring')->default(false);
            $table->enum('recurrence_pattern', ['daily', 'weekly', 'monthly', 'custom'])->nullable();
            $table->json('recurrence_days')->nullable();
            $table->timestamp('recurrence_ends_at')->nullable();
            $table->json('eligible_user_tiers')->nullable();
            $table->boolean('requires_registration')->default(false);
            $table->integer('max_orders_per_user')->nullable();
            $table->enum('status', [
                'draft', 'scheduled', 'teaser', 'active',
                'paused', 'ended', 'cancelled', 'sold_out',
            ])->default('draft');
            $table->boolean('is_featured')->default(false);
            $table->boolean('show_countdown')->default(true);
            $table->boolean('show_stock_level')->default(true);
            $table->boolean('show_sold_count')->default(false);
            $table->unsignedInteger('total_products')->default(0);
            $table->unsignedInteger('total_views')->default(0);
            $table->unsignedInteger('total_orders')->default(0);
            $table->decimal('total_revenue', 14, 2)->default(0.00);
            $table->unsignedInteger('total_subscribers')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->index(['status', 'starts_at', 'ends_at']);
            $table->index('is_featured');
        });

        Schema::create('flash_sale_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flash_sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title')->nullable();
            $table->string('badge_text')->nullable();
            $table->string('thumbnail_image')->nullable();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('products_count')->default(0);
            $table->unsignedInteger('sold_count')->default(0);
            $table->timestamps();
            $table->index(['flash_sale_id', 'starts_at']);
        });

        Schema::create('flash_sale_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flash_sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('flash_sale_slot_id')
                  ->nullable()->constrained('flash_sale_slots')->nullOnDelete();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_variant_id')
                  ->nullable()->constrained()->nullOnDelete();
            $table->decimal('original_price', 12, 2);
            $table->decimal('flash_price', 12, 2);
            $table->decimal('discount_percentage', 5, 2);
            $table->integer('flash_stock_total');
            $table->integer('flash_stock_reserved')->default(0);
            $table->integer('flash_stock_sold')->default(0);
            // Colonne calculée : retirer si votre DB ne supporte pas les stored columns
            $table->integer('flash_stock_remaining')
                  ->storedAs('flash_stock_total - flash_stock_reserved - flash_stock_sold');
            $table->integer('max_quantity_per_order')->default(1);
            $table->integer('max_quantity_per_user')->nullable();
            $table->enum('status', [
                'scheduled', 'active', 'paused', 'sold_out', 'ended', 'cancelled',
            ])->default('scheduled');
            $table->boolean('is_featured')->default(false);
            $table->boolean('show_stock_level')->default(true);
            $table->unsignedInteger('low_stock_threshold')->default(5);
            $table->unsignedInteger('view_count')->default(0);
            $table->unsignedInteger('add_to_cart_count')->default(0);
            $table->unsignedInteger('checkout_count')->default(0);
            $table->unsignedInteger('waitlist_count')->default(0);
            $table->boolean('restore_stock_after_sale')->default(true);
            $table->integer('stock_restored')->default(0);
            $table->timestamp('stock_restored_at')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['flash_sale_id', 'product_id', 'product_variant_id'], 'flash_product_unique');
            $table->index(['flash_sale_id', 'status']);
            $table->index(['product_id', 'status']);
        });

        Schema::create('flash_sale_purchase_limits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flash_sale_product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity_purchased')->default(0);
            $table->integer('quantity_in_cart')->default(0);
            $table->timestamp('first_purchased_at')->nullable();
            $table->timestamp('last_purchased_at')->nullable();
            $table->timestamps();
            $table->unique(['flash_sale_product_id', 'user_id'], 'flash_limit_unique');
        });

        Schema::create('flash_sale_waitlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flash_sale_product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity_requested')->default(1);
            $table->unsignedInteger('position')->nullable();
            $table->enum('status', ['waiting', 'notified', 'purchased', 'expired', 'cancelled'])
                  ->default('waiting');
            $table->timestamp('notified_at')->nullable();
            $table->timestamp('notification_expires_at')->nullable();
            $table->timestamp('purchased_at')->nullable();
            $table->timestamps();
            $table->unique(['flash_sale_product_id', 'user_id'], 'waitlist_unique');
            $table->index(['flash_sale_product_id', 'status', 'position']);
        });

        Schema::create('flash_sale_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('flash_sale_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('flash_sale_product_id')
                  ->nullable()->constrained('flash_sale_products')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->json('channels');                        // ["email","push","sms"]
            $table->enum('notify_type', [
                'before_start', 'on_start', 'low_stock', 'price_drop', 'restocked',
            ])->default('before_start');
            $table->integer('minutes_before')->default(15);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_notified_at')->nullable();
            $table->enum('status', [
                'pending', 'sent', 'clicked', 'purchased', 'expired', 'unsubscribed',
            ])->default('pending');
            $table->timestamps();
            $table->unique(['user_id', 'flash_sale_id', 'notify_type'], 'flash_notif_unique');
            $table->index(['flash_sale_id', 'status']);
        });

        Schema::create('flash_sale_cart_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flash_sale_product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('session_id')->nullable();
            $table->integer('quantity');
            $table->string('cart_item_token', 64)->unique();
            $table->enum('status', ['active', 'converted', 'expired', 'released'])
                  ->default('active');
            $table->timestamp('expires_at');
            $table->timestamp('converted_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->timestamps();
            $table->index(['flash_sale_product_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->index('expires_at');
        });

        Schema::create('flash_sale_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flash_sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('flash_sale_product_id')
                  ->nullable()->constrained('flash_sale_products')->nullOnDelete();
            $table->timestamp('recorded_at');
            $table->unsignedInteger('views_snapshot')->default(0);
            $table->unsignedInteger('unique_visitors_snapshot')->default(0);
            $table->unsignedInteger('active_carts')->default(0);
            $table->unsignedInteger('orders_count')->default(0);
            $table->unsignedInteger('units_sold')->default(0);
            $table->decimal('revenue', 14, 2)->default(0.00);
            $table->integer('stock_remaining')->default(0);
            $table->decimal('add_to_cart_rate', 5, 2)->default(0.00);
            $table->decimal('conversion_rate', 5, 2)->default(0.00);
            $table->decimal('cart_abandonment_rate', 5, 2)->default(0.00);
            $table->json('top_countries')->nullable();
            $table->timestamps();
            $table->index(['flash_sale_id', 'recorded_at']);
            $table->index(['flash_sale_product_id', 'recorded_at']);
        });

        Schema::create('flash_sale_supplier_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flash_sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')
                  ->nullable()->constrained()->nullOnDelete();
            $table->decimal('proposed_flash_price', 12, 2);
            $table->decimal('original_price', 12, 2);
            $table->integer('proposed_stock');
            $table->integer('max_quantity_per_user')->default(1);
            $table->text('supplier_notes')->nullable();
            $table->enum('status', [
                'pending', 'under_review', 'approved',
                'rejected', 'negotiation', 'withdrawn',
            ])->default('pending');
            $table->decimal('negotiated_flash_price', 12, 2)->nullable();
            $table->integer('negotiated_stock')->nullable();
            $table->text('admin_notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('submitted_at')->useCurrent();
            $table->timestamps();
            $table->unique(['flash_sale_id', 'product_id', 'product_variant_id'], 'flash_request_unique');
            $table->index(['flash_sale_id', 'status']);
            $table->index(['supplier_id', 'status']);
        });

        // ─────────────────────────────────────────────────────────────────────
        // 16. PANIER
        //     ⚠️  Après coupons (FK coupon_id)
        // ─────────────────────────────────────────────────────────────────────
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('session_id')->nullable();
            $table->string('currency', 10)->default('MGA');
            $table->decimal('subtotal', 12, 2)->default(0.00);
            $table->decimal('discount_amount', 12, 2)->default(0.00);
            $table->decimal('shipping_estimate', 12, 2)->default(0.00);
            $table->decimal('total', 12, 2)->default(0.00);
            $table->foreignId('coupon_id')->nullable()->constrained('coupons')->nullOnDelete();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'session_id']);
        });

        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')
                  ->nullable()->constrained()->cascadeOnDelete();
            $table->integer('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('total_price', 12, 2);
            $table->json('options')->nullable();
            $table->timestamps();
            $table->unique(['cart_id', 'product_id', 'product_variant_id'], 'cart_item_unique');
        });

        // ─────────────────────────────────────────────────────────────────────
        // 17. COMMANDES
        // ─────────────────────────────────────────────────────────────────────
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();           // ORD-2024-XXXXX
            $table->foreignId('customer_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('coupon_id')->nullable()->constrained()->nullOnDelete();
            // ── Adresse de livraison (snapshot) ─────────────────────────
            $table->string('shipping_first_name');
            $table->string('shipping_last_name');
            $table->string('shipping_company')->nullable();
            $table->string('shipping_address_line_1');
            $table->string('shipping_address_line_2')->nullable();
            $table->string('shipping_city');
            $table->string('shipping_state')->nullable();
            $table->string('shipping_postal_code')->nullable();
            $table->string('shipping_country', 3);
            $table->string('shipping_phone')->nullable();
            $table->decimal('shipping_latitude', 10, 7)->nullable();
            $table->decimal('shipping_longitude', 10, 7)->nullable();
            // ── Adresse de facturation (snapshot) ───────────────────────
            $table->string('billing_first_name');
            $table->string('billing_last_name');
            $table->string('billing_company')->nullable();
            $table->string('billing_address_line_1');
            $table->string('billing_address_line_2')->nullable();
            $table->string('billing_city');
            $table->string('billing_state')->nullable();
            $table->string('billing_postal_code')->nullable();
            $table->string('billing_country', 3);
            // ── Financier ────────────────────────────────────────────────
            $table->string('currency', 10)->default('MGA');
            $table->decimal('subtotal', 12, 2);
            $table->decimal('discount_amount', 12, 2)->default(0.00);
            $table->decimal('shipping_cost', 12, 2)->default(0.00);
            $table->decimal('tax_amount', 12, 2)->default(0.00);
            $table->decimal('platform_fee', 12, 2)->default(0.00);
            $table->decimal('total', 12, 2);
            // ── Statuts ──────────────────────────────────────────────────
            $table->enum('status', [
                'pending', 'payment_pending', 'paid', 'processing',
                'partially_shipped', 'shipped', 'out_for_delivery',
                'delivered', 'completed', 'cancelled',
                'refund_requested', 'refunded', 'disputed',
            ])->default('pending');
            $table->enum('payment_status', [
                'unpaid', 'paid', 'partially_paid', 'refunded', 'partially_refunded',
            ])->default('unpaid');
            $table->text('customer_notes')->nullable();
            $table->text('internal_notes')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->timestamps();
            $table->index('reference');
            $table->index(['customer_id', 'status']);
            $table->index(['status', 'payment_status']);
            $table->index('created_at');
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_variant_id')
                  ->nullable()->constrained()->nullOnDelete();
            // ── Snapshot produit ─────────────────────────────────────────
            $table->string('product_name');
            $table->string('product_sku')->nullable();
            $table->string('variant_name')->nullable();
            $table->string('product_image')->nullable();
            $table->json('product_options')->nullable();
            // ── Prix ─────────────────────────────────────────────────────
            $table->integer('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('compare_at_price', 12, 2)->nullable();
            $table->decimal('discount_amount', 12, 2)->default(0.00);
            $table->decimal('tax_rate', 5, 2)->default(0.00);
            $table->decimal('tax_amount', 12, 2)->default(0.00);
            $table->decimal('total_price', 12, 2);
            // ── Commission ───────────────────────────────────────────────
            $table->decimal('commission_rate', 5, 2)->default(10.00);
            $table->decimal('commission_amount', 12, 2)->default(0.00);
            $table->decimal('supplier_revenue', 12, 2)->default(0.00);
            $table->enum('status', [
                'pending', 'confirmed', 'processing', 'shipped',
                'delivered', 'cancelled', 'returned', 'refunded',
            ])->default('pending');
            $table->boolean('is_reviewed')->default(false);
            $table->timestamps();
        });

        Schema::create('order_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->text('notes')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip_address')->nullable();
            $table->timestamps();
        });

        // ─────────────────────────────────────────────────────────────────────
        // 18. PAIEMENTS & PORTEFEUILLES
        // ─────────────────────────────────────────────────────────────────────
        Schema::create('payment_methods_config', function (Blueprint $table) {
            $table->id();
            $table->string('name');                          // MVola, Stripe, PayPal…
            $table->string('code')->unique();
            $table->string('logo')->nullable();
            $table->text('description')->nullable();
            $table->json('countries')->nullable();
            $table->json('currencies')->nullable();
            $table->json('config')->nullable();              // Clés API (chiffrées)
            $table->decimal('fee_percentage', 5, 3)->default(0.000);
            $table->decimal('fee_fixed', 10, 2)->default(0.00);
            $table->decimal('min_amount', 12, 2)->nullable();
            $table->decimal('max_amount', 12, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();           // TXN-2024-XXXXX
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('payment_method_id')
                  ->nullable()->constrained('payment_methods_config')->nullOnDelete();
            $table->enum('type', [
                'payment', 'refund', 'payout',
                'commission', 'wallet_topup', 'wallet_withdrawal',
            ]);
            $table->enum('status', [
                'pending', 'processing', 'completed', 'failed', 'cancelled', 'disputed',
            ])->default('pending');
            $table->decimal('amount', 12, 2);
            $table->decimal('fee_amount', 12, 2)->default(0.00);
            $table->decimal('net_amount', 12, 2);
            $table->string('currency', 10)->default('MGA');
            $table->string('gateway_reference')->nullable();
            $table->string('gateway_response')->nullable();
            $table->json('metadata')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->index(['order_id', 'type']);
            $table->index(['user_id', 'type']);
            $table->index('status');
        });

        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->morphs('owner');                         // user, supplier, transporter
            $table->string('currency', 10)->default('MGA');
            $table->decimal('balance', 14, 2)->default(0.00);
            $table->decimal('pending_balance', 14, 2)->default(0.00);
            $table->decimal('reserved_balance', 14, 2)->default(0.00);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['owner_type', 'owner_id', 'currency']);
        });

        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('type', [
                'credit', 'debit', 'pending_credit', 'pending_debit', 'reserve', 'release',
            ]);
            $table->decimal('amount', 14, 2);
            $table->decimal('balance_before', 14, 2);
            $table->decimal('balance_after', 14, 2);
            $table->string('description')->nullable();
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->timestamps();
        });

        Schema::create('commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->decimal('order_item_total', 12, 2);
            $table->decimal('commission_rate', 5, 2);
            $table->decimal('commission_amount', 12, 2);
            $table->decimal('supplier_revenue', 12, 2);
            $table->enum('status', ['pending', 'confirmed', 'paid', 'refunded'])->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('payout_id')
                  ->nullable()->constrained('supplier_payouts')->nullOnDelete();
            $table->timestamps();
        });

        // ─────────────────────────────────────────────────────────────────────
        // 19. EXPÉDITIONS
        // ─────────────────────────────────────────────────────────────────────
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();           // SHP-2024-XXXXX
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->foreignId('transporter_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('shipping_rate_id')
                  ->nullable()->constrained('shipping_rates')->nullOnDelete();
            // ── Origine ─────────────────────────────────────────────────
            $table->string('origin_name');
            $table->string('origin_address_line_1');
            $table->string('origin_address_line_2')->nullable();
            $table->string('origin_city');
            $table->string('origin_country', 3);
            $table->string('origin_postal_code')->nullable();
            $table->string('origin_phone')->nullable();
            $table->decimal('origin_latitude', 10, 7)->nullable();
            $table->decimal('origin_longitude', 10, 7)->nullable();
            // ── Destination ──────────────────────────────────────────────
            $table->string('destination_name');
            $table->string('destination_address_line_1');
            $table->string('destination_address_line_2')->nullable();
            $table->string('destination_city');
            $table->string('destination_country', 3);
            $table->string('destination_postal_code')->nullable();
            $table->string('destination_phone')->nullable();
            $table->decimal('destination_latitude', 10, 7)->nullable();
            $table->decimal('destination_longitude', 10, 7)->nullable();
            // ── Colis ────────────────────────────────────────────────────
            $table->decimal('weight_kg', 8, 3)->nullable();
            $table->decimal('length_cm', 8, 2)->nullable();
            $table->decimal('width_cm', 8, 2)->nullable();
            $table->decimal('height_cm', 8, 2)->nullable();
            $table->decimal('declared_value', 12, 2)->nullable();
            $table->boolean('is_fragile')->default(false);
            $table->boolean('requires_signature')->default(false);
            $table->string('special_instructions')->nullable();
            // ── Suivi ────────────────────────────────────────────────────
            $table->string('tracking_number')->nullable()->unique();
            $table->string('carrier_tracking_url')->nullable();
            $table->enum('status', [
                'pending', 'assigned', 'pickup_scheduled', 'picked_up',
                'in_transit', 'out_for_delivery', 'delivered',
                'delivery_failed', 'returned', 'lost', 'damaged',
            ])->default('pending');
            // ── Financier ────────────────────────────────────────────────
            $table->decimal('shipping_cost', 12, 2)->default(0.00);
            $table->decimal('transporter_earning', 12, 2)->default(0.00);
            $table->decimal('platform_shipping_fee', 12, 2)->default(0.00);
            $table->timestamp('estimated_pickup_at')->nullable();
            $table->timestamp('picked_up_at')->nullable();
            $table->timestamp('estimated_delivery_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->index(['order_id', 'status']);
            $table->index(['transporter_id', 'status']);
        });

        Schema::create('shipment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_item_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity');
            $table->timestamps();
        });

        Schema::create('shipment_trackings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained()->cascadeOnDelete();
            $table->string('status');
            $table->string('location')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->text('description');
            $table->string('agent_name')->nullable();
            $table->string('agent_phone')->nullable();
            $table->json('proof_images')->nullable();
            $table->string('recipient_name')->nullable();
            $table->string('recipient_signature')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();
        });

        Schema::create('shipment_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('transporter_id')->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('speed_rating');
            $table->unsignedTinyInteger('care_rating');
            $table->unsignedTinyInteger('communication_rating');
            $table->decimal('overall_rating', 3, 2);
            $table->text('comment')->nullable();
            $table->timestamps();
        });

        // ─────────────────────────────────────────────────────────────────────
        // 20. RETOURS
        // ─────────────────────────────────────────────────────────────────────
        Schema::create('return_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->unique()->nullable()->constrained()->nullOnDelete();
            $table->boolean('returns_accepted')->default(true);
            $table->unsignedInteger('return_window_days')->default(30);
            $table->boolean('free_return_shipping')->default(false);
            $table->enum('return_shipping_paid_by', ['customer', 'supplier', 'platform'])
                  ->default('customer');
            $table->json('accepted_reasons')->nullable();
            $table->json('non_returnable_categories')->nullable();
            $table->boolean('exchange_allowed')->default(true);
            $table->boolean('store_credit_allowed')->default(true);
            $table->boolean('original_payment_refund_allowed')->default(true);
            $table->unsignedInteger('restocking_fee_percentage')->default(0);
            $table->text('instructions')->nullable();
            $table->boolean('requires_original_packaging')->default(false);
            $table->boolean('requires_all_accessories')->default(false);
            $table->text('excluded_conditions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('returns', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->foreignId('customer_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->foreignId('return_policy_id')
                  ->nullable()->constrained('return_policies')->nullOnDelete();
            $table->enum('status', [
                'draft', 'requested', 'under_review', 'additional_info',
                'approved', 'rejected', 'label_generated', 'shipped_back',
                'in_transit', 'received', 'inspection', 'refund_processing',
                'exchange_processing', 'store_credit_issued', 'completed',
                'cancelled', 'expired',
            ])->default('requested');
            $table->enum('return_type', ['refund', 'exchange', 'store_credit', 'repair'])
                  ->default('refund');
            $table->enum('reason', [
                'defective', 'wrong_item', 'not_as_described',
                'damaged_in_transit', 'missing_parts', 'size_issue',
                'changed_mind', 'arrived_late', 'duplicate_order',
                'quality_issue', 'other',
            ]);
            $table->text('customer_description');
            $table->json('customer_images')->nullable();
            $table->json('customer_videos')->nullable();
            $table->boolean('is_partial_return')->default(false);
            $table->decimal('requested_refund_amount', 12, 2)->nullable();
            $table->decimal('approved_refund_amount', 12, 2)->nullable();
            $table->decimal('shipping_refund_amount', 12, 2)->default(0.00);
            $table->decimal('restocking_fee', 12, 2)->default(0.00);
            $table->decimal('final_refund_amount', 12, 2)->nullable();
            $table->enum('refund_method', [
                'original_payment', 'wallet', 'store_credit', 'bank_transfer',
            ])->nullable();
            $table->foreignId('exchange_product_id')
                  ->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('exchange_variant_id')
                  ->nullable()->constrained('product_variants')->nullOnDelete();
            $table->foreignId('exchange_order_id')
                  ->nullable()->constrained('orders')->nullOnDelete();
            $table->text('supplier_notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('additional_info_request')->nullable();
            $table->json('inspection_notes')->nullable();
            $table->enum('inspection_result', [
                'as_described', 'not_as_described', 'not_returned',
            ])->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('label_generated_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip_address')->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->index(['order_id', 'status']);
            $table->index(['customer_id', 'status']);
            $table->index(['supplier_id', 'status']);
        });

        Schema::create('return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('return_id')->constrained('returns')->cascadeOnDelete();
            $table->foreignId('order_item_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_variant_id')
                  ->nullable()->constrained()->nullOnDelete();
            $table->integer('quantity_ordered');
            $table->integer('quantity_returned');
            $table->enum('condition_on_return', [
                'unopened', 'like_new', 'used_good', 'used_fair',
                'damaged', 'defective', 'incomplete',
            ])->nullable();
            $table->enum('condition_on_receipt', [
                'unopened', 'like_new', 'used_good', 'used_fair',
                'damaged', 'defective', 'incomplete', 'not_received',
            ])->nullable();
            $table->decimal('unit_price_at_purchase', 12, 2);
            $table->decimal('refund_amount', 12, 2)->nullable();
            $table->text('item_notes')->nullable();
            $table->json('item_images')->nullable();
            $table->boolean('is_approved')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('return_shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('return_id')->unique()->constrained('returns')->cascadeOnDelete();
            $table->foreignId('transporter_id')->nullable()->constrained()->nullOnDelete();
            $table->string('sender_name');
            $table->string('sender_address_line_1');
            $table->string('sender_address_line_2')->nullable();
            $table->string('sender_city');
            $table->string('sender_country', 3);
            $table->string('sender_postal_code')->nullable();
            $table->string('sender_phone')->nullable();
            $table->string('recipient_name');
            $table->string('recipient_address_line_1');
            $table->string('recipient_address_line_2')->nullable();
            $table->string('recipient_city');
            $table->string('recipient_country', 3);
            $table->string('recipient_postal_code')->nullable();
            $table->string('recipient_phone')->nullable();
            $table->string('tracking_number')->nullable()->unique();
            $table->string('carrier_name')->nullable();
            $table->string('carrier_tracking_url')->nullable();
            $table->string('return_label_path')->nullable();
            $table->enum('status', [
                'label_created', 'dropped_off', 'in_transit',
                'out_for_delivery', 'delivered', 'delivery_failed',
                'returned_to_sender', 'lost',
            ])->default('label_created');
            $table->decimal('shipping_cost', 10, 2)->default(0.00);
            $table->enum('cost_paid_by', ['customer', 'supplier', 'platform'])->default('customer');
            $table->decimal('weight_kg', 8, 3)->nullable();
            $table->string('package_dimensions')->nullable();
            $table->timestamp('label_generated_at')->nullable();
            $table->timestamp('dropped_off_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
        });

        Schema::create('return_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('return_id')->constrained('returns')->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->text('notes')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('actor_type', ['customer', 'supplier', 'admin', 'system'])->default('system');
            $table->string('ip_address')->nullable();
            $table->timestamps();
            $table->index(['return_id', 'created_at']);
        });

        Schema::create('return_refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('return_id')->constrained('returns')->cascadeOnDelete();
            $table->foreignId('transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 10)->default('MGA');
            $table->enum('method', [
                'original_payment', 'wallet', 'store_credit', 'bank_transfer', 'cash',
            ]);
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'cancelled'])
                  ->default('pending');
            $table->string('gateway_reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });

        // ─────────────────────────────────────────────────────────────────────
        // 21. LITIGES
        // ─────────────────────────────────────────────────────────────────────
        Schema::create('disputes', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->foreignId('order_item_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('return_id')
                  ->nullable()->constrained('returns')->nullOnDelete();
            $table->foreignId('opened_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('against_supplier_id')
                  ->nullable()->constrained('suppliers')->nullOnDelete();
            $table->foreignId('against_transporter_id')
                  ->nullable()->constrained('transporters')->nullOnDelete();
            $table->enum('type', [
                'item_not_received', 'item_not_as_described', 'defective_item',
                'wrong_item_received', 'missing_items', 'damaged_in_transit',
                'late_delivery', 'return_refused', 'refund_not_received',
                'counterfeit_product', 'payment_issue', 'fraud',
                'seller_unresponsive', 'other',
            ]);
            $table->enum('status', [
                'open', 'awaiting_seller', 'awaiting_buyer', 'awaiting_transporter',
                'under_review', 'mediation', 'escalated',
                'resolved_buyer', 'resolved_seller', 'resolved_split',
                'closed_no_action', 'cancelled',
            ])->default('open');
            $table->enum('priority', ['low', 'normal', 'high', 'critical'])->default('normal');
            $table->text('description');
            $table->decimal('claimed_amount', 12, 2)->nullable();
            $table->decimal('resolved_amount', 12, 2)->nullable();
            $table->unsignedInteger('buyer_split_percentage')->nullable();
            $table->boolean('auto_resolved')->default(false);
            $table->timestamp('seller_response_deadline')->nullable();
            $table->timestamp('buyer_response_deadline')->nullable();
            $table->timestamp('auto_resolve_at')->nullable();
            $table->timestamp('seller_responded_at')->nullable();
            $table->timestamp('escalated_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('resolution_summary')->nullable();
            $table->string('ip_address')->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->index(['order_id', 'status']);
            $table->index(['opened_by', 'status']);
            $table->index(['status', 'priority']);
        });

        Schema::create('dispute_evidences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dispute_id')->constrained()->cascadeOnDelete();
            $table->foreignId('submitted_by')->constrained('users')->restrictOnDelete();
            $table->enum('submitted_by_role', ['customer', 'supplier', 'transporter', 'admin']);
            $table->enum('evidence_type', [
                'photo', 'video', 'screenshot', 'document',
                'tracking_info', 'chat_export', 'other',
            ]);
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('file_path');
            $table->string('original_filename');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size_bytes')->nullable();
            $table->boolean('is_accepted')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['dispute_id', 'submitted_by_role']);
        });

        Schema::create('dispute_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dispute_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->restrictOnDelete();
            $table->enum('sender_role', ['customer', 'supplier', 'transporter', 'admin']);
            $table->text('message');
            $table->json('attachments')->nullable();
            $table->boolean('is_internal')->default(false);
            $table->boolean('is_system_message')->default(false);
            $table->boolean('is_important')->default(false);
            $table->timestamp('read_by_customer_at')->nullable();
            $table->timestamp('read_by_seller_at')->nullable();
            $table->timestamp('read_by_admin_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->index(['dispute_id', 'created_at']);
        });

        Schema::create('dispute_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dispute_id')->constrained()->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->text('notes')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('actor_type', ['customer', 'supplier', 'transporter', 'admin', 'system'])
                  ->default('system');
            $table->timestamps();
            $table->index(['dispute_id', 'created_at']);
        });

        Schema::create('dispute_escalations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dispute_id')->constrained()->cascadeOnDelete();
            $table->foreignId('escalated_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('reason', [
                'no_seller_response', 'no_agreement_reached', 'suspected_fraud',
                'policy_violation', 'high_amount', 'repeat_offender',
                'customer_request', 'other',
            ]);
            $table->text('notes');
            $table->enum('level', ['tier1', 'tier2', 'management'])->default('tier1');
            $table->enum('status', ['pending', 'in_progress', 'resolved', 'closed'])->default('pending');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('dispute_resolutions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dispute_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('resolved_by')->constrained('users')->restrictOnDelete();
            $table->enum('outcome', [
                'full_refund_buyer', 'partial_refund_buyer', 'no_refund',
                'exchange', 'store_credit', 'split_refund',
                'seller_penalized', 'transporter_penalized',
                'both_parties_compensated', 'no_action_required',
            ]);
            $table->decimal('buyer_refund_amount', 12, 2)->default(0.00);
            $table->decimal('seller_deduction_amount', 12, 2)->default(0.00);
            $table->decimal('platform_absorbed_amount', 12, 2)->default(0.00);
            $table->enum('refund_method', [
                'original_payment', 'wallet', 'store_credit', 'bank_transfer',
            ])->nullable();
            $table->text('resolution_summary');
            $table->text('seller_note')->nullable();
            $table->text('buyer_note')->nullable();
            $table->boolean('seller_accepted')->nullable();
            $table->boolean('buyer_accepted')->nullable();
            $table->timestamp('buyer_accepted_at')->nullable();
            $table->timestamp('seller_accepted_at')->nullable();
            $table->timestamp('appeal_deadline')->nullable();
            $table->boolean('was_appealed')->default(false);
            $table->timestamps();
        });

        // ─────────────────────────────────────────────────────────────────────
        // 22. AVIS & ÉVALUATIONS
        // ─────────────────────────────────────────────────────────────────────
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_item_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('customer_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->unsignedTinyInteger('quality_rating')->nullable();
            $table->unsignedTinyInteger('value_for_money_rating')->nullable();
            $table->unsignedTinyInteger('description_match_rating')->nullable();
            $table->unsignedTinyInteger('packaging_rating')->nullable();
            $table->string('title', 200)->nullable();
            $table->text('comment')->nullable();
            $table->json('pros')->nullable();
            $table->json('cons')->nullable();
            $table->boolean('would_recommend')->nullable();
            $table->string('usage_duration')->nullable();
            $table->json('size_feedback')->nullable();
            $table->boolean('is_verified_purchase')->default(true);
            $table->foreignId('product_variant_id')
                  ->nullable()->constrained()->nullOnDelete();
            $table->string('variant_label')->nullable();
            $table->text('supplier_reply')->nullable();
            $table->timestamp('supplier_replied_at')->nullable();
            $table->boolean('supplier_reply_is_visible')->default(true);
            $table->enum('status', [
                'pending', 'approved', 'rejected', 'flagged', 'hidden', 'spam',
            ])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_incentivized')->default(false);
            $table->unsignedInteger('helpful_count')->default(0);
            $table->unsignedInteger('not_helpful_count')->default(0);
            $table->unsignedInteger('reports_count')->default(0);
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
            $table->index(['product_id', 'status', 'rating']);
            $table->index(['supplier_id', 'status']);
            $table->index(['customer_id', 'status']);
        });

        Schema::create('review_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_id')->constrained()->cascadeOnDelete();
            $table->enum('media_type', ['image', 'video']);
            $table->string('file_path');
            $table->string('thumbnail_path')->nullable();
            $table->string('original_filename')->nullable();
            $table->unsignedBigInteger('file_size_bytes')->nullable();
            $table->string('mime_type')->nullable();
            $table->integer('duration_seconds')->nullable();
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            $table->string('alt_text')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_approved')->default(true);
            $table->timestamps();
        });

        Schema::create('review_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_helpful');
            $table->timestamps();
            $table->unique(['review_id', 'user_id']);
        });

        Schema::create('review_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reported_by')->constrained('users')->restrictOnDelete();
            $table->enum('reason', [
                'spam', 'fake_review', 'inappropriate', 'off_topic',
                'personal_info', 'competitor', 'incentivized', 'other',
            ]);
            $table->text('description')->nullable();
            $table->enum('status', ['pending', 'under_review', 'accepted', 'rejected'])
                  ->default('pending');
            $table->text('admin_notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['review_id', 'reported_by']);
        });

        Schema::create('review_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('order_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->enum('channel', ['email', 'sms', 'push', 'in_app'])->default('email');
            $table->enum('status', [
                'pending', 'sent', 'clicked', 'review_submitted', 'unsubscribed', 'failed',
            ])->default('pending');
            $table->unsignedTinyInteger('attempt_number')->default(1);
            $table->timestamp('scheduled_at');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('clicked_at')->nullable();
            $table->timestamps();
            $table->index(['customer_id', 'status']);
            $table->index('scheduled_at');
        });

        Schema::create('supplier_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('order_id')->unique()->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('overall_rating');
            $table->unsignedTinyInteger('communication_rating')->nullable();
            $table->unsignedTinyInteger('shipping_speed_rating')->nullable();
            $table->unsignedTinyInteger('product_accuracy_rating')->nullable();
            $table->unsignedTinyInteger('packaging_quality_rating')->nullable();
            $table->unsignedTinyInteger('customer_service_rating')->nullable();
            $table->text('comment')->nullable();
            $table->boolean('would_buy_again')->nullable();
            $table->text('supplier_reply')->nullable();
            $table->timestamp('supplier_replied_at')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'flagged'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->boolean('is_verified')->default(true);
            $table->timestamp('approved_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->index(['supplier_id', 'status']);
        });

        Schema::create('transporter_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transporter_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('shipment_id')->unique()->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('overall_rating');
            $table->unsignedTinyInteger('speed_rating')->nullable();
            $table->unsignedTinyInteger('care_rating')->nullable();
            $table->unsignedTinyInteger('communication_rating')->nullable();
            $table->unsignedTinyInteger('professionalism_rating')->nullable();
            $table->unsignedTinyInteger('accuracy_rating')->nullable();
            $table->text('comment')->nullable();
            $table->boolean('package_arrived_intact')->nullable();
            $table->boolean('delivered_on_time')->nullable();
            $table->boolean('would_use_again')->nullable();
            $table->text('transporter_reply')->nullable();
            $table->timestamp('transporter_replied_at')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'flagged'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->index(['transporter_id', 'status']);
        });

        Schema::create('product_qa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asked_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->text('question');
            $table->boolean('is_anonymous')->default(false);
            $table->enum('status', ['pending', 'answered', 'closed', 'rejected'])->default('pending');
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('views_count')->default(0);
            $table->unsignedInteger('answers_count')->default(0);
            $table->timestamp('answered_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->index(['product_id', 'status']);
        });

        Schema::create('product_qa_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_qa_id')->constrained()->cascadeOnDelete();
            $table->foreignId('answered_by')->constrained('users')->restrictOnDelete();
            $table->enum('answerer_type', ['supplier', 'customer', 'admin']);
            $table->text('answer');
            $table->boolean('is_anonymous')->default(false);
            $table->boolean('is_accepted')->default(false);
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->unsignedInteger('helpful_count')->default(0);
            $table->unsignedInteger('not_helpful_count')->default(0);
            $table->timestamp('approved_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->index(['product_qa_id', 'is_accepted']);
        });

        Schema::create('product_qa_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_qa_answer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_helpful');
            $table->timestamps();
            $table->unique(['product_qa_answer_id', 'user_id']);
        });

        Schema::create('review_summary_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('average_rating', 4, 2)->default(0.00);
            $table->unsignedInteger('total_reviews')->default(0);
            $table->unsignedInteger('verified_reviews')->default(0);
            $table->unsignedInteger('reviews_with_media')->default(0);
            $table->unsignedInteger('count_1_star')->default(0);
            $table->unsignedInteger('count_2_stars')->default(0);
            $table->unsignedInteger('count_3_stars')->default(0);
            $table->unsignedInteger('count_4_stars')->default(0);
            $table->unsignedInteger('count_5_stars')->default(0);
            $table->decimal('avg_quality', 4, 2)->default(0.00);
            $table->decimal('avg_value_for_money', 4, 2)->default(0.00);
            $table->decimal('avg_description_match', 4, 2)->default(0.00);
            $table->decimal('avg_packaging', 4, 2)->default(0.00);
            $table->unsignedInteger('recommend_count')->default(0);
            $table->json('top_pros')->nullable();
            $table->json('top_cons')->nullable();
            $table->json('size_feedback_summary')->nullable();
            $table->timestamp('last_calculated_at')->nullable();
            $table->timestamps();
        });

        // ─────────────────────────────────────────────────────────────────────
        // 23. PROFILS CLIENTS, FIDÉLITÉ & WISHLISTS
        // ─────────────────────────────────────────────────────────────────────
        Schema::create('customer_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('total_spent', 14, 2)->default(0.00);
            $table->unsignedInteger('total_orders')->default(0);
            $table->unsignedInteger('cancelled_orders')->default(0);
            $table->unsignedInteger('returned_orders')->default(0);
            $table->unsignedInteger('loyalty_points')->default(0);
            $table->enum('loyalty_tier', ['bronze', 'silver', 'gold', 'platinum'])
                  ->default('bronze');
            $table->boolean('receives_newsletter')->default(true);
            $table->boolean('receives_sms')->default(true);
            $table->boolean('receives_push')->default(true);
            $table->json('preferred_categories')->nullable();
            $table->json('preferences')->nullable();
            $table->timestamps();
        });

        Schema::create('loyalty_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['earned', 'spent', 'expired', 'adjusted']);
            $table->integer('points');
            $table->integer('balance_before');
            $table->integer('balance_after');
            $table->string('reason')->nullable();
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('wishlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name')->default('Ma liste de souhaits');
            $table->boolean('is_public')->default(false);
            $table->string('share_token')->unique()->nullable();
            $table->timestamps();
        });

        Schema::create('wishlist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wishlist_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')
                  ->nullable()->constrained()->nullOnDelete();
            $table->decimal('price_at_added', 12, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['wishlist_id', 'product_id', 'product_variant_id'], 'wishlist_product_unique');
        });

        Schema::create('product_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('session_id')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('referrer')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
            $table->index(['product_id', 'created_at']);
        });

        Schema::create('recently_viewed', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->timestamp('viewed_at');
            $table->unique(['user_id', 'product_id']);
            $table->index(['user_id', 'viewed_at']);
        });

        Schema::create('product_comparisons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('session_id')->nullable();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        // ─────────────────────────────────────────────────────────────────────
        // 24. NOTIFICATIONS & LOGS
        //     ⚠️  La table `notifications` est déjà créée par Laravel.
        //         On crée uniquement push_subscriptions, email_logs, sms_logs.
        // ─────────────────────────────────────────────────────────────────────
        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('endpoint')->unique();
            $table->text('public_key')->nullable();
            $table->text('auth_token')->nullable();
            $table->string('platform')->nullable();          // web, android, ios
            $table->string('device_name')->nullable();
            $table->timestamps();
        });

        Schema::create('email_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('to_email');
            $table->string('subject');
            $table->string('template')->nullable();
            $table->enum('status', ['pending', 'sent', 'failed', 'bounced', 'opened', 'clicked'])
                  ->default('pending');
            $table->string('message_id')->nullable();
            $table->text('error')->nullable();
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamps();
        });

        Schema::create('sms_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('to_phone');
            $table->text('message');
            $table->string('provider')->nullable();          // Twilio, Vonage…
            $table->enum('status', ['pending', 'sent', 'delivered', 'failed'])->default('pending');
            $table->string('provider_message_id')->nullable();
            $table->text('error')->nullable();
            $table->decimal('cost', 8, 4)->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });

        // ─────────────────────────────────────────────────────────────────────
        // 25. BANNERS & CMS
        // ─────────────────────────────────────────────────────────────────────
        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('image_desktop');
            $table->string('image_mobile')->nullable();
            $table->string('url')->nullable();
            $table->enum('target', ['_self', '_blank'])->default('_self');
            $table->enum('position', [
                'hero', 'homepage_middle', 'homepage_bottom',
                'category_top', 'sidebar', 'popup', 'header_bar',
            ])->default('hero');
            $table->string('background_color')->nullable();
            $table->string('text_color')->nullable();
            $table->string('button_text')->nullable();
            $table->string('button_url')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedInteger('click_count')->default(0);
            $table->unsignedInteger('view_count')->default(0);
            $table->timestamps();
        });

        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('content');
            $table->string('meta_title')->nullable();
            $table->string('meta_description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->string('question');
            $table->text('answer');
            $table->string('category')->nullable();
            $table->enum('audience', ['all', 'customers', 'suppliers', 'transporters'])->default('all');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->enum('type', ['info', 'warning', 'success', 'error'])->default('info');
            $table->enum('audience', ['all', 'customers', 'suppliers', 'transporters'])->default('all');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_dismissible')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
        });

        // ─────────────────────────────────────────────────────────────────────
        // 26. PARAMÈTRES, AUDIT & SUPPORT
        // ─────────────────────────────────────────────────────────────────────
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('group')->default('general');
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->enum('type', ['string', 'integer', 'float', 'boolean', 'json', 'text'])
                  ->default('string');
            $table->string('description')->nullable();
            $table->boolean('is_public')->default(false);
            $table->boolean('is_encrypted')->default(false);
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event');
            $table->nullableMorphs('auditable'); // crée automatiquement l'index (auditable_type, auditable_id)
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('url')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'event']);
            $table->index('created_at');
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->string('log_name')->nullable();
            $table->text('description');
            $table->nullableMorphs('subject');
            $table->nullableMorphs('causer');
            $table->json('properties')->nullable();
            $table->string('batch_uuid', 36)->nullable();
            $table->timestamps();
            $table->index('log_name');
            $table->index('created_at');
        });

        Schema::create('platform_reports', function (Blueprint $table) {
            $table->id();
            $table->date('report_date');
            $table->enum('period', ['daily', 'weekly', 'monthly'])->default('daily');
            $table->decimal('total_revenue', 14, 2)->default(0.00);
            $table->decimal('total_commission', 14, 2)->default(0.00);
            $table->decimal('total_shipping_fees', 14, 2)->default(0.00);
            $table->decimal('total_refunds', 14, 2)->default(0.00);
            $table->unsignedInteger('total_orders')->default(0);
            $table->unsignedInteger('new_customers')->default(0);
            $table->unsignedInteger('new_suppliers')->default(0);
            $table->unsignedInteger('new_transporters')->default(0);
            $table->unsignedInteger('total_products_sold')->default(0);
            $table->json('breakdown')->nullable();
            $table->timestamps();
            $table->unique(['report_date', 'period']);
        });

        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('subject');
            $table->enum('category', [
                'order', 'payment', 'delivery', 'product', 'account', 'technical', 'other',
            ])->default('other');
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->enum('status', ['open', 'pending', 'in_progress', 'resolved', 'closed'])
                  ->default('open');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->integer('satisfaction_rating')->nullable();
            $table->timestamps();
        });

        Schema::create('support_ticket_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->restrictOnDelete();
            $table->text('message');
            $table->json('attachments')->nullable();
            $table->boolean('is_internal')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('tax_rates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('country_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('state_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('rate', 5, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('name');
            $table->string('symbol', 10);
            $table->decimal('exchange_rate', 12, 6)->default(1.000000);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamp('rate_updated_at')->nullable();
            $table->timestamps();
        });

        // ─────────────────────────────────────────────────────────────────────
        // 27. DIVERS
        //     ⚠️  cache, cache_locks, jobs, failed_jobs, job_batches
        //         sont déjà créées par les migrations Laravel par défaut.
        //         On crée uniquement les tables métier ici.
        // ─────────────────────────────────────────────────────────────────────
        Schema::create('search_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('session_id')->nullable();
            $table->string('query');
            $table->integer('results_count')->default(0);
            $table->json('filters_applied')->nullable();
            $table->boolean('resulted_in_purchase')->default(false);
            $table->string('ip_address')->nullable();
            $table->timestamps();
            $table->index('query');
            $table->index('created_at');
        });

        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('referred_id')->unique()->constrained('users')->restrictOnDelete();
            $table->enum('status', ['pending', 'rewarded', 'expired', 'cancelled'])->default('pending');
            $table->decimal('reward_amount', 10, 2)->nullable();
            $table->string('reward_type')->nullable();        // cash, points, coupon
            $table->timestamp('rewarded_at')->nullable();
            $table->timestamps();
        });

        Schema::create('store_credits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->decimal('remaining', 12, 2);
            $table->string('reason')->nullable();
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_used')->default(false);
            $table->timestamps();
        });

        Schema::create('newsletters', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_subscribed')->default(true);
            $table->string('token')->unique()->nullable();
            $table->timestamp('subscribed_at');
            $table->timestamp('unsubscribed_at')->nullable();
            $table->string('source')->nullable();            // footer, popup, checkout…
            $table->timestamps();
        });

        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->morphs('mediable');
            $table->string('collection_name')->default('default');
            $table->string('disk')->default('public');
            $table->string('directory')->nullable();
            $table->string('filename');
            $table->string('original_filename');
            $table->string('mime_type');
            $table->string('extension', 10);
            $table->unsignedBigInteger('size_bytes');
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            $table->json('custom_properties')->nullable();
            $table->json('generated_conversions')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    // =========================================================================
    //  DOWN  —  suppression dans l'ordre inverse des FK
    // =========================================================================
    public function down(): void
    {
        // Divers
        Schema::dropIfExists('media');
        Schema::dropIfExists('newsletters');
        Schema::dropIfExists('store_credits');
        Schema::dropIfExists('referrals');
        Schema::dropIfExists('search_logs');
        // Paramètres / audit / support
        Schema::dropIfExists('currencies');
        Schema::dropIfExists('tax_rates');
        Schema::dropIfExists('support_ticket_messages');
        Schema::dropIfExists('support_tickets');
        Schema::dropIfExists('platform_reports');
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('settings');
        // CMS
        Schema::dropIfExists('announcements');
        Schema::dropIfExists('faqs');
        Schema::dropIfExists('pages');
        Schema::dropIfExists('banners');
        // Notifications
        Schema::dropIfExists('sms_logs');
        Schema::dropIfExists('email_logs');
        Schema::dropIfExists('push_subscriptions');
        // Profils & wishlists
        Schema::dropIfExists('product_comparisons');
        Schema::dropIfExists('recently_viewed');
        Schema::dropIfExists('product_views');
        Schema::dropIfExists('wishlist_items');
        Schema::dropIfExists('wishlists');
        Schema::dropIfExists('loyalty_transactions');
        Schema::dropIfExists('customer_profiles');
        // Avis
        Schema::dropIfExists('review_summary_stats');
        Schema::dropIfExists('product_qa_votes');
        Schema::dropIfExists('product_qa_answers');
        Schema::dropIfExists('product_qa');
        Schema::dropIfExists('transporter_reviews');
        Schema::dropIfExists('supplier_reviews');
        Schema::dropIfExists('review_reminders');
        Schema::dropIfExists('review_reports');
        Schema::dropIfExists('review_votes');
        Schema::dropIfExists('review_media');
        Schema::dropIfExists('reviews');
        // Litiges
        Schema::dropIfExists('dispute_resolutions');
        Schema::dropIfExists('dispute_escalations');
        Schema::dropIfExists('dispute_status_histories');
        Schema::dropIfExists('dispute_messages');
        Schema::dropIfExists('dispute_evidences');
        Schema::dropIfExists('disputes');
        // Retours
        Schema::dropIfExists('return_refunds');
        Schema::dropIfExists('return_status_histories');
        Schema::dropIfExists('return_shipments');
        Schema::dropIfExists('return_items');
        Schema::dropIfExists('returns');
        Schema::dropIfExists('return_policies');
        // Expéditions
        Schema::dropIfExists('shipment_ratings');
        Schema::dropIfExists('shipment_trackings');
        Schema::dropIfExists('shipment_items');
        Schema::dropIfExists('shipments');
        // Paiements
        Schema::dropIfExists('commissions');
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('wallets');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('payment_methods_config');
        // Commandes
        Schema::dropIfExists('order_status_histories');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        // Panier
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('carts');
        // Ventes flash
        Schema::dropIfExists('flash_sale_supplier_requests');
        Schema::dropIfExists('flash_sale_analytics');
        Schema::dropIfExists('flash_sale_cart_reservations');
        Schema::dropIfExists('flash_sale_notifications');
        Schema::dropIfExists('flash_sale_waitlists');
        Schema::dropIfExists('flash_sale_purchase_limits');
        Schema::dropIfExists('flash_sale_products');
        Schema::dropIfExists('flash_sale_slots');
        Schema::dropIfExists('flash_sales');
        // Promotions & coupons
        Schema::dropIfExists('promotion_products');
        Schema::dropIfExists('promotions');
        Schema::dropIfExists('coupon_usages');
        Schema::dropIfExists('coupons');
        // Inventaire & variantes
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('inventories');
        Schema::dropIfExists('product_variant_attribute_values');
        Schema::dropIfExists('product_variants');
        // Produits
        Schema::dropIfExists('product_tag');
        Schema::dropIfExists('product_tags');
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('products');
        // Attributs & marques
        Schema::dropIfExists('category_attributes');
        Schema::dropIfExists('attribute_values');
        Schema::dropIfExists('attributes');
        Schema::dropIfExists('brands');
        // Catégories
        Schema::dropIfExists('categories');
        // Transporteurs
        Schema::dropIfExists('transporter_bank_accounts');
        Schema::dropIfExists('shipping_rates');
        Schema::dropIfExists('transporter_zones');
        Schema::dropIfExists('delivery_zones');
        Schema::dropIfExists('transporter_vehicles');
        Schema::dropIfExists('transporter_documents');
        Schema::dropIfExists('transporters');
        // Fournisseurs
        Schema::dropIfExists('supplier_payouts');
        Schema::dropIfExists('supplier_bank_accounts');
        Schema::dropIfExists('supplier_documents');
        Schema::dropIfExists('suppliers');
        // Géographie
        Schema::dropIfExists('addresses');
        Schema::dropIfExists('cities');
        Schema::dropIfExists('states');
        Schema::dropIfExists('countries');
        // Utilisateurs & rôles
        // Utilisateurs & rôles
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
        // Tables Sanctum + Notifications (créées par ce fichier)
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('personal_access_tokens');
        // Suppression des colonnes métier ajoutées sur users
        // (les colonnes two_factor sont gérées par leur propre migration)
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['referred_by']);
            $table->dropColumn([
                'first_name', 'last_name', 'phone', 'phone_verified_at',
                'avatar', 'gender', 'date_of_birth', 'locale', 'currency',
                'status', 'referral_code', 'referred_by', 'two_factor_enabled',
                'last_login_at', 'last_login_ip', 'deleted_at',
            ]);
        });
    }
};

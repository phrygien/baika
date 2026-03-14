<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * RoleSeeder
 *
 * Crée tous les rôles et permissions de la plateforme.
 *
 * ── Exécution ──────────────────────────────────────────────────────────────
 *
 *   php artisan db:seed --class=RoleSeeder
 */
class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Créer toutes les permissions ──────────────────────────────
        $permissions = $this->permissionsList();
        foreach ($permissions as $data) {
            Permission::firstOrCreate(
                ["name" => $data["name"]],
                [
                    "display_name" => $data["display_name"],
                    "group" => $data["group"],
                ],
            );
        }

        // ── 2. Créer les rôles et leur affecter les permissions ───────────
        foreach ($this->roleDefinitions() as $roleData) {
            $role = Role::firstOrCreate(
                ["name" => $roleData["name"]],
                [
                    "display_name" => $roleData["display_name"],
                    "description" => $roleData["description"],
                ],
            );

            if ($roleData["permissions"] === "*") {
                // Admin = toutes les permissions
                $role->syncPermissions(Permission::pluck("id")->toArray());
            } else {
                $permIds = Permission::whereIn("name", $roleData["permissions"])
                    ->pluck("id")
                    ->toArray();
                $role->syncPermissions($permIds);
            }
        }

        $this->command->info("✅ Rôles et permissions créés.");
    }

    // =========================================================================
    //  LISTE DES PERMISSIONS
    // =========================================================================
    private function permissionsList(): array
    {
        return [
            // ── Dashboard ─────────────────────────────────────────────────
            [
                "name" => "dashboard.view",
                "display_name" => "Voir le dashboard",
                "group" => "dashboard",
            ],

            // ── Utilisateurs ──────────────────────────────────────────────
            [
                "name" => "users.view",
                "display_name" => "Voir les utilisateurs",
                "group" => "users",
            ],
            [
                "name" => "users.create",
                "display_name" => "Créer un utilisateur",
                "group" => "users",
            ],
            [
                "name" => "users.edit",
                "display_name" => "Modifier un utilisateur",
                "group" => "users",
            ],
            [
                "name" => "users.delete",
                "display_name" => "Supprimer un utilisateur",
                "group" => "users",
            ],
            [
                "name" => "users.ban",
                "display_name" => "Bannir un utilisateur",
                "group" => "users",
            ],
            [
                "name" => "users.assign_roles",
                "display_name" => "Gérer les rôles utilisateurs",
                "group" => "users",
            ],

            // ── Fournisseurs ──────────────────────────────────────────────
            [
                "name" => "suppliers.view",
                "display_name" => "Voir les fournisseurs",
                "group" => "suppliers",
            ],
            [
                "name" => "suppliers.approve",
                "display_name" => "Approuver un fournisseur",
                "group" => "suppliers",
            ],
            [
                "name" => "suppliers.suspend",
                "display_name" => "Suspendre un fournisseur",
                "group" => "suppliers",
            ],
            [
                "name" => "suppliers.manage_own",
                "display_name" => "Gérer sa boutique",
                "group" => "suppliers",
            ],

            // ── Transporteurs ─────────────────────────────────────────────
            [
                "name" => "transporters.view",
                "display_name" => "Voir les transporteurs",
                "group" => "transporters",
            ],
            [
                "name" => "transporters.approve",
                "display_name" => "Approuver un transporteur",
                "group" => "transporters",
            ],
            [
                "name" => "transporters.suspend",
                "display_name" => "Suspendre un transporteur",
                "group" => "transporters",
            ],
            [
                "name" => "transporters.manage_own",
                "display_name" => "Gérer son profil transporteur",
                "group" => "transporters",
            ],

            // ── Produits ──────────────────────────────────────────────────
            [
                "name" => "products.view",
                "display_name" => "Voir les produits",
                "group" => "products",
            ],
            [
                "name" => "products.create",
                "display_name" => "Créer un produit",
                "group" => "products",
            ],
            [
                "name" => "products.edit",
                "display_name" => "Modifier un produit",
                "group" => "products",
            ],
            [
                "name" => "products.delete",
                "display_name" => "Supprimer un produit",
                "group" => "products",
            ],
            [
                "name" => "products.approve",
                "display_name" => "Approuver un produit",
                "group" => "products",
            ],
            [
                "name" => "products.manage_own",
                "display_name" => "Gérer ses propres produits",
                "group" => "products",
            ],

            // ── Catégories ────────────────────────────────────────────────
            [
                "name" => "categories.view",
                "display_name" => "Voir les catégories",
                "group" => "categories",
            ],
            [
                "name" => "categories.manage",
                "display_name" => "Gérer les catégories",
                "group" => "categories",
            ],

            // ── Commandes ─────────────────────────────────────────────────
            [
                "name" => "orders.view",
                "display_name" => "Voir toutes les commandes",
                "group" => "orders",
            ],
            [
                "name" => "orders.view_own",
                "display_name" => "Voir ses commandes",
                "group" => "orders",
            ],
            [
                "name" => "orders.manage",
                "display_name" => "Gérer les commandes",
                "group" => "orders",
            ],
            [
                "name" => "orders.cancel",
                "display_name" => "Annuler une commande",
                "group" => "orders",
            ],
            [
                "name" => "orders.refund",
                "display_name" => "Rembourser une commande",
                "group" => "orders",
            ],
            [
                "name" => "orders.manage_own",
                "display_name" => "Gérer ses commandes fournisseur",
                "group" => "orders",
            ],

            // ── Paiements ─────────────────────────────────────────────────
            [
                "name" => "payments.view",
                "display_name" => "Voir les paiements",
                "group" => "payments",
            ],
            [
                "name" => "payments.manage",
                "display_name" => "Gérer les paiements",
                "group" => "payments",
            ],
            [
                "name" => "payments.payout",
                "display_name" => "Effectuer un virement",
                "group" => "payments",
            ],
            [
                "name" => "payments.view_own",
                "display_name" => "Voir ses paiements",
                "group" => "payments",
            ],

            // ── Expéditions ───────────────────────────────────────────────
            [
                "name" => "shipments.view",
                "display_name" => "Voir les expéditions",
                "group" => "shipments",
            ],
            [
                "name" => "shipments.manage",
                "display_name" => "Gérer les expéditions",
                "group" => "shipments",
            ],
            [
                "name" => "shipments.manage_own",
                "display_name" => "Gérer ses livraisons",
                "group" => "shipments",
            ],

            // ── Retours ───────────────────────────────────────────────────
            [
                "name" => "returns.view",
                "display_name" => "Voir les retours",
                "group" => "returns",
            ],
            [
                "name" => "returns.manage",
                "display_name" => "Gérer les retours",
                "group" => "returns",
            ],
            [
                "name" => "returns.create",
                "display_name" => "Créer une demande de retour",
                "group" => "returns",
            ],
            [
                "name" => "returns.approve",
                "display_name" => "Approuver un retour",
                "group" => "returns",
            ],

            // ── Litiges ───────────────────────────────────────────────────
            [
                "name" => "disputes.view",
                "display_name" => "Voir les litiges",
                "group" => "disputes",
            ],
            [
                "name" => "disputes.manage",
                "display_name" => "Gérer les litiges",
                "group" => "disputes",
            ],
            [
                "name" => "disputes.resolve",
                "display_name" => "Résoudre un litige",
                "group" => "disputes",
            ],
            [
                "name" => "disputes.create",
                "display_name" => "Ouvrir un litige",
                "group" => "disputes",
            ],

            // ── Promotions / Flash Sales ───────────────────────────────────
            [
                "name" => "promotions.view",
                "display_name" => "Voir les promotions",
                "group" => "promotions",
            ],
            [
                "name" => "promotions.manage",
                "display_name" => "Gérer les promotions",
                "group" => "promotions",
            ],
            [
                "name" => "promotions.manage_own",
                "display_name" => "Gérer ses promotions",
                "group" => "promotions",
            ],
            [
                "name" => "flash_sales.manage",
                "display_name" => "Gérer les ventes flash",
                "group" => "promotions",
            ],
            [
                "name" => "flash_sales.request",
                "display_name" => "Proposer un produit flash",
                "group" => "promotions",
            ],

            // ── Avis ──────────────────────────────────────────────────────
            [
                "name" => "reviews.view",
                "display_name" => "Voir les avis",
                "group" => "reviews",
            ],
            [
                "name" => "reviews.moderate",
                "display_name" => "Modérer les avis",
                "group" => "reviews",
            ],
            [
                "name" => "reviews.reply_own",
                "display_name" => "Répondre à ses avis",
                "group" => "reviews",
            ],

            // ── Rapports ──────────────────────────────────────────────────
            [
                "name" => "reports.view",
                "display_name" => "Voir les rapports",
                "group" => "reports",
            ],
            [
                "name" => "reports.export",
                "display_name" => "Exporter les rapports",
                "group" => "reports",
            ],
            [
                "name" => "reports.view_own",
                "display_name" => "Voir ses propres rapports",
                "group" => "reports",
            ],

            // ── CMS / Paramètres ──────────────────────────────────────────
            [
                "name" => "cms.manage",
                "display_name" => "Gérer le contenu CMS",
                "group" => "cms",
            ],
            [
                "name" => "settings.manage",
                "display_name" => "Gérer les paramètres",
                "group" => "settings",
            ],
            [
                "name" => "settings.view",
                "display_name" => "Voir les paramètres",
                "group" => "settings",
            ],

            // ── Support ───────────────────────────────────────────────────
            [
                "name" => "support.view",
                "display_name" => "Voir les tickets support",
                "group" => "support",
            ],
            [
                "name" => "support.manage",
                "display_name" => "Gérer les tickets support",
                "group" => "support",
            ],
            [
                "name" => "support.create",
                "display_name" => "Créer un ticket support",
                "group" => "support",
            ],
        ];
    }

    // =========================================================================
    //  DÉFINITION DES RÔLES
    // =========================================================================
    private function roleDefinitions(): array
    {
        return [
            [
                "name" => "admin",
                "display_name" => "Administrateur",
                "description" => "Accès total à toute la plateforme.",
                "permissions" => "*", // toutes les permissions
            ],
            [
                "name" => "moderator",
                "display_name" => "Modérateur",
                "description" =>
                    "Modération du contenu et gestion des litiges.",
                "permissions" => [
                    "dashboard.view",
                    "users.view",
                    "users.ban",
                    "products.view",
                    "products.approve",
                    "orders.view",
                    "orders.cancel",
                    "orders.refund",
                    "reviews.view",
                    "reviews.moderate",
                    "disputes.view",
                    "disputes.manage",
                    "disputes.resolve",
                    "returns.view",
                    "returns.manage",
                    "returns.approve",
                    "support.view",
                    "support.manage",
                    "reports.view",
                ],
            ],
            [
                "name" => "supplier",
                "display_name" => "Fournisseur",
                "description" => "Gestion de sa boutique et de ses produits.",
                "permissions" => [
                    "dashboard.view",
                    "suppliers.manage_own",
                    "products.view",
                    "products.create",
                    "products.edit",
                    "products.delete",
                    "products.manage_own",
                    "orders.view_own",
                    "orders.manage_own",
                    "payments.view_own",
                    "shipments.view",
                    "shipments.manage_own",
                    "returns.view",
                    "returns.approve",
                    "disputes.view",
                    "disputes.manage",
                    "promotions.view",
                    "promotions.manage_own",
                    "flash_sales.request",
                    "reviews.view",
                    "reviews.reply_own",
                    "reports.view_own",
                    "support.create",
                ],
            ],
            [
                "name" => "transporter",
                "display_name" => "Transporteur",
                "description" => "Gestion des livraisons.",
                "permissions" => [
                    "dashboard.view",
                    "transporters.manage_own",
                    "shipments.view",
                    "shipments.manage_own",
                    "orders.view_own",
                    "payments.view_own",
                    "disputes.view",
                    "support.create",
                    "reports.view_own",
                ],
            ],
            [
                "name" => "customer",
                "display_name" => "Client",
                "description" => "Accès client standard.",
                "permissions" => [
                    "orders.view_own",
                    "returns.create",
                    "disputes.create",
                    "support.create",
                    "payments.view_own",
                ],
            ],
        ];
    }
}

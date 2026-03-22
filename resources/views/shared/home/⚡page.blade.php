<?php
use Livewire\Component;
use App\Models\Product;
use App\Models\Category;
use App\Models\FlashSale;

new class extends Component
{
    public array $bestSellers          = [];   // flat — pour les cards promo
    public array $bestSellersByCategory = [];  // groupé par catégorie racine
    public array $newArrivals          = [];
    public array $topRated             = [];
    public array $categories           = [];
    public array $activeSales          = [];
    public array $upcomingSales        = [];

    public function mount(): void
    {
        $with = ['primaryImage:id,product_id,image_path,alt_text,is_primary', 'category:id,name', 'brand:id,name'];
        $sel  = ['id','name','slug','base_price','compare_at_price','currency','average_rating','total_reviews','total_sold','category_id','brand_id'];

        // ── Best sellers flat (pour promos / featured) ───────────────────────
        $this->bestSellers = Product::select($sel)->with($with)
            ->where('status','approved')->where('is_active',true)
            ->orderByDesc('total_sold')->limit(16)->get()
            ->map(fn($p) => $this->mapProduct($p))->toArray();

        // ── Nouvelles arrivées ───────────────────────────────────────────────
        $this->newArrivals = Product::select($sel)->with($with)
            ->where('status','approved')->where('is_active',true)
            ->orderByDesc('created_at')->limit(16)->get()
            ->map(fn($p) => $this->mapProduct($p))->toArray();

        // ── Top rated ────────────────────────────────────────────────────────
        $this->topRated = Product::select($sel)->with($with)
            ->where('status','approved')->where('is_active',true)
            ->whereNotNull('average_rating')->orderByDesc('average_rating')->limit(16)->get()
            ->map(fn($p) => $this->mapProduct($p))->toArray();

        // ── Catégories racines + 4 enfants directs actifs ────────────────────
        $this->categories = Category::where('is_active', true)
            ->roots()
            ->with([
                'children' => fn($q) => $q
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->limit(4)
                    ->select(['id','parent_id','name','slug','image','icon']),
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit(12)
            ->get(['id','name','slug','icon','image'])
            ->map(fn($cat) => [
                'id'       => $cat->id,
                'name'     => $cat->name,
                'slug'     => $cat->slug,
                'icon'     => $cat->icon,
                'image'    => $cat->image,
                'children' => $cat->children->map(fn($c) => [
                    'id'    => $c->id,
                    'name'  => $c->name,
                    'slug'  => $c->slug,
                    'image' => $c->image,
                    'icon'  => $c->icon,
                ])->toArray(),
            ])
            ->toArray();

        // ── Best sellers par catégorie racine (produits vraiment dans la catégorie) ──
        $this->bestSellersByCategory = $this->buildBestSellersByCategory($sel, $with);

        // ── Flash sales actives ───────────────────────────────────────────────
        $this->activeSales = FlashSale::with([
                'products' => fn($q) => $q->active()
                    ->with(['product.primaryImage:id,product_id,image_path,alt_text,is_primary'])
                    ->orderBy('sort_order')
                    ->limit(20),
            ])
            ->active()
            ->orderByDesc('is_featured')
            ->orderBy('ends_at')
            ->limit(5)
            ->get()
            ->map(fn($sale) => $this->mapSale($sale))
            ->toArray();

        // ── Flash sales à venir ───────────────────────────────────────────────
        $this->upcomingSales = FlashSale::with(['products' => fn($q) => $q->limit(4)])
            ->upcoming()
            ->orderBy('starts_at')
            ->limit(3)
            ->get()
            ->map(fn($sale) => $this->mapSale($sale))
            ->toArray();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Pour chaque catégorie racine, on récupère UNIQUEMENT les produits dont
    // category_id est dans {racine} ∪ {tous les descendants}.
    // ─────────────────────────────────────────────────────────────────────────
    protected function buildBestSellersByCategory(array $sel, array $with): array
    {
        // On charge les catégories racines avec TOUS leurs descendants récursifs
        $rootCategories = Category::where('is_active', true)
            ->roots()
            ->with(['allChildren'])   // relation récursive définie dans le modèle
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit(12)
            ->get();

        $result = [];

        foreach ($rootCategories as $rootCat) {
            // Collecte récursive de tous les IDs (racine + enfants + petits-enfants…)
            $allIds = $this->collectCategoryIds($rootCat);

            $products = Product::select($sel)->with($with)
                ->where('status', 'approved')
                ->where('is_active', true)
                ->whereIn('category_id', $allIds)
                ->orderByDesc('total_sold')
                ->limit(16)
                ->get()
                ->map(fn($p) => $this->mapProduct($p))
                ->toArray();

            // N'ajouter le groupe que s'il contient au moins 1 produit
            if (!empty($products)) {
                $result[] = [
                    'category_id'   => $rootCat->id,
                    'category_name' => $rootCat->name,
                    'products'      => $products,
                ];
            }
        }

        return $result;
    }

    // Collecte récursive de tous les IDs d'une catégorie et de ses descendants
    protected function collectCategoryIds($category): array
    {
        $ids = [$category->id];
        foreach ($category->allChildren as $child) {
            $ids = array_merge($ids, $this->collectCategoryIds($child));
        }
        return array_unique($ids);
    }

    protected function mapProduct($p): array
    {
        return [
            'id'               => $p->id,
            'name'             => $p->name,
            'base_price'       => $p->base_price,
            'compare_at_price' => $p->compare_at_price,
            'currency'         => $p->currency ?? 'USD',
            'average_rating'   => $p->average_rating,
            'total_reviews'    => $p->total_reviews,
            'total_sold'       => $p->total_sold ?? 0,
            'image'            => $p->primaryImage?->image_path,
            'category'         => $p->category?->name,
            'brand'            => $p->brand?->name,
            'discount'         => $p->compare_at_price && $p->compare_at_price > $p->base_price
                ? (int)round((1 - $p->base_price / $p->compare_at_price) * 100) : 0,
        ];
    }

    protected function mapSale(FlashSale $sale): array
    {
        $secondsLeft = max(0, (int) now()->diffInSeconds($sale->ends_at, false));
        return [
            'id'             => $sale->id,
            'title'          => $sale->title,
            'slug'           => $sale->slug,
            'badge_text'     => $sale->badge_text,
            'description'    => $sale->description,
            'banner'         => $sale->banner_image_desktop,
            'thumbnail'      => $sale->thumbnail_image,
            'bg_color'       => $sale->background_color ?? '#CC0C39',
            'text_color'     => $sale->text_color ?? '#ffffff',
            'is_featured'    => $sale->is_featured,
            'show_countdown' => $sale->show_countdown,
            'show_stock'     => $sale->show_stock_level,
            'show_sold'      => $sale->show_sold_count,
            'status'         => $sale->status,
            'starts_at'      => $sale->starts_at?->toISOString(),
            'ends_at'        => $sale->ends_at?->toISOString(),
            'seconds_left'   => $secondsLeft,
            'total_products' => $sale->total_products ?? 0,
            'total_orders'   => $sale->total_orders ?? 0,
            'products'       => $sale->products->map(fn($sp) => $this->mapSaleProduct($sp))->toArray(),
        ];
    }

    protected function mapSaleProduct(\App\Models\FlashSaleProduct $sp): array
    {
        $product = $sp->product;
        return [
            'id'               => $sp->id,
            'product_id'       => $sp->product_id,
            'name'             => $product?->name ?? '-',
            'image'            => $product?->primaryImage?->image_path,
            'original_price'   => $sp->original_price,
            'flash_price'      => $sp->flash_price,
            'discount'         => (int) round($sp->discount_percentage),
            'currency'         => $product?->currency ?? 'USD',
            'stock_total'      => $sp->flash_stock_total,
            'stock_sold'       => $sp->flash_stock_sold,
            'stock_remaining'  => $sp->stockRemaining(),
            'is_low_stock'     => $sp->isLowStock(),
            'is_available'     => $sp->isAvailable(),
            'is_featured'      => $sp->is_featured,
            'show_stock'       => $sp->show_stock_level,
            'max_per_order'    => $sp->max_quantity_per_order,
        ];
    }
};
?>

@once
<style>
/* ══════════════════════════════════════════════════════
   LIGHT MODE
══════════════════════════════════════════════════════ */
.amzp *{box-sizing:border-box;margin:0;padding:0}
.amzp{background:#eaeded;font-family:Arial,sans-serif;font-size:13px;color:#0F1111;transition:background .2s,color .2s}
.amzp-w{max-width:1500px;margin:0 auto;padding:0 9px}
.noscr::-webkit-scrollbar{display:none}
.noscr{-ms-overflow-style:none;scrollbar-width:none}

/* HERO */
.amzp-hero{position:relative;background:#222;overflow:hidden;width:100%}
.amzp-hero-track{display:flex;width:100%;transition:transform .45s cubic-bezier(.4,0,.2,1)}
.amzp-hero-slide{min-width:100%;height:350px;position:relative;flex-shrink:0;overflow:hidden}
@media(max-width:640px){.amzp-hero-slide{height:180px}}
.amzp-hero-slide img{width:100%;height:100%;object-fit:cover;display:block}
.amzp-hero-overlay{position:absolute;inset:0;background:linear-gradient(90deg,rgba(0,0,0,.6) 0%,rgba(0,0,0,.15) 55%,transparent 85%)}
.amzp-hero-copy{position:absolute;top:50%;left:6%;transform:translateY(-50%);z-index:2;max-width:420px}
.amzp-hero-tag{font-size:.7rem;font-weight:700;color:#febd69;text-transform:uppercase;letter-spacing:.1em}
.amzp-hero-title{font-size:1.9rem;font-weight:900;color:#fff;line-height:1.1;text-shadow:0 2px 8px rgba(0,0,0,.5);margin-top:4px}
@media(max-width:640px){.amzp-hero-title{font-size:1.1rem}}
.amzp-hero-sub{color:rgba(255,255,255,.85);font-size:.85rem;margin-top:8px;line-height:1.45}
.amzp-hero-cta{display:inline-block;margin-top:14px;background:#febd69;color:#111;font-weight:700;font-size:.82rem;padding:9px 22px;border-radius:3px;text-decoration:none;transition:background .12s}
.amzp-hero-cta:hover{background:#f3a847}
.amzp-hero-arrow{position:absolute;top:0;bottom:0;z-index:10;width:44px;display:flex;align-items:center;justify-content:center;background:rgba(255,255,255,.18);border:none;cursor:pointer;transition:background .15s}
.amzp-hero-arrow:hover{background:rgba(255,255,255,.55)}
.amzp-hero-arrow.prev{left:0}
.amzp-hero-arrow.next{right:0}
.amzp-hero-arrow svg{width:22px;height:22px;color:#fff;filter:drop-shadow(0 1px 3px rgba(0,0,0,.6))}
.amzp-hero-dots{position:absolute;bottom:12px;left:50%;transform:translateX(-50%);display:flex;gap:6px;z-index:5}
.amzp-dot{width:8px;height:8px;border-radius:50%;background:rgba(255,255,255,.4);border:none;cursor:pointer;padding:0;transition:background .2s}
.amzp-dot.active{background:#fff}

/* SECTION CARD */
.amzp-card{background:#fff;margin-bottom:8px;transition:background .2s}
.amzp-card-head{padding:14px 14px 6px;display:flex;align-items:baseline;gap:10px}
.amzp-sec-title{font-size:1.2rem;font-weight:700;color:#0F1111;transition:color .2s}
.amzp-more{font-size:.8rem;color:#007185;text-decoration:none;white-space:nowrap}
.amzp-more:hover{color:#c7511f;text-decoration:underline}

/* PRODUCT STRIP */
.amzp-strip{display:flex;overflow-x:auto;padding:0 38px 14px;border-top:1px solid #f0f0f0;transition:border-color .2s}
.amzp-prod{width:158px;min-width:158px;flex-shrink:0;padding:10px 8px 8px;border-right:1px solid #f0f0f0;display:flex;flex-direction:column;align-items:center;text-align:center;cursor:pointer;text-decoration:none;color:inherit;transition:border-color .2s,background .2s}
.amzp-prod:last-child{border-right:none}
.amzp-prod:hover{background:#fafafa}
.amzp-prod:hover .amzp-prod-img{transform:scale(1.04)}
.amzp-prod-img-wrap{position:relative;width:140px;height:158px;background:#f7f7f7;display:flex;align-items:center;justify-content:center;overflow:hidden;border-radius:2px;transition:background .2s}
.amzp-prod-img{width:100%;height:100%;object-fit:contain;padding:6px;transition:transform .25s}
.amzp-prod-noimg{width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:#f0f2f3}
.amzp-prod-noimg svg{width:40px;height:40px;color:#c8cdd0}
.amzp-rank{position:absolute;top:0;left:0;background:#B12704;color:#fff;font-size:.6rem;font-weight:700;padding:2px 5px;border-radius:0 0 3px 0}
.amzp-new-badge{position:absolute;top:0;right:0;background:#007185;color:#fff;font-size:.58rem;font-weight:700;padding:2px 5px;border-radius:0 0 0 3px}
.amzp-disc-badge{position:absolute;bottom:4px;left:4px;background:#B12704;color:#fff;font-size:.6rem;font-weight:700;padding:2px 5px;border-radius:2px}
.amzp-prod-name{font-size:.72rem;color:#0F1111;line-height:1.35;margin-top:6px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;width:100%;transition:color .2s}
.amzp-stars{color:#c45500;font-size:.65rem;margin-top:3px}
.amzp-price{color:#B12704;font-weight:700;font-size:.88rem;margin-top:3px}
.amzp-compare{color:#565959;font-size:.67rem;text-decoration:line-through;margin-left:3px;transition:color .2s}
.amzp-sold{color:#565959;font-size:.62rem;margin-top:1px;transition:color .2s}

/* CAROUSEL ARROWS */
.amzp-arr{position:absolute;top:0;bottom:0;z-index:5;width:38px;display:flex;align-items:center;justify-content:center;background:rgba(255,255,255,.92);border:none;cursor:pointer;transition:background .12s}
.amzp-arr:hover{background:#fff}
.amzp-arr.l{left:0;box-shadow:4px 0 8px rgba(0,0,0,.06)}
.amzp-arr.r{right:0;box-shadow:-4px 0 8px rgba(0,0,0,.06)}
.amzp-arr svg{width:16px;height:16px;color:#666}

/* 4-COL GRID */
.amzp-grid4{display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:8px}
@media(max-width:900px){.amzp-grid4{grid-template-columns:repeat(2,1fr)}}
@media(max-width:480px){.amzp-grid4{grid-template-columns:1fr}}

/* SUBCAT CARD */
.amzp-sub{background:#fff;padding:13px;transition:background .2s}
.amzp-sub-title{font-size:.98rem;font-weight:700;color:#0F1111;margin-bottom:8px;transition:color .2s}
.amzp-sub-grid{display:grid;grid-template-columns:1fr 1fr;gap:6px}
.amzp-sub-cell{display:flex;flex-direction:column;gap:3px;cursor:pointer}
.amzp-sub-img{aspect-ratio:1;width:100%;border-radius:2px;overflow:hidden;background:#f0f2f3;display:flex;align-items:center;justify-content:center;transition:background .2s}
.amzp-sub-img img{width:100%;height:100%;object-fit:cover;display:block}
.amzp-sub-img svg{width:28px;height:28px;color:#c8cdd0}
.amzp-sub-label{font-size:.7rem;color:#0F1111;line-height:1.2;transition:color .2s}
.amzp-sub-more{font-size:.77rem;color:#007185;text-decoration:none;display:block;margin-top:8px}
.amzp-sub-more:hover{color:#c7511f;text-decoration:underline}

/* PROMO CARD */
.amzp-promo{background:#fff;padding:13px;display:flex;flex-direction:column;gap:8px;transition:background .2s}
.amzp-promo-title{font-size:.98rem;font-weight:700;color:#0F1111;line-height:1.3;transition:color .2s}
.amzp-promo-img{width:100%;aspect-ratio:4/3;border-radius:3px;overflow:hidden;background:#f0f2f3;display:flex;align-items:center;justify-content:center;transition:background .2s}
.amzp-promo-img img{width:100%;height:100%;object-fit:cover;display:block}
.amzp-promo-img svg{width:40px;height:40px;color:#c8cdd0}
.amzp-promo-more{font-size:.77rem;color:#007185;text-decoration:none}
.amzp-promo-more:hover{color:#c7511f;text-decoration:underline}

/* CIRCLE CATEGORIES */
.amzp-circles{display:flex;gap:18px;overflow-x:auto;padding:4px 2px 12px}
.amzp-circle-item{display:flex;flex-direction:column;align-items:center;gap:7px;flex-shrink:0;width:108px;cursor:pointer}
.amzp-circle{width:88px;height:88px;border-radius:50%;overflow:hidden;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:transform .2s;background:#d0d3d4}
.amzp-circle:hover{transform:scale(1.06)}
.amzp-circle img{width:100%;height:100%;object-fit:cover;display:block}
.amzp-circle-noimg{width:100%;height:100%;display:flex;align-items:center;justify-content:center}
.amzp-circle-noimg svg{width:36px;height:36px;color:rgba(255,255,255,.55)}
.amzp-circle-label{font-size:.76rem;font-weight:600;color:#0F1111;text-align:center;line-height:1.3;transition:color .2s}

/* FLASH BADGE & COUNTDOWN */
.amzp-flash{display:inline-flex;align-items:center;background:#CC0C39;color:#fff;font-size:.6rem;font-weight:700;padding:2px 6px;border-radius:2px}
.amzp-cd{background:#0F1111;color:#fff;font-size:.6rem;font-weight:700;padding:2px 5px;border-radius:2px;font-variant-numeric:tabular-nums;letter-spacing:.03em}

/* RECO CTA */
.amzp-reco{background:#fff;margin-bottom:8px;text-align:center;padding:28px 16px;transition:background .2s}
.amzp-reco h2{font-size:1.1rem;font-weight:700;color:#0F1111;margin-bottom:12px;transition:color .2s}
.amzp-reco-btn{display:inline-block;background:linear-gradient(to bottom,#f7dfa5,#f0c14b);border:1px solid #a88734;border-radius:3px;color:#111;font-weight:700;font-size:.84rem;padding:9px 22px;text-decoration:none}
.amzp-reco-btn:hover{background:linear-gradient(to bottom,#f0c14b,#e7a800)}

/* ══════════════════════════════════════════════════════
   DARK MODE
══════════════════════════════════════════════════════ */
.dark .amzp{background:#0f1111;color:#d5d9d9}
.dark .amzp-card{background:#1c1f20}
.dark .amzp-sec-title{color:#e3e6e6}
.dark .amzp-more{color:#6bbfc9}
.dark .amzp-more:hover{color:#febd69}
.dark .amzp-strip{border-top-color:#2d3131}
.dark .amzp-prod{border-right-color:#2d3131}
.dark .amzp-prod:hover{background:#232627}
.dark .amzp-prod-img-wrap{background:#2a2d2e}
.dark .amzp-prod-noimg{background:#2a2d2e}
.dark .amzp-prod-noimg svg{color:#444b4d}
.dark .amzp-prod-name{color:#e3e6e6}
.dark .amzp-stars{color:#ffa41c}
.dark .amzp-price{color:#ff6b6b}
.dark .amzp-compare{color:#888}
.dark .amzp-sold{color:#8d9191}
.dark .amzp-arr{background:rgba(28,31,32,.95)}
.dark .amzp-arr:hover{background:#2d3131}
.dark .amzp-arr svg{color:#ccc}
.dark .amzp-arr.l{box-shadow:4px 0 8px rgba(0,0,0,.3)}
.dark .amzp-arr.r{box-shadow:-4px 0 8px rgba(0,0,0,.3)}
.dark .amzp-sub{background:#1c1f20}
.dark .amzp-sub-title{color:#e3e6e6}
.dark .amzp-sub-img{background:#2a2d2e}
.dark .amzp-sub-img svg{color:#444b4d}
.dark .amzp-sub-label{color:#d5d9d9}
.dark .amzp-sub-more{color:#6bbfc9}
.dark .amzp-sub-more:hover{color:#febd69}
.dark .amzp-promo{background:#1c1f20}
.dark .amzp-promo-title{color:#e3e6e6}
.dark .amzp-promo-img{background:#2a2d2e}
.dark .amzp-promo-img svg{color:#444b4d}
.dark .amzp-promo-more{color:#6bbfc9}
.dark .amzp-promo-more:hover{color:#febd69}
.dark .amzp-circle{background:#2a2d2e}
.dark .amzp-circle-noimg svg{color:rgba(255,255,255,.2)}
.dark .amzp-circle-label{color:#d5d9d9}
.dark .amzp-cd{background:#febd69;color:#111}
.dark .dark-muted{color:#8d9191 !important}
.dark .amzp-card [style*="color:#565959"]{color:#8d9191 !important}
.dark .amzp-card [style*="border-bottom:1px solid #f0f0f0"]{border-bottom-color:#2d3131 !important}
.dark .amzp-reco{background:#1c1f20}
.dark .amzp-reco h2{color:#e3e6e6}
.dark .amzp-prod [style*="color:#0F1111"],.dark .amzp-prod [style*="color: #0F1111"]{color:#e3e6e6 !important}
.dark .amzp-prod [style*="color:#565959"],.dark .amzp-prod [style*="color: #565959"]{color:#8d9191 !important}
.dark .amzp-sub  [style*="color:#0F1111"]{color:#e3e6e6 !important}
.dark .amzp-sub  [style*="color:#007185"]{color:#6bbfc9 !important}
.dark .amzp-promo [style*="color:#0F1111"]{color:#e3e6e6 !important}
.dark .amzp-promo [style*="color:#B12704"]{color:#ff6b6b !important}
</style>
@endonce

@php
$svgImg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">'
        . '<rect x="3" y="3" width="18" height="18" rx="2"/>'
        . '<circle cx="8.5" cy="8.5" r="1.5"/>'
        . '<polyline points="21 15 16 10 5 21"/>'
        . '</svg>';

$svgCircle = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">'
           . '<circle cx="12" cy="12" r="3"/>'
           . '<path d="M12 2v3M12 19v3M4.22 4.22l2.12 2.12M17.66 17.66l2.12 2.12M2 12h3M19 12h3M4.22 19.78l2.12-2.12M17.66 6.34l2.12-2.12"/>'
           . '</svg>';

// Helpers Blade
$bsCat = fn(int $idx) => $bestSellersByCategory[$idx] ?? null;
@endphp

<div class="amzp"
    x-data
    x-init="
        const update = () => $el.classList.toggle('dark', document.documentElement.classList.contains('dark'));
        update();
        new MutationObserver(update).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
    "
>

{{-- ══════════════════════════════════════════════
     1 ▸ HERO CAROUSEL
═══════════════════════════════════════════════ --}}
@php
$slides = [
    ['img'=>'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=1400&q=80','bg'=>'#1a3a5c','tag'=>__('Security'),'title'=>__('Connected Security'),'sub'=>'ring | blink','cta'=>__('Discover')],
    ['img'=>'https://images.unsplash.com/photo-1607082348824-0a96f2a4b9da?w=1400&q=80','bg'=>'#1e3820','tag'=>__('New arrivals'),'title'=>__('New Arrivals Are Here'),'sub'=>__("Check out the latest options while they're still in stock."),'cta'=>__('Shop New Arrivals')],
    ['img'=>'https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?w=1400&q=80','bg'=>'#3a1e1e','tag'=>__('Flash Sales'),'title'=>__('Up to -75% Today'),'sub'=>__('Limited time. Thousands of deals updated daily.'),'cta'=>__('See Deals')],
    ['img'=>'https://images.unsplash.com/photo-1555529669-e69e7aa0ba9a?w=1400&q=80','bg'=>'#1e1e3a','tag'=>__('Best Sellers'),'title'=>__('Best Sellers This Week'),'sub'=>__('Our most loved products, updated daily.'),'cta'=>__('Explore')],
];
$nSlides = count($slides);
@endphp

<div class="amzp-hero"
    x-data="{ cur:0, n:{{ $nSlides }}, t:null,
        go(i){ this.cur=i; this.reset() },
        prev(){ this.cur=(this.cur-1+this.n)%this.n; this.reset() },
        next(){ this.cur=(this.cur+1)%this.n; this.reset() },
        reset(){ clearInterval(this.t); this.t=setInterval(()=>{ this.cur=(this.cur+1)%this.n },6000) }
    }"
    x-init="t=setInterval(()=>{ cur=(cur+1)%n },6000)"
>
    <div class="amzp-hero-track" :style="'transform:translateX(-'+cur*100+'%)'">
        @foreach ($slides as $s)
        <div class="amzp-hero-slide" style="background:{{ $s['bg'] }}">
            <img src="{{ $s['img'] }}" alt="" loading="{{ $loop->first ? 'eager' : 'lazy' }}">
            <div class="amzp-hero-overlay"></div>
            <div class="amzp-hero-copy">
                <p class="amzp-hero-tag">{{ $s['tag'] }}</p>
                <h1 class="amzp-hero-title">{{ $s['title'] }}</h1>
                <p class="amzp-hero-sub">{{ $s['sub'] }}</p>
                <a href="{{ route('all-products') }}" class="amzp-hero-cta" wire:navigate>{{ $s['cta'] }}</a>
            </div>
        </div>
        @endforeach
    </div>
    <button @click="prev()" class="amzp-hero-arrow prev" aria-label="prev">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
    </button>
    <button @click="next()" class="amzp-hero-arrow next" aria-label="next">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
    </button>
    <div class="amzp-hero-dots">
        @foreach ($slides as $si => $_)
            <button @click="go({{ $si }})" class="amzp-dot" :class="{ active: cur === {{ $si }} }"></button>
        @endforeach
    </div>
</div>

{{-- ══════════════════════════════════════════════
     2 ▸ 4 PROMO CARDS
═══════════════════════════════════════════════ --}}
<div class="amzp-w" style="position:relative;z-index:5;margin-top:-70px;padding-bottom:8px">
    <div class="amzp-grid4">

        {{-- Card A: Catégorie [0] + ses enfants --}}
        @php $catA = $categories[0] ?? null; @endphp
        <div class="amzp-sub">
            <p class="amzp-sub-title">{{ $catA['name'] ?? __('Kitchen & Home') }}</p>
            <div class="amzp-sub-grid">
                @forelse (array_slice($catA['children'] ?? [], 0, 4) as $child)
                    <div class="amzp-sub-cell">
                        <div class="amzp-sub-img">
                            @if(!empty($child['image']))<img src="{{ asset($child['image']) }}" alt="{{ $child['name'] }}" loading="lazy">
                            @else{!! $svgImg !!}@endif
                        </div>
                        <span class="amzp-sub-label">{{ $child['name'] }}</span>
                    </div>
                @empty
                    @for ($e = 0; $e < 4; $e++)
                        <div class="amzp-sub-cell"><div class="amzp-sub-img">{!! $svgImg !!}</div><span class="amzp-sub-label">—</span></div>
                    @endfor
                @endforelse
            </div>
            <a href="{{ route('all-products') }}" class="amzp-sub-more" wire:navigate>{{ __('See more') }}</a>
        </div>

        {{-- Card B: Premier flash sale actif --}}
        <div class="amzp-promo">
            <p class="amzp-promo-title" style="color:#CC0C39">{{ !empty($activeSales[0]) ? $activeSales[0]['title'] : __('-25% on your first order') }}</p>
            <div class="amzp-promo-img">
                @if(!empty($activeSales[0]['banner']))<img src="{{ asset($activeSales[0]['banner']) }}" alt="">
                @elseif(!empty($activeSales[0]['products'][0]['image']))<img src="{{ asset($activeSales[0]['products'][0]['image']) }}" alt="" style="object-fit:contain;padding:8px;background:#f5f0ff">
                @else{!! $svgImg !!}@endif
            </div>
            @if(!empty($activeSales[0]['products'][0]))
                @php $fp0 = $activeSales[0]['products'][0]; @endphp
                <p style="font-size:.72rem;color:#B12704;font-weight:700">-{{ $fp0['discount'] }}% — {{ number_format($fp0['flash_price'],2) }} {{ $fp0['currency'] }}</p>
            @endif
            <a href="{{ route('all-products') }}" class="amzp-promo-more" wire:navigate>{{ __('See all flash sales') }}</a>
        </div>

        {{-- Card C: Deuxième flash sale --}}
        <div class="amzp-promo">
            <p class="amzp-promo-title">{{ !empty($activeSales[1]) ? $activeSales[1]['title'] : __('Mini prices: -50% from 2 items') }}</p>
            <div class="amzp-promo-img">
                @if(!empty($activeSales[1]['banner']))<img src="{{ asset($activeSales[1]['banner']) }}" alt="">
                @elseif(!empty($activeSales[1]['products'][0]['image']))<img src="{{ asset($activeSales[1]['products'][0]['image']) }}" alt="" style="object-fit:contain;padding:8px;background:#f0fff4">
                @else{!! $svgImg !!}@endif
            </div>
            <a href="{{ route('all-products') }}" class="amzp-promo-more" wire:navigate>{{ __('Discover deals') }}</a>
        </div>

        {{-- Card D: Nouvelles arrivées --}}
        <div class="amzp-promo">
            <p class="amzp-promo-title">{{ __('Spring cleaning from 1.99') }}</p>
            <div class="amzp-promo-img">
                @if(!empty($newArrivals[0]['image']))<img src="{{ asset($newArrivals[0]['image']) }}" alt="" style="object-fit:contain;padding:8px">
                @else{!! $svgImg !!}@endif
            </div>
            <a href="{{ route('all-products') }}" class="amzp-promo-more" wire:navigate>{{ __('Discover Amazon Haul') }}</a>
        </div>

    </div>
</div>

{{-- ══════════════════════════════════════════════
     3 ▸ TOP RATED STRIP
═══════════════════════════════════════════════ --}}
@if (!empty($topRated))
<div class="amzp-w">
    <div class="amzp-card">
        <div class="amzp-card-head">
            <span class="amzp-sec-title">{{ __('Absolute Comfort') }}</span>
            <a href="{{ route('all-products') }}" class="amzp-more" wire:navigate>{{ __('See more') }}</a>
        </div>
        <div x-data="{el:null}" x-init="el=$refs.c1" style="position:relative">
            <button @click="el.scrollBy({left:-900,behavior:'smooth'})" class="amzp-arr l"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg></button>
            <div x-ref="c1" class="amzp-strip noscr">
                @foreach ($topRated as $p)
                    <a href="#" class="amzp-prod">
                        <div class="amzp-prod-img-wrap">
                            @if($p['image'])<img src="{{ asset($p['image']) }}" class="amzp-prod-img" loading="lazy">
                            @else<div class="amzp-prod-noimg">{!! $svgImg !!}</div>@endif
                        </div>
                        <p class="amzp-prod-name">{{ Str::limit($p['name'],40) }}</p>
                        @if($p['average_rating'])<p class="amzp-stars">{{ str_repeat('★',round($p['average_rating'])) }}{{ str_repeat('☆',5-round($p['average_rating'])) }}</p>@endif
                        <p class="amzp-price">{{ number_format($p['base_price'],2) }} <span style="font-weight:400;font-size:.62rem;color:#565959">{{ $p['currency'] }}</span></p>
                    </a>
                @endforeach
            </div>
            <button @click="el.scrollBy({left:900,behavior:'smooth'})" class="amzp-arr r"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg></button>
        </div>
    </div>
</div>
@endif

{{-- ══════════════════════════════════════════════
     4 ▸ BEST SELLERS — groupe [0] (produits réellement dans cette catégorie)
═══════════════════════════════════════════════ --}}
@if (!empty($bestSellersByCategory[0]['products']))
@php $bsGroup0 = $bestSellersByCategory[0]; @endphp
<div class="amzp-w">
    <div class="amzp-card">
        <div class="amzp-card-head">
            <span class="amzp-sec-title">{{ __('Best Sellers in') }} {{ $bsGroup0['category_name'] }}</span>
            <a href="{{ route('all-products') }}" class="amzp-more" wire:navigate>{{ __('See more') }}</a>
        </div>
        <div x-data="{el:null}" x-init="el=$refs.c2" style="position:relative">
            <button @click="el.scrollBy({left:-900,behavior:'smooth'})" class="amzp-arr l"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg></button>
            <div x-ref="c2" class="amzp-strip noscr">
                @foreach ($bsGroup0['products'] as $i => $p)
                    <a href="#" class="amzp-prod">
                        <div class="amzp-prod-img-wrap">
                            @if($p['image'])<img src="{{ asset($p['image']) }}" class="amzp-prod-img" loading="lazy">
                            @else<div class="amzp-prod-noimg">{!! $svgImg !!}</div>@endif
                            @if($i < 10)<span class="amzp-rank">#{{ $i+1 }}</span>@endif
                        </div>
                        <p class="amzp-prod-name">{{ Str::limit($p['name'],40) }}</p>
                        @if($p['average_rating'])<p class="amzp-stars">{{ str_repeat('★',round($p['average_rating'])) }}{{ str_repeat('☆',5-round($p['average_rating'])) }}</p>@endif
                        <p class="amzp-price">{{ number_format($p['base_price'],2) }} <span style="font-weight:400;font-size:.62rem;color:#565959">{{ $p['currency'] }}</span></p>
                        @if($p['total_sold']>0)<p class="amzp-sold">{{ $p['total_sold']>=1000 ? number_format($p['total_sold']/1000,1).'k+' : $p['total_sold'].'+' }} {{ __('bought') }}</p>@endif
                    </a>
                @endforeach
            </div>
            <button @click="el.scrollBy({left:900,behavior:'smooth'})" class="amzp-arr r"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg></button>
        </div>
    </div>
</div>
@endif

{{-- ══════════════════════════════════════════════
     5 ▸ 4-COL GRID — Catégories [0..2] + Featured [3]
═══════════════════════════════════════════════ --}}
<div class="amzp-w" style="margin-bottom:8px">
    <div class="amzp-grid4">

        @foreach (array_slice($categories, 0, 3) as $cat)
        <div class="amzp-sub">
            <p class="amzp-sub-title">{{ $cat['name'] }}</p>
            <div class="amzp-sub-grid">
                @forelse (array_slice($cat['children'], 0, 4) as $child)
                    <div class="amzp-sub-cell">
                        <div class="amzp-sub-img">
                            @if(!empty($child['image']))<img src="{{ asset($child['image']) }}" alt="{{ $child['name'] }}" loading="lazy">
                            @else{!! $svgImg !!}@endif
                        </div>
                        <span class="amzp-sub-label">{{ $child['name'] }}</span>
                    </div>
                @empty
                    @for ($e = 0; $e < 4; $e++)
                        <div class="amzp-sub-cell"><div class="amzp-sub-img">{!! $svgImg !!}</div><span class="amzp-sub-label">—</span></div>
                    @endfor
                @endforelse
            </div>
            <a href="{{ route('all-products') }}" class="amzp-sub-more" wire:navigate>{{ __('See more') }}</a>
        </div>
        @endforeach

        {{-- Slot 4 : produit mis en avant dans catégorie [3] --}}
        @php
            $catFeat1   = $categories[3] ?? null;
            // Premier produit best seller appartenant réellement à cette catégorie
            $bsFeat1    = $bestSellersByCategory[3]['products'][0] ?? ($bestSellers[0] ?? null);
        @endphp
        <div class="amzp-sub">
            <p class="amzp-sub-title">{{ __('Featured in') }} {{ $catFeat1['name'] ?? __('Kitchen') }}</p>
            @if ($bsFeat1)
                @if($bsFeat1['image'])
                    <img src="{{ asset($bsFeat1['image']) }}" style="width:100%;height:160px;object-fit:contain;padding:8px;background:#f9f9f9;border-radius:2px" loading="lazy">
                @else
                    <div style="width:100%;height:160px;background:#f0f2f3;display:flex;align-items:center;justify-content:center;border-radius:2px">{!! $svgImg !!}</div>
                @endif
                <p style="font-size:.72rem;color:#0F1111;line-height:1.3;margin-top:6px">{{ Str::limit($bsFeat1['name'],60) }}</p>
                <p style="color:#B12704;font-weight:700;font-size:.85rem;margin-top:3px">{{ number_format($bsFeat1['base_price'],2) }} {{ $bsFeat1['currency'] }}</p>
            @endif
            <a href="{{ route('all-products') }}" class="amzp-sub-more" wire:navigate>{{ __('Learn more') }}</a>
        </div>

    </div>
</div>

{{-- ══════════════════════════════════════════════
     6 ▸ CIRCLE CATEGORIES — row 1
═══════════════════════════════════════════════ --}}
@if (count($categories) > 0)
<div class="amzp-w">
    <div class="amzp-card">
        <div class="amzp-card-head">
            <span class="amzp-sec-title">{{ __('Discover the French Showcase') }}</span>
            <a href="{{ route('all-products') }}" class="amzp-more" wire:navigate>{{ __('See more') }}</a>
        </div>
        <div class="amzp-circles noscr" style="padding:8px 14px 16px">
            @php $circleColors = ['#cc0000','#a50000','#d32f2f','#b71c1c','#c62828','#e53935','#880e4f','#4a148c','#1565c0','#01579b','#006400','#005a00']; @endphp
            @foreach ($categories as $ci => $cat)
                <div class="amzp-circle-item">
                    <div class="amzp-circle" style="background:{{ $circleColors[$ci % 12] }}">
                        @if(!empty($cat['image']))<img src="{{ asset($cat['image']) }}" alt="{{ $cat['name'] }}" loading="lazy">
                        @else<div class="amzp-circle-noimg">{!! $svgCircle !!}</div>@endif
                    </div>
                    <span class="amzp-circle-label">{{ $cat['name'] }}</span>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endif

{{-- ══════════════════════════════════════════════
     7 ▸ BEST SELLERS — groupe [1] (produits réellement dans cette catégorie)
═══════════════════════════════════════════════ --}}
@if (!empty($bestSellersByCategory[1]['products']))
@php $bsGroup1 = $bestSellersByCategory[1]; @endphp
<div class="amzp-w">
    <div class="amzp-card">
        <div class="amzp-card-head">
            <span class="amzp-sec-title">{{ __('Best Sellers in') }} {{ $bsGroup1['category_name'] }}</span>
        </div>
        <div x-data="{el:null}" x-init="el=$refs.c3" style="position:relative">
            <button @click="el.scrollBy({left:-900,behavior:'smooth'})" class="amzp-arr l"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg></button>
            <div x-ref="c3" class="amzp-strip noscr" style="padding-bottom:16px">
                @foreach (array_slice($bsGroup1['products'], 0, 12) as $i => $p)
                    <a href="#" class="amzp-prod" style="width:175px;min-width:175px">
                        <div class="amzp-prod-img-wrap" style="width:155px;height:178px">
                            @if($p['image'])<img src="{{ asset($p['image']) }}" class="amzp-prod-img" loading="lazy">
                            @else<div class="amzp-prod-noimg">{!! $svgImg !!}</div>@endif
                            <span class="amzp-rank">#{{ $i+1 }}</span>
                        </div>
                        <p class="amzp-prod-name" style="font-size:.75rem">{{ Str::limit($p['name'],50) }}</p>
                        @if($p['average_rating'])
                            <p class="amzp-stars">{{ str_repeat('★',round($p['average_rating'])) }}{{ str_repeat('☆',5-round($p['average_rating'])) }}
                                @if($p['total_reviews'])<span style="color:#007185;font-size:.62rem">({{ number_format($p['total_reviews']) }})</span>@endif
                            </p>
                        @endif
                        <p class="amzp-price" style="font-size:.9rem">{{ number_format($p['base_price'],2) }} <span style="font-weight:400;font-size:.62rem;color:#565959">{{ $p['currency'] }}</span></p>
                        @if($p['discount']>0)<p style="color:#B12704;font-size:.7rem;font-weight:600">-{{ $p['discount'] }}%</p>@endif
                    </a>
                @endforeach
            </div>
            <button @click="el.scrollBy({left:900,behavior:'smooth'})" class="amzp-arr r"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg></button>
        </div>
    </div>
</div>
@endif

{{-- ══════════════════════════════════════════════
     8 ▸ 4-COL GRID row 2 — Catégories [4..5] + Featured [6] + [7]
═══════════════════════════════════════════════ --}}
<div class="amzp-w" style="margin-bottom:8px">
    <div class="amzp-grid4">

        @foreach (array_slice($categories, 4, 2) as $cat)
        <div class="amzp-sub">
            <p class="amzp-sub-title">{{ $cat['name'] }}</p>
            <div class="amzp-sub-grid">
                @forelse (array_slice($cat['children'], 0, 4) as $child)
                    <div class="amzp-sub-cell">
                        <div class="amzp-sub-img">
                            @if(!empty($child['image']))<img src="{{ asset($child['image']) }}" alt="{{ $child['name'] }}" loading="lazy">
                            @else{!! $svgImg !!}@endif
                        </div>
                        <span class="amzp-sub-label">{{ $child['name'] }}</span>
                    </div>
                @empty
                    @for ($e = 0; $e < 4; $e++)
                        <div class="amzp-sub-cell"><div class="amzp-sub-img">{!! $svgImg !!}</div><span class="amzp-sub-label">—</span></div>
                    @endfor
                @endforelse
            </div>
            <a href="{{ route('all-products') }}" class="amzp-sub-more" wire:navigate>{{ __('See more') }}</a>
        </div>
        @endforeach

        {{-- Slot 3 : produit mis en avant dans catégorie [6] --}}
        @php
            $catFeat2 = $categories[6] ?? null;
            $bsFeat2  = $bestSellersByCategory[6]['products'][0] ?? ($bestSellers[3] ?? null);
        @endphp
        <div class="amzp-sub">
            <p class="amzp-sub-title">{{ __('Featured in') }} {{ $catFeat2['name'] ?? __('Lawn & Garden') }}</p>
            @if ($bsFeat2)
                @if($bsFeat2['image'])
                    <img src="{{ asset($bsFeat2['image']) }}" style="width:100%;height:160px;object-fit:contain;padding:8px;background:#f9f9f9;border-radius:2px" loading="lazy">
                @else
                    <div style="width:100%;height:160px;background:#f0f2f3;display:flex;align-items:center;justify-content:center;border-radius:2px">{!! $svgImg !!}</div>
                @endif
                <p style="font-size:.72rem;color:#0F1111;line-height:1.3;margin-top:6px">{{ Str::limit($bsFeat2['name'],60) }}</p>
                <p style="color:#B12704;font-weight:700;font-size:.85rem;margin-top:3px">{{ number_format($bsFeat2['base_price'],2) }} {{ $bsFeat2['currency'] }}</p>
            @endif
            <a href="{{ route('all-products') }}" class="amzp-sub-more" wire:navigate>{{ __('Learn more') }}</a>
        </div>

        {{-- Catégorie [7] --}}
        @php $cat7 = $categories[7] ?? null; @endphp
        <div class="amzp-sub">
            <p class="amzp-sub-title">{{ $cat7['name'] ?? __('Garden') }}</p>
            <div class="amzp-sub-grid">
                @forelse (array_slice($cat7['children'] ?? [], 0, 4) as $child)
                    <div class="amzp-sub-cell">
                        <div class="amzp-sub-img">
                            @if(!empty($child['image']))<img src="{{ asset($child['image']) }}" alt="{{ $child['name'] }}" loading="lazy">
                            @else{!! $svgImg !!}@endif
                        </div>
                        <span class="amzp-sub-label">{{ $child['name'] }}</span>
                    </div>
                @empty
                    @for ($e = 0; $e < 4; $e++)
                        <div class="amzp-sub-cell"><div class="amzp-sub-img">{!! $svgImg !!}</div><span class="amzp-sub-label">—</span></div>
                    @endfor
                @endforelse
            </div>
            <a href="{{ route('all-products') }}" class="amzp-sub-more" wire:navigate>{{ __('See more') }}</a>
        </div>

    </div>
</div>

{{-- ══════════════════════════════════════════════
     9 ▸ BEST SELLERS — groupe [2] (produits réellement dans cette catégorie)
═══════════════════════════════════════════════ --}}
@if (!empty($bestSellersByCategory[2]['products']))
@php $bsGroup2 = $bestSellersByCategory[2]; @endphp
<div class="amzp-w">
    <div class="amzp-card">
        <div class="amzp-card-head">
            <span class="amzp-sec-title">{{ __('Best Sellers in') }} {{ $bsGroup2['category_name'] }}</span>
        </div>
        <div x-data="{el:null}" x-init="el=$refs.c4" style="position:relative">
            <button @click="el.scrollBy({left:-900,behavior:'smooth'})" class="amzp-arr l"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg></button>
            <div x-ref="c4" class="amzp-strip noscr">
                @foreach (array_slice($bsGroup2['products'], 0, 12) as $i => $p)
                    <a href="#" class="amzp-prod">
                        <div class="amzp-prod-img-wrap">
                            @if($p['image'])<img src="{{ asset($p['image']) }}" class="amzp-prod-img" loading="lazy">
                            @else<div class="amzp-prod-noimg">{!! $svgImg !!}</div>@endif
                            <span class="amzp-rank">#{{ $i+1 }}</span>
                        </div>
                        <p class="amzp-prod-name">{{ Str::limit($p['name'],40) }}</p>
                        <p class="amzp-price">{{ number_format($p['base_price'],2) }} <span style="font-weight:400;font-size:.62rem;color:#565959">{{ $p['currency'] }}</span></p>
                        @if($p['total_sold']>0)<p class="amzp-sold">{{ $p['total_sold']>=1000 ? number_format($p['total_sold']/1000,1).'k+' : $p['total_sold'].'+' }} {{ __('bought') }}</p>@endif
                    </a>
                @endforeach
            </div>
            <button @click="el.scrollBy({left:900,behavior:'smooth'})" class="amzp-arr r"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg></button>
        </div>
    </div>
</div>
@endif

{{-- ══════════════════════════════════════════════
     10 ▸ CIRCLE CATEGORIES — row 2
═══════════════════════════════════════════════ --}}
@if (count($categories) >= 4)
<div class="amzp-w">
    <div class="amzp-card">
        <div class="amzp-card-head">
            <span class="amzp-sec-title">{{ __('Our Exclusive Boutique') }}</span>
            <a href="{{ route('all-products') }}" class="amzp-more" wire:navigate>{{ __('Discover') }}</a>
        </div>
        <div class="amzp-circles noscr" style="padding:8px 14px 16px">
            @php $reds = ['#cc0000','#b71c1c','#c62828','#d32f2f','#e53935','#f44336','#ef5350','#e57373','#b71c1c','#880e4f','#4a148c','#1a237e']; @endphp
            @foreach ($categories as $ci2 => $cat2)
                <div class="amzp-circle-item">
                    <div class="amzp-circle" style="background:{{ $reds[$ci2 % 12] }}">
                        @if(!empty($cat2['image']))<img src="{{ asset($cat2['image']) }}" alt="{{ $cat2['name'] }}" loading="lazy">
                        @else<div class="amzp-circle-noimg">{!! $svgCircle !!}</div>@endif
                    </div>
                    <span class="amzp-circle-label">{{ $cat2['name'] }}</span>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endif

{{-- ══════════════════════════════════════════════
     11 ▸ 4 PROMO CARDS ROW 2 — Catégorie [9] dynamique
═══════════════════════════════════════════════ --}}
<div class="amzp-w" style="margin-bottom:8px">
    <div class="amzp-grid4">

        {{-- Troisième flash sale --}}
        <div class="amzp-promo">
            <p class="amzp-promo-title" style="color:#CC0C39">{{ !empty($activeSales[2]) ? $activeSales[2]['title'] : __('All Flash Sales') }}</p>
            <div class="amzp-promo-img">
                @if(!empty($activeSales[2]['banner']))<img src="{{ asset($activeSales[2]['banner']) }}" alt="">
                @elseif(!empty($activeSales[2]['products'][0]['image']))<img src="{{ asset($activeSales[2]['products'][0]['image']) }}" alt="" style="object-fit:contain;padding:8px">
                @else{!! $svgImg !!}@endif
            </div>
            <a href="{{ route('all-products') }}" class="amzp-promo-more" wire:navigate>{{ __('Discover') }}</a>
        </div>

        {{-- Produit mis en avant dans catégorie [8] --}}
        @php
            $catFeat3 = $categories[8] ?? null;
            $bsFeat3  = $bestSellersByCategory[8]['products'][0] ?? ($bestSellers[5] ?? null);
        @endphp
        <div class="amzp-promo">
            <p class="amzp-promo-title">{{ __('Featured in') }} {{ $catFeat3['name'] ?? __('Automobile') }}</p>
            <div class="amzp-promo-img">
                @if(!empty($bsFeat3['image']))<img src="{{ asset($bsFeat3['image']) }}" alt="" style="object-fit:contain;padding:8px">
                @else{!! $svgImg !!}@endif
            </div>
            @if($bsFeat3)
                <p style="font-size:.72rem;color:#0F1111;line-height:1.3">{{ Str::limit($bsFeat3['name'],55) }}</p>
                <p style="color:#B12704;font-weight:700;font-size:.84rem">{{ number_format($bsFeat3['base_price'],2) }} {{ $bsFeat3['currency'] }}</p>
            @endif
            <a href="{{ route('all-products') }}" class="amzp-promo-more" wire:navigate>{{ __('Learn more') }}</a>
        </div>

        {{-- Refurbished tech --}}
        <div class="amzp-promo">
            <p class="amzp-promo-title">{{ __('Refurbished tech under 50') }}</p>
            <div class="amzp-promo-img">
                @if(!empty($newArrivals[2]['image']))<img src="{{ asset($newArrivals[2]['image']) }}" alt="" style="object-fit:contain;padding:8px">
                @else{!! $svgImg !!}@endif
            </div>
            <a href="{{ route('all-products') }}" class="amzp-promo-more" wire:navigate>{{ __('See all') }}</a>
        </div>

        {{-- Catégorie [9] avec ses enfants --}}
        @php $cat9 = $categories[9] ?? null; @endphp
        <div class="amzp-sub">
            <p class="amzp-sub-title">{{ $cat9['name'] ?? __('Garden') }}</p>
            <div class="amzp-sub-grid">
                @forelse (array_slice($cat9['children'] ?? [], 0, 4) as $child)
                    <div class="amzp-sub-cell">
                        <div class="amzp-sub-img">
                            @if(!empty($child['image']))<img src="{{ asset($child['image']) }}" alt="{{ $child['name'] }}" loading="lazy">
                            @else{!! $svgImg !!}@endif
                        </div>
                        <span class="amzp-sub-label">{{ $child['name'] }}</span>
                    </div>
                @empty
                    @for ($e = 0; $e < 4; $e++)
                        <div class="amzp-sub-cell"><div class="amzp-sub-img">{!! $svgImg !!}</div><span class="amzp-sub-label">—</span></div>
                    @endfor
                @endforelse
            </div>
            <a href="{{ route('all-products') }}" class="amzp-sub-more" wire:navigate>{{ __('Discover') }}</a>
        </div>

    </div>
</div>

{{-- ══════════════════════════════════════════════
     12 ▸ FLASH SALES — une carte par vente active
═══════════════════════════════════════════════ --}}
@foreach ($activeSales as $sale)
<div class="amzp-w">
    <div class="amzp-card" style="border-top:3px solid {{ $sale['bg_color'] }}">

        <div class="amzp-card-head" style="flex-wrap:wrap;gap:8px;padding-bottom:10px;border-bottom:1px solid #f0f0f0">
            <div style="display:flex;align-items:center;gap:8px;flex:1;min-width:0">
                @if($sale['badge_text'])
                    <span style="background:{{ $sale['bg_color'] }};color:{{ $sale['text_color'] }};font-size:.65rem;font-weight:700;padding:2px 8px;border-radius:3px;white-space:nowrap;flex-shrink:0">{{ $sale['badge_text'] }}</span>
                @endif
                <span class="amzp-sec-title" style="color:{{ $sale['bg_color'] }};white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $sale['title'] }}</span>
                @if($sale['description'])
                    <span style="font-size:.72rem;color:#565959;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" class="dark-muted">{{ $sale['description'] }}</span>
                @endif
            </div>

            @if($sale['show_countdown'] && $sale['seconds_left'] > 0)
                <div
                    x-data="{
                        secs:{{ $sale['seconds_left'] }},
                        get h(){ return Math.floor(this.secs/3600) },
                        get m(){ return Math.floor((this.secs%3600)/60) },
                        get s(){ return this.secs%60 },
                        pad(n){ return String(n).padStart(2,'0') },
                        tick(){ if(this.secs>0) this.secs-- }
                    }"
                    x-init="setInterval(()=>tick(),1000)"
                    style="display:inline-flex;align-items:center;gap:5px;font-size:.8rem;color:{{ $sale['bg_color'] }};font-weight:700;flex-shrink:0"
                >
                    <svg style="width:13px;height:13px;flex-shrink:0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/></svg>
                    {{ __('Ends in') }}
                    <span class="amzp-cd" :style="secs < 3600 ? 'background:#CC0C39' : ''" x-text="pad(h)+':'+pad(m)+':'+pad(s)">{{ gmdate('H:i:s', $sale['seconds_left']) }}</span>
                </div>
            @elseif($sale['status'] === 'scheduled')
                <span style="font-size:.75rem;color:#007185;font-weight:600;flex-shrink:0">{{ __('Starts') }} {{ \Carbon\Carbon::parse($sale['starts_at'])->diffForHumans() }}</span>
            @endif

            @if($sale['total_orders'] > 0)
                <span style="font-size:.7rem;color:#565959;flex-shrink:0" class="dark-muted">{{ number_format($sale['total_orders']) }} {{ __('orders') }}</span>
            @endif
            <a href="{{ route('all-products') }}" class="amzp-more" style="margin-left:auto;flex-shrink:0" wire:navigate>{{ __('See all') }} ({{ $sale['total_products'] }}) ›</a>
        </div>

        @if(!empty($sale['products']))
            <div x-data="{el:null}" x-init="el=$refs['fs{{ $sale['id'] }}']" style="position:relative">
                <button @click="el.scrollBy({left:-900,behavior:'smooth'})" class="amzp-arr l"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg></button>
                <div x-ref="fs{{ $sale['id'] }}" class="amzp-strip noscr">
                    @foreach ($sale['products'] as $sp)
                        <a href="#" class="amzp-prod" style="width:150px;min-width:150px;{{ !$sp['is_available'] ? 'opacity:0.55' : '' }}">
                            <div class="amzp-prod-img-wrap" style="width:136px;height:155px">
                                @if($sp['image'])<img src="{{ asset($sp['image']) }}" class="amzp-prod-img" loading="lazy">
                                @else<div class="amzp-prod-noimg">{!! $svgImg !!}</div>@endif
                                @if($sp['discount'] > 0)
                                    <span class="amzp-disc-badge" style="background:{{ $sale['bg_color'] }}">-{{ $sp['discount'] }}%</span>
                                @endif
                                @if($sp['is_featured'])
                                    <span class="amzp-new-badge" style="background:#f0c14b;color:#111">★</span>
                                @endif
                                @if(!$sp['is_available'])
                                    <div style="position:absolute;inset:0;background:rgba(255,255,255,.6);display:flex;align-items:center;justify-content:center">
                                        <span style="font-size:.65rem;font-weight:700;color:#555;background:#fff;padding:3px 6px;border-radius:3px">{{ __('Sold out') }}</span>
                                    </div>
                                @endif
                            </div>

                            @if($sale['show_countdown'] && $sale['seconds_left'] > 0 && $sp['is_available'])
                                <div
                                    x-data="{
                                        secs:{{ $sale['seconds_left'] }},
                                        get h(){ return Math.floor(this.secs/3600) },
                                        get m(){ return Math.floor((this.secs%3600)/60) },
                                        get s(){ return this.secs%60 },
                                        pad(n){ return String(n).padStart(2,'0') },
                                        tick(){ if(this.secs>0) this.secs-- }
                                    }"
                                    x-init="setInterval(()=>tick(),1000)"
                                    style="display:flex;align-items:center;gap:4px;margin-top:6px"
                                >
                                    <span class="amzp-flash" style="background:{{ $sale['bg_color'] }}">-{{ $sp['discount'] }}%</span>
                                    <span class="amzp-cd" x-text="pad(h)+':'+pad(m)+':'+pad(s)"></span>
                                </div>
                            @else
                                <div style="margin-top:6px">
                                    <span class="amzp-flash" style="background:{{ $sale['bg_color'] }}">-{{ $sp['discount'] }}%</span>
                                </div>
                            @endif

                            <p class="amzp-prod-name" style="font-size:.71rem">{{ Str::limit($sp['name'],38) }}</p>

                            <div style="display:flex;align-items:baseline;gap:3px;flex-wrap:wrap;justify-content:center;margin-top:2px">
                                <span class="amzp-price" style="font-size:.85rem">{{ number_format($sp['flash_price'],2) }} <span style="font-weight:400;font-size:.62rem;color:#565959">{{ $sp['currency'] }}</span></span>
                                @if($sp['original_price'] > $sp['flash_price'])
                                    <span class="amzp-compare">{{ number_format($sp['original_price'],2) }}</span>
                                @endif
                            </div>

                            @if($sale['show_stock'] && $sp['show_stock'] && $sp['stock_total'] > 0)
                                @php $pct = min(100, round($sp['stock_sold'] / $sp['stock_total'] * 100)); @endphp
                                <div style="width:100%;margin-top:5px">
                                    <div style="background:#e0e0e0;border-radius:2px;height:4px;overflow:hidden">
                                        <div style="background:{{ $pct >= 80 ? '#CC0C39' : ($pct >= 50 ? '#f0c14b' : '#4caf50') }};height:100%;width:{{ $pct }}%;transition:width .5s"></div>
                                    </div>
                                    <p style="font-size:.6rem;color:{{ $sp['is_low_stock'] ? '#CC0C39' : '#565959' }};margin-top:2px;font-weight:{{ $sp['is_low_stock'] ? '700' : '400' }}">
                                        @if($sp['is_low_stock']){{ __('Only') }} {{ $sp['stock_remaining'] }} {{ __('left') }}
                                        @else{{ $sp['stock_remaining'] }} {{ __('remaining') }}@endif
                                    </p>
                                </div>
                            @endif

                            @if($sale['show_sold'] && $sp['stock_sold'] > 0)
                                <p class="amzp-sold">{{ $sp['stock_sold'] }}+ {{ __('sold') }}</p>
                            @endif
                            @if($sp['max_per_order'] && $sp['is_available'])
                                <p style="font-size:.6rem;color:#007185;margin-top:1px">{{ __('Max') }} {{ $sp['max_per_order'] }} {{ __('per order') }}</p>
                            @endif
                        </a>
                    @endforeach
                </div>
                <button @click="el.scrollBy({left:900,behavior:'smooth'})" class="amzp-arr r"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg></button>
            </div>
        @else
            <div style="padding:24px;text-align:center;color:#565959;font-size:.82rem">{{ __('Products coming soon') }}…</div>
        @endif

    </div>
</div>
@endforeach

{{-- ── Ventes à venir (teaser) ──────────────────────────────── --}}
@if(!empty($upcomingSales))
<div class="amzp-w" style="margin-bottom:8px">
    <div style="display:grid;grid-template-columns:repeat({{ min(count($upcomingSales),3) }},1fr);gap:8px">
        @foreach ($upcomingSales as $uSale)
            <div class="amzp-promo" style="border-top:3px solid {{ $uSale['bg_color'] }}">
                <div style="display:flex;align-items:center;gap:6px">
                    @if($uSale['badge_text'])
                        <span style="background:{{ $uSale['bg_color'] }};color:{{ $uSale['text_color'] }};font-size:.62rem;font-weight:700;padding:2px 6px;border-radius:3px">{{ $uSale['badge_text'] }}</span>
                    @endif
                    <p class="amzp-promo-title" style="color:{{ $uSale['bg_color'] }}">{{ $uSale['title'] }}</p>
                </div>
                <div class="amzp-promo-img">
                    @if($uSale['banner'])<img src="{{ asset($uSale['banner']) }}" alt="">
                    @elseif(!empty($uSale['products'][0]['image']))<img src="{{ asset($uSale['products'][0]['image']) }}" alt="" style="object-fit:contain;padding:8px">
                    @else{!! $svgImg !!}@endif
                </div>
                <div style="font-size:.75rem;color:#007185;font-weight:600">
                    {{ __('Starts') }} {{ \Carbon\Carbon::parse($uSale['starts_at'])->diffForHumans() }}
                </div>
                <a href="{{ route('all-products') }}" class="amzp-promo-more" wire:navigate>{{ __('Get notified') }}</a>
            </div>
        @endforeach
    </div>
</div>
@endif

{{-- ══════════════════════════════════════════════
     13 ▸ RECOMMANDATIONS PERSONNALISÉES CTA
═══════════════════════════════════════════════ --}}
<div class="amzp-w">
    <div class="amzp-reco">
        <h2>{{ __('See your personalised recommendations') }}</h2>
        <a href="{{ route('all-products') }}" class="amzp-reco-btn" wire:navigate>{{ __('Sign in') }}</a>
        <p style="font-size:.74rem;color:#565959;margin-top:10px">
            {{ __('New customer?') }} <a href="#" style="color:#007185;text-decoration:none">{{ __('Start here') }}</a>
        </p>
    </div>
</div>

</div>{{-- /.amzp --}}

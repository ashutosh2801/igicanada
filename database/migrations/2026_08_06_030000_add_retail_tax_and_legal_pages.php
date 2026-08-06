<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->json('tax_breakdown')->nullable()->after('tax_total');
        });

        Schema::table('content_pages', function (Blueprint $table): void {
            $table->dropUnique(['slug']);
            $table->string('sales_channel', 20)->default('wholesale')->index()->after('id');
            $table->unique(['sales_channel', 'slug']);
        });

        $now = now();
        foreach ($this->legalPages() as $page) {
            DB::table('content_pages')->updateOrInsert(
                ['sales_channel' => 'retail', 'slug' => $page['slug']],
                [...$page, 'sales_channel' => 'retail', 'updated_at' => $now, 'created_at' => $now],
            );
        }
    }

    public function down(): void
    {
        DB::table('content_pages')->where('sales_channel', 'retail')->whereIn('slug', [
            'privacy-policy', 'terms-of-sale', 'shipping-returns',
        ])->delete();

        Schema::table('content_pages', function (Blueprint $table): void {
            $table->dropUnique(['sales_channel', 'slug']);
            $table->dropColumn('sales_channel');
            $table->unique('slug');
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn('tax_breakdown');
        });
    }

    private function legalPages(): array
    {
        return [
            [
                'title' => 'Privacy Policy',
                'slug' => 'privacy-policy',
                'excerpt' => 'How Leather Wallets Canada collects, uses, stores, and protects customer information.',
                'body_html' => '<h2>Information we collect</h2><p>We collect information you provide when placing an order or contacting us, including your name, email address, telephone number, shipping address, and order details.</p><h2>How we use information</h2><p>We use this information to process and deliver orders, provide customer support, prevent fraud, maintain business records, and meet legal obligations.</p><h2>Payments</h2><p>Online payments are processed by PayPal. We do not store complete payment card details on our servers.</p><h2>Sharing and retention</h2><p>Information may be shared with service providers that help us operate the store, process payments, send communications, and deliver orders. We retain information only as long as reasonably necessary for these purposes and legal requirements.</p><h2>Your choices</h2><p>You may contact us to ask about access to or correction of your personal information. Before publishing this policy, the business should add its privacy contact name, email address, retention schedule, analytics providers, and any marketing tools used.</p>',
                'meta_title' => 'Privacy Policy · Leather Wallets Canada',
                'meta_description' => 'Privacy practices for the Leather Wallets Canada retail store.',
                'status' => 'review_required',
                'is_legal' => true,
                'published_at' => null,
            ],
            [
                'title' => 'Terms of Sale',
                'slug' => 'terms-of-sale',
                'excerpt' => 'Terms governing purchases from Leather Wallets Canada.',
                'body_html' => '<h2>Orders</h2><p>Orders are subject to product availability and acceptance. We may contact you if an item becomes unavailable or if additional verification is required.</p><h2>Prices and payment</h2><p>Prices are displayed in Canadian dollars unless stated otherwise. Applicable shipping and taxes are shown or confirmed during checkout. Payment must be completed using an available payment method before a paid order is processed.</p><h2>Product information</h2><p>We aim to present product colours, measurements, and descriptions accurately. Natural leather may show variations in grain, colour, and texture.</p><h2>Delivery</h2><p>Delivery estimates are not guarantees. Risk of delay may arise from carriers, weather, customs, or circumstances outside our control.</p><h2>Review required</h2><p>Before publishing, the business should review cancellation rules, warranty terms, governing law, limitation-of-liability language, and consumer-protection requirements with qualified counsel.</p>',
                'meta_title' => 'Terms of Sale · Leather Wallets Canada',
                'meta_description' => 'Terms applicable to retail purchases from Leather Wallets Canada.',
                'status' => 'review_required',
                'is_legal' => true,
                'published_at' => null,
            ],
            [
                'title' => 'Shipping & Returns',
                'slug' => 'shipping-returns',
                'excerpt' => 'Shipping destinations, delivery expectations, and return instructions.',
                'body_html' => '<h2>Shipping</h2><p>We currently support standard shipping to addresses in Canada and the United States. Shipping charges are calculated from the order subtotal and destination.</p><h2>Order inspection</h2><p>Please inspect your order promptly after delivery and contact us if it arrives damaged, incorrect, or incomplete. Keep the packaging and photographs where possible.</p><h2>Returns</h2><p>Contact our team before returning an item so that we can provide instructions and confirm eligibility. Items should be unused, unaltered, and returned with their original packaging unless they are defective.</p><h2>Review required</h2><p>Before publishing, the business must add its return window, return address, refund timing, final-sale exclusions, return-shipping responsibility, exchange policy, and customer-service contact details.</p>',
                'meta_title' => 'Shipping and Returns · Leather Wallets Canada',
                'meta_description' => 'Shipping and returns information for Leather Wallets Canada orders.',
                'status' => 'review_required',
                'is_legal' => true,
                'published_at' => null,
            ],
        ];
    }
};

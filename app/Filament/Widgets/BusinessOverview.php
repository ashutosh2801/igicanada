<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\ContactEnquiries\ContactEnquiryResource;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\ContactEnquiry;
use App\Models\Order;
use App\Support\AdminStorefront;
use Carbon\CarbonImmutable;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class BusinessOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $monthStart = now()->startOfMonth();
        $previousMonthStart = $monthStart->copy()->subMonth();

        $revenue = (float) AdminStorefront::apply(Order::query())
            ->where('payment_status', 'paid')
            ->where('paid_at', '>=', $monthStart)
            ->sum('total');

        $previousRevenue = (float) AdminStorefront::apply(Order::query())
            ->where('payment_status', 'paid')
            ->whereBetween('paid_at', [$previousMonthStart, $monthStart])
            ->sum('total');

        $orders = AdminStorefront::apply(Order::query())->where('placed_at', '>=', $monthStart)->count();
        $previousOrders = AdminStorefront::apply(Order::query())
            ->whereBetween('placed_at', [$previousMonthStart, $monthStart])
            ->count();

        $actionStatuses = AdminStorefront::current() === 'retail'
            ? ['processing']
            : ['awaiting_quote', 'quoted', 'processing'];
        $pendingOrders = AdminStorefront::apply(Order::query())
            ->whereIn('status', $actionStatuses)
            ->count();

        $newEnquiries = AdminStorefront::apply(ContactEnquiry::query())->where('status', 'new')->count();

        return [
            Stat::make('Revenue this month', '$'.number_format($revenue, 2))
                ->description($this->changeDescription($revenue, $previousRevenue))
                ->descriptionIcon($revenue >= $previousRevenue ? Heroicon::ArrowTrendingUp : Heroicon::ArrowTrendingDown)
                ->descriptionColor($revenue >= $previousRevenue ? 'success' : 'danger')
                ->chart($this->dailyPaidRevenue())
                ->color('success')
                ->icon(Heroicon::Banknotes)
                ->url(OrderResource::getUrl('index')),
            Stat::make('Orders this month', number_format($orders))
                ->description($this->countChangeDescription($orders, $previousOrders))
                ->descriptionIcon($orders >= $previousOrders ? Heroicon::ArrowTrendingUp : Heroicon::ArrowTrendingDown)
                ->descriptionColor($orders >= $previousOrders ? 'success' : 'danger')
                ->chart($this->dailyOrderCount())
                ->color('primary')
                ->icon(Heroicon::ShoppingBag)
                ->url(OrderResource::getUrl('index')),
            Stat::make('Orders requiring action', number_format($pendingOrders))
                ->description(AdminStorefront::current() === 'retail' ? 'Retail orders being processed' : 'Awaiting quote, quoted or processing')
                ->descriptionIcon(Heroicon::Clock)
                ->color($pendingOrders > 0 ? 'warning' : 'success')
                ->icon(Heroicon::ClipboardDocumentCheck)
                ->url(OrderResource::getUrl('index')),
            Stat::make('New enquiries', number_format($newEnquiries))
                ->description($newEnquiries === 1 ? 'Unread customer enquiry' : 'Unread customer enquiries')
                ->descriptionIcon(Heroicon::Envelope)
                ->color($newEnquiries > 0 ? 'warning' : 'success')
                ->icon(Heroicon::ChatBubbleLeftRight)
                ->url(ContactEnquiryResource::getUrl('index')),
        ];
    }

    private function changeDescription(float $current, float $previous): string
    {
        if ($previous === 0.0) {
            return $current > 0 ? 'New revenue this month' : 'No paid revenue yet';
        }

        $change = (($current - $previous) / $previous) * 100;

        return number_format(abs($change), 1).'% '.($change >= 0 ? 'up' : 'down').' from last month';
    }

    private function countChangeDescription(int $current, int $previous): string
    {
        $difference = $current - $previous;

        if ($difference === 0) {
            return 'Same as last month';
        }

        return number_format(abs($difference)).' '.($difference > 0 ? 'more' : 'fewer').' than last month';
    }

    /** @return array<float> */
    private function dailyPaidRevenue(): array
    {
        $start = CarbonImmutable::today()->subDays(6);
        $totals = AdminStorefront::apply(Order::query())
            ->where('payment_status', 'paid')
            ->where('paid_at', '>=', $start)
            ->get(['paid_at', 'total'])
            ->groupBy(fn (Order $order): string => $order->paid_at->toDateString())
            ->map(fn ($orders): float => (float) $orders->sum('total'));

        return collect(range(0, 6))
            ->map(fn (int $day): float => (float) ($totals[$start->addDays($day)->toDateString()] ?? 0))
            ->all();
    }

    /** @return array<float> */
    private function dailyOrderCount(): array
    {
        $start = CarbonImmutable::today()->subDays(6);
        $totals = AdminStorefront::apply(Order::query())
            ->where('placed_at', '>=', $start)
            ->get(['placed_at'])
            ->countBy(fn (Order $order): string => $order->placed_at->toDateString());

        return collect(range(0, 6))
            ->map(fn (int $day): float => (float) ($totals[$start->addDays($day)->toDateString()] ?? 0))
            ->all();
    }
}

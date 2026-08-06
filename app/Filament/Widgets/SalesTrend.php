<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Support\AdminStorefront;
use Carbon\CarbonImmutable;
use Filament\Widgets\ChartWidget;

class SalesTrend extends ChartWidget
{
    protected static ?int $sort = 2;

    protected static bool $isLazy = false;

    protected ?string $heading = 'Sales trend';

    protected ?string $description = 'Paid order revenue for the last 14 days';

    protected string $color = 'primary';

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $start = CarbonImmutable::today()->subDays(13);
        $orders = AdminStorefront::apply(Order::query())
            ->where('payment_status', 'paid')
            ->where('paid_at', '>=', $start)
            ->get(['sales_channel', 'paid_at', 'total']);

        $days = collect(range(0, 13))->map(fn (int $day): CarbonImmutable => $start->addDays($day));

        $datasets = collect([
            'wholesale' => ['Wholesale', '#dc2626', 'rgba(220, 38, 38, 0.10)'],
            'retail' => ['Retail', '#a16207', 'rgba(161, 98, 7, 0.10)'],
        ])->when(
            AdminStorefront::current() !== 'all',
            fn ($channels) => $channels->only(AdminStorefront::current()),
        )->map(
            fn (array $style, string $channel): array => $this->dataset($orders, $days, $channel, ...$style),
        )->values()->all();

        return [
            'datasets' => $datasets,
            'labels' => $days->map(fn (CarbonImmutable $day): string => $day->format('M j'))->all(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => ['display' => true],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => ['prefix' => '$'],
                ],
            ],
        ];
    }

    private function dataset($orders, $days, string $channel, string $label, string $border, string $background): array
    {
        $revenueByDay = $orders
            ->where('sales_channel', $channel)
            ->groupBy(fn (Order $order): string => $order->paid_at->toDateString())
            ->map(fn ($dailyOrders): float => (float) $dailyOrders->sum('total'));

        return [
            'label' => $label.' revenue (CAD)',
            'data' => $days
                ->map(fn (CarbonImmutable $day): float => (float) ($revenueByDay[$day->toDateString()] ?? 0))
                ->all(),
            'borderColor' => $border,
            'backgroundColor' => $background,
            'fill' => true,
            'tension' => 0.35,
        ];
    }
}

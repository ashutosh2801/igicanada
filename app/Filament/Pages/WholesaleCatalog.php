<?php

namespace App\Filament\Pages;

use App\Models\Category;
use App\Models\Product;
use App\Support\AdminStorefront;
use BackedEnum;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use UnitEnum;

class WholesaleCatalog extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentArrowDown;
    protected static string|UnitEnum|null $navigationGroup = 'Catalog';
    protected static ?string $navigationLabel = 'Wholesale catalog';
    protected static ?string $title = 'Wholesale catalog';
    protected static ?int $navigationSort = 4;
    protected static ?string $slug = 'wholesale-catalog';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'catalog_date' => today()->toDateString(),
            'product_ids' => Product::query()
                ->where('is_active', true)
                ->whereIn('visibility', ['wholesale', 'both'])
                ->whereHas('variants', fn ($query) => $query->where('is_active', true)->where('is_available_wholesale', true))
                ->orderBy('name')
                ->pluck('id')
                ->all(),
            'category_id' => null,
            'show_prices' => true,
            'show_stock' => false,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Catalog settings')->description('Generate a dated PDF for selected wholesale products.')->schema([
                DatePicker::make('catalog_date')->label('Catalog date')->required()->native(false),
                Select::make('category_id')
                    ->label('Limit to category')
                    ->options(fn (): array => Category::query()
                        ->where('is_active', true)
                        ->orderBy('name')
                        ->get(['id', 'name'])
                        ->mapWithKeys(fn (Category $category): array => [(string) $category->id => $this->safeUtf8($category->name)])
                        ->all())
                    ->searchable()
                    ->native(false)
                    ->live()
                    ->afterStateUpdated(function (Set $set, ?string $state): void {
                        $set('product_ids', $this->wholesaleProductIds($state));
                    }),
                Select::make('product_ids')
                    ->label('Products')
                    ->options(fn (Get $get): array => $this->wholesaleProductOptions($get('category_id')))
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->required()
                    ->helperText('Selecting a category limits this list to products in that category. Remove products here when creating a smaller catalog.'),
                Toggle::make('show_prices')->label('Show wholesale prices')->default(true),
                Toggle::make('show_stock')->label('Show stock quantities')->default(false),
            ])->columns(2)->columnSpanFull(),
        ])->statePath('data');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])->livewireSubmitHandler('generate')->footer([
                Actions::make([
                    Action::make('generate')->label('Download PDF catalog')->icon(Heroicon::OutlinedArrowDownTray)->submit('generate')->keyBindings(['mod+s']),
                ])->alignment(Alignment::Start),
            ]),
        ]);
    }

    public function generate()
    {
        $data = $this->form->getState();
        $query = Product::query()
            ->where('is_active', true)
            ->whereIn('visibility', ['wholesale', 'both'])
            ->whereIn('id', $data['product_ids'])
            ->with([
                'primaryMedia:id,disk,path,alt_text',
                'images:id,product_id,media_asset_id,path,alt_text,position',
                'images.mediaAsset:id,disk,path,alt_text,title',
                'variants' => fn ($query) => $query->where('is_active', true)->where('is_available_wholesale', true)->orderBy('id'),
            ])
            ->orderBy('name');

        if (filled($data['category_id'])) {
            $query->whereHas('categories', fn ($categories) => $categories->whereKey($data['category_id']));
        }

        $products = $query->get()->map(function (Product $product): array {
            $images = collect([$product->primaryImageUrl(), ...$product->images->map->url()->all()])
                ->filter()
                ->unique()
                ->take(2)
                ->values();

            return ['product' => $product, 'images' => $images];
        });

        abort_if($products->isEmpty(), 422, 'Select at least one wholesale product.');

        $date = Carbon::parse($data['catalog_date']);
        $pdf = Pdf::setOption(['isRemoteEnabled' => true])->loadView('pdf.wholesale-catalog', [
            'products' => $products,
            'catalogDate' => $date,
            'showPrices' => (bool) $data['show_prices'],
            'showStock' => (bool) $data['show_stock'],
            'company' => config('commerce.company'),
        ])->setPaper('letter');

        return $pdf->download('IGI-Canada-Wholesale-Catalog-'.$date->format('Y-m-d').'.pdf');
    }

    /** @return array<int, int> */
    private function wholesaleProductIds(?string $categoryId): array
    {
        return $this->wholesaleProductQuery($categoryId)->pluck('id')->all();
    }

    /** @return array<int|string, string> */
    private function wholesaleProductOptions(?string $categoryId): array
    {
        return $this->wholesaleProductQuery($categoryId)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->mapWithKeys(fn (Product $product): array => [(string) $product->id => $this->safeUtf8($product->name)])
            ->all();
    }

    private function wholesaleProductQuery(?string $categoryId): \Illuminate\Database\Eloquent\Builder
    {
        return Product::query()
            ->where('is_active', true)
            ->whereIn('visibility', ['wholesale', 'both'])
            ->whereHas('variants', fn ($query) => $query->where('is_active', true)->where('is_available_wholesale', true))
            ->when(filled($categoryId), fn ($query) => $query->whereHas('categories', fn ($categories) => $categories->whereKey($categoryId)));
    }

    private function safeUtf8(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $clean = @iconv('UTF-8', 'UTF-8//IGNORE', $value);

        return is_string($clean) ? $clean : '';
    }
}

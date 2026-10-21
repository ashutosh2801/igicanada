<?php

namespace App\Filament\Pages;

use App\Models\Category;
use App\Models\Product;
use App\Support\AdminStorefront;
use BackedEnum;
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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Http\RedirectResponse;
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
                Section::make('Catalog details')
                    ->description('Choose which commercial details appear in the downloaded PDF.')
                    ->schema([
                        Toggle::make('show_prices')->label('Show wholesale prices')->default(true),
                        Toggle::make('show_stock')->label('Show stock quantities')->default(false),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
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

    public function generate(): RedirectResponse
    {
        $data = $this->form->getState();
        $token = Str::random(48);
        Cache::put('wholesale-catalog-download:'.$token, $data, now()->addMinutes(10));

        // The PDF must be downloaded by a normal HTTP response. Returning binary
        // PDF content directly from this Livewire action makes Livewire try to
        // JSON-encode it, which causes a malformed UTF-8 error.
        return redirect()->route('admin.wholesale-catalog.download', ['token' => $token]);
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

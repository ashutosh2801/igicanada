<?php

namespace App\Filament\Resources\Categories\Tables;

use App\Filament\Tables\Columns\DirectImageColumn;
use App\Models\Category;
use App\Support\AdminStorefront;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('parent.name')
                    ->label('Parent / submenu')
                    ->searchable(),
                TextColumn::make('legacy_id')
                    ->numeric()
                    ->sortable()
                    ->visible(fn (): bool => AdminStorefront::showsWholesaleFields()),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('slug')
                    ->searchable(),
                TextColumn::make('visibility')
                    ->label('Website')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'both' => 'Both',
                        'retail' => 'Leather Wallets',
                        default => 'IGI Canada',
                    })
                    ->visible(fn (): bool => AdminStorefront::current() === 'all'),
                DirectImageColumn::make('image')
                    ->state(function (Category $record): ?string {
                        $url = $record->imageUrl();

                        if ($url === null) {
                            return null;
                        }

                        return str_starts_with($url, '/storage/') ? substr($url, strlen('/storage/')) : $url;
                    })
                    ->disk('public')
                    ->checkFileExistence(false),
                TextColumn::make('position')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('is_featured')
                    ->label('Homepage')
                    ->boolean()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('name')
                    ->schema([
                        TextInput::make('value')->label('Name')->placeholder('Search by name…'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => blank($data['value'] ?? null) ? $query : $query->where('name', 'like', '%'.$data['value'].'%')),
                Filter::make('slug')
                    ->schema([
                        TextInput::make('value')->label('Slug')->placeholder('Search by slug…'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => blank($data['value'] ?? null) ? $query : $query->where('slug', 'like', '%'.$data['value'].'%')),
                SelectFilter::make('parent')
                    ->label('Parent / submenu')
                    ->options(function (): array {
                        $parents = Category::query()
                            ->whereHas('children')
                            ->orderBy('name')
                            ->pluck('name', 'id');

                        return [0 => 'No parent (top-level)'] + $parents->all();
                    })
                    ->searchable()
                    ->preload()
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;

                        if (blank($value)) {
                            return $query;
                        }

                        return (int) $value === 0
                            ? $query->whereNull('parent_id')
                            : $query->where('parent_id', $value);
                    }),
                TernaryFilter::make('has_image')
                    ->label('Has image')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->where(fn (Builder $query): Builder => $query
                            ->whereNotNull('image_path')
                            ->orWhereNotNull('image_media_asset_id')),
                        false: fn (Builder $query): Builder => $query->where(fn (Builder $query): Builder => $query
                            ->whereNull('image_path')
                            ->whereNull('image_media_asset_id')),
                    ),
                SelectFilter::make('visibility')
                    ->label('Website')
                    ->options([
                        'wholesale' => 'IGI Canada only',
                        'retail' => 'Leather Wallets only',
                        'both' => 'Both websites',
                    ])
                    ->visible(fn (): bool => AdminStorefront::current() === 'all'),
                Filter::make('position')
                    ->schema([
                        TextInput::make('min')->label('Min')->numeric()->minValue(0)->placeholder('0'),
                        TextInput::make('max')->label('Max')->numeric()->minValue(0)->placeholder('999'),
                    ])
                    ->columns(2)
                    ->query(function (Builder $query, array $data): Builder {
                        if (filled($data['min'] ?? null)) {
                            $query->where('position', '>=', (int) $data['min']);
                        }

                        if (filled($data['max'] ?? null)) {
                            $query->where('position', '<=', (int) $data['max']);
                        }

                        return $query;
                    }),
                Filter::make('legacy_id')
                    ->label('Legacy ID')
                    ->schema([
                        TextInput::make('min')->label('Min')->numeric()->placeholder('0'),
                        TextInput::make('max')->label('Max')->numeric()->placeholder('999999'),
                    ])
                    ->columns(2)
                    ->visible(fn (): bool => AdminStorefront::showsWholesaleFields())
                    ->query(function (Builder $query, array $data): Builder {
                        if (filled($data['min'] ?? null)) {
                            $query->where('legacy_id', '>=', (int) $data['min']);
                        }

                        if (filled($data['max'] ?? null)) {
                            $query->where('legacy_id', '<=', (int) $data['max']);
                        }

                        return $query;
                    }),
                TernaryFilter::make('is_active'),
                Filter::make('created_at')
                    ->label('Created date')
                    ->schema([
                        DatePicker::make('from')->label('From'),
                        DatePicker::make('until')->label('Until'),
                    ])
                    ->columns(2)
                    ->query(function (Builder $query, array $data): Builder {
                        if (filled($data['from'] ?? null)) {
                            $query->whereDate('created_at', '>=', $data['from']);
                        }

                        if (filled($data['until'] ?? null)) {
                            $query->whereDate('created_at', '<=', $data['until']);
                        }

                        return $query;
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if (filled($data['from'] ?? null)) {
                            $indicators[] = 'Created from '.$data['from'];
                        }

                        if (filled($data['until'] ?? null)) {
                            $indicators[] = 'Created until '.$data['until'];
                        }

                        return $indicators;
                    }),
                Filter::make('updated_at')
                    ->label('Updated date')
                    ->schema([
                        DatePicker::make('from')->label('From'),
                        DatePicker::make('until')->label('Until'),
                    ])
                    ->columns(2)
                    ->query(function (Builder $query, array $data): Builder {
                        if (filled($data['from'] ?? null)) {
                            $query->whereDate('updated_at', '>=', $data['from']);
                        }

                        if (filled($data['until'] ?? null)) {
                            $query->whereDate('updated_at', '<=', $data['until']);
                        }

                        return $query;
                    }),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('position')
            ->reorderable('position');
    }
}

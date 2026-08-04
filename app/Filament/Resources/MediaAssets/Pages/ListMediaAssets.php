<?php

namespace App\Filament\Resources\MediaAssets\Pages;

use App\Filament\Resources\MediaAssets\MediaAssetResource;
use App\Models\MediaAsset;
use App\Models\MediaFolder;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListMediaAssets extends ListRecords
{
    protected static string $resource = MediaAssetResource::class;

    protected ?string $subheading = 'Upload images once, organize them into folders, and reuse them across multiple products.';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('uploadImages')
                ->label('Upload images')
                ->icon('heroicon-o-arrow-up-tray')
                ->modalHeading('Upload images to media library')
                ->modalSubmitActionLabel('Add to library')
                ->schema([
                    Select::make('media_folder_id')
                        ->label('Folder')
                        ->options(fn () => MediaFolder::query()->orderBy('name')->pluck('name', 'id'))
                        ->searchable()
                        ->preload()
                        ->createOptionForm([
                            TextInput::make('name')->required()->unique('media_folders', 'name'),
                        ])
                        ->createOptionUsing(fn (array $data): int => MediaFolder::create($data)->id),
                    FileUpload::make('uploads')
                        ->label('Images')
                        ->image()
                        ->multiple()
                        ->appendFiles()
                        ->reorderable()
                        ->maxFiles(50)
                        ->disk('public')
                        ->directory('media-library')
                        ->required()
                        ->helperText('Select or drag up to 50 images at once.'),
                ])
                ->action(function (array $data): void {
                    foreach ($data['uploads'] as $path) {
                        MediaAsset::firstOrCreate(
                            ['path' => $path],
                            [
                                'media_folder_id' => $data['media_folder_id'] ?? null,
                                'disk' => 'public',
                                'filename' => basename($path),
                            ],
                        );
                    }

                    Notification::make()
                        ->title(count($data['uploads']).' image(s) added to media library')
                        ->success()
                        ->send();
                }),
            CreateAction::make()->label('Add single image'),
        ];
    }
}

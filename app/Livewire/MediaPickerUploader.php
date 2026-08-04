<?php

namespace App\Livewire;

use App\Models\MediaAsset;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class MediaPickerUploader extends Component
{
    use WithFileUploads;

    /** @var array<int, TemporaryUploadedFile> */
    public array $uploads = [];

    public function updatedUploads(): void
    {
        if ($this->uploads !== []) {
            $this->uploadImages();
        }
    }

    public function uploadImages(): void
    {
        abort_unless(auth()->user()?->canAccessPanel(Filament::getPanel('admin')), 403);

        $this->validate([
            'uploads' => ['required', 'array', 'min:1', 'max:20'],
            'uploads.*' => ['required', 'image', 'max:12288'],
        ], [
            'uploads.required' => 'Select at least one image to upload.',
            'uploads.max' => 'You can upload a maximum of 20 images at once.',
        ]);

        foreach ($this->uploads as $upload) {
            $originalName = $upload->getClientOriginalName();
            $path = $upload->store('media-library', 'public');

            MediaAsset::create([
                'media_folder_id' => null,
                'disk' => 'public',
                'path' => $path,
                'filename' => $originalName,
                'title' => Str::of(pathinfo($originalName, PATHINFO_FILENAME))
                    ->replace(['-', '_'], ' ')
                    ->title()
                    ->limit(255),
            ]);
        }

        $count = count($this->uploads);
        $this->reset('uploads');

        $this->dispatch('media-picker-uploaded');

        Notification::make()
            ->title("{$count} image(s) uploaded. You can select them below.")
            ->success()
            ->send();
    }

    public function render(): View
    {
        return view('livewire.media-picker-uploader');
    }
}

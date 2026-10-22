<?php

namespace App\Filament\Pages;

use App\Models\EmailTemplate;
use App\Models\User;
use App\Support\AdminStorefront;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
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
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use UnitEnum;

class CustomerEmail extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPaperAirplane;
    protected static string|UnitEnum|null $navigationGroup = 'Customers';
    protected static ?string $navigationLabel = 'Send email';
    protected static ?string $title = 'Send email to customers';
    protected static ?int $navigationSort = 2;
    protected static ?string $slug = 'customer-email';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'sales_channel' => $this->defaultChannel(),
            'user_ids' => [],
            'template_id' => null,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Recipients and message')->description('Only the customers selected below will receive this email.')->schema([
                Select::make('sales_channel')
                    ->label('Website')
                    ->options(['wholesale' => 'IGI Canada wholesale', 'retail' => 'Leather Wallets retail'])
                    ->visible(fn (): bool => AdminStorefront::current() === 'all')
                    ->dehydratedWhenHidden()
                    ->live()
                    ->afterStateUpdated(function (Set $set): void {
                        $set('user_ids', []);
                        $set('template_id', null);
                    })
                    ->required(),
                Select::make('user_ids')
                    ->label('Select customers')
                    ->options(fn (Get $get): array => $this->customerOptions((string) ($get('sales_channel') ?: $this->defaultChannel())))
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->required()
                    ->helperText('Select one or more customers. Administrators are never included.'),
                Select::make('template_id')
                    ->label('Email template')
                    ->options(fn (Get $get): array => $this->templateOptions((string) ($get('sales_channel') ?: $this->defaultChannel())))
                    ->searchable()
                    ->preload()
                    ->required()
                    ->helperText('The selected active template will be personalized with each customer name and login details.'),
                FileUpload::make('attachments')
                    ->label('Attachments')
                    ->multiple()
                    ->reorderable()
                    ->disk('local')
                    ->directory('customer-email-attachments')
                    ->visibility('private')
                    ->maxFiles(5)
                    ->maxSize(10240)
                    ->acceptedFileTypes([
                        'application/pdf',
                        'application/zip',
                        'application/msword',
                        'application/vnd.ms-excel',
                        'application/vnd.ms-powerpoint',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                        'image/*',
                        'text/plain',
                        'text/csv',
                    ])
                    ->helperText('Optional. Up to 5 files, 10 MB each. These files will be attached to every selected customer email.'),
            ])->columns(2)->columnSpanFull(),
        ])->statePath('data');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])->livewireSubmitHandler('send')->footer([
                Actions::make([
                    Action::make('send')
                        ->label('Send to selected customers')
                        ->icon(Heroicon::OutlinedPaperAirplane)
                        ->submit('send'),
                ])->alignment(Alignment::Start),
            ]),
        ]);
    }

    public function send(): void
    {
        $data = $this->form->getState();
        $channel = (string) ($data['sales_channel'] ?? $this->defaultChannel());
        $template = EmailTemplate::query()
            ->active()
            ->forChannel($channel)
            ->findOrFail($data['template_id']);
        $users = User::query()
            ->whereIn('account_type', [$channel])
            ->whereIn('id', $data['user_ids'])
            ->whereNotNull('email')
            ->get();

        $attachments = collect($data['attachments'] ?? [])
            ->filter(fn (mixed $path): bool => is_string($path) && $path !== '')
            ->values();
        $attachmentDisk = Storage::disk('local');

        $sent = 0;
        try {
            foreach ($users as $user) {
                $variables = [
                    'name' => (string) $user->name,
                    'username' => (string) ($user->legacy_username ?: $user->email),
                    'email' => (string) $user->email,
                    'phone' => (string) ($user->phone ?: ''),
                    'type' => $channel,
                    'login_link' => route($channel === 'retail' ? 'retail.account.login' : 'login'),
                ];

                Mail::html($template->render($variables), function ($message) use ($user, $template, $variables, $attachments, $attachmentDisk): void {
                    $message
                        ->to($user->email, $user->name)
                        ->subject($template->renderSubject($variables));

                    foreach ($attachments as $path) {
                        if (! $attachmentDisk->exists($path)) {
                            continue;
                        }

                        $message->attach($attachmentDisk->path($path), [
                            'as' => basename($path),
                            'mime' => $attachmentDisk->mimeType($path) ?: 'application/octet-stream',
                        ]);
                    }
                });
                $sent++;
            }
        } finally {
            foreach ($attachments as $path) {
                $attachmentDisk->delete($path);
            }
        }

        Notification::make()
            ->title("Email sent to {$sent} selected customer(s)")
            ->success()
            ->send();
    }

    /** @return array<int|string, string> */
    private function customerOptions(string $channel): array
    {
        return User::query()
            ->where('account_type', $channel)
            ->whereNotNull('email')
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->mapWithKeys(fn (User $user): array => [(string) $user->id => "{$user->name} ({$user->email})"])
            ->all();
    }

    /** @return array<int|string, string> */
    private function templateOptions(string $channel): array
    {
        return EmailTemplate::query()
            ->active()
            ->forChannel($channel)
            ->orderBy('name')
            ->get(['id', 'name', 'subject'])
            ->mapWithKeys(fn (EmailTemplate $template): array => [(string) $template->id => "{$template->name} - {$template->subject}"])
            ->all();
    }

    private function defaultChannel(): string
    {
        return in_array(AdminStorefront::current(), ['retail', 'walletsandbelts'], true) ? 'retail' : 'wholesale';
    }
}

<?php

namespace App\Filament\Auth\Pages;

use App\Support\AdminStorefront;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class Login extends BaseLogin
{
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getSalesChannelFormComponent(),
                $this->getRememberFormComponent(),
            ]);
    }

    protected function getSalesChannelFormComponent(): Select
    {
        return Select::make('sales_channel')
            ->label('Website')
            ->hiddenLabel()
            ->helperText('Admin data will be scoped to the selected website.')
            ->options(AdminStorefront::websiteOptions())
            ->default(config('storefronts.default_channel'))
            ->required();
    }

    public function authenticate(): ?LoginResponse
    {
        $response = parent::authenticate();

        if ($response !== null) {
            $channel = $this->form->getState()['sales_channel'] ?? AdminStorefront::current();

            AdminStorefront::select($channel);

            auth()->user()?->forceFill(['admin_sales_channel' => $channel])->saveQuietly();
        }

        return $response;
    }
}

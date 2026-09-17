<?php

namespace App\Filament\Pages\Auth;

use Filament\Actions\Action;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

class Login extends BaseLogin
{
    protected string $view = 'filament.auth.fullscreen-login';

    protected Width | string | null $maxWidth = Width::FiveExtraLarge;

    public function getTitle(): string | Htmlable
    {
        return 'Masuk — PT Borneo Prima Item Creation';
    }

    public function getHeading(): string | Htmlable | null
    {
        return 'Selamat Datang';
    }

    public function getSubheading(): string | Htmlable | null
    {
        // Jangan tampilkan link register bawaan, ganti dengan deskripsi produk.
        return new HtmlString(
            'Masuk untuk mengelola <span class="font-semibold text-gray-900 dark:text-white">pengajuan &amp; persetujuan item PT Borneo Prima</span> — cepat, akurat, dan terpantau.'
        );
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label('Alamat email')
            ->placeholder('nama@borneoprima.com')
            ->prefixIcon('heroicon-m-envelope')
            ->email()
            ->required()
            ->autocomplete('username')
            ->autofocus()
            ->extraInputAttributes([
                'inputmode' => 'email',
                'autocapitalize' => 'none',
                'autocorrect' => 'off',
                'spellcheck' => 'false',
            ]);
    }

    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label('Kata sandi')
            ->placeholder('••••••••')
            ->prefixIcon('heroicon-m-lock-closed')
            ->password()
            ->revealable(filament()->arePasswordsRevealable())
            ->autocomplete('current-password')
            ->required();
    }

    protected function getRememberFormComponent(): Component
    {
        return Checkbox::make('remember')
            ->label('Ingat saya di perangkat ini');
    }

    protected function getAuthenticateFormAction(): Action
    {
        return Action::make('authenticate')
            ->label('Masuk ke Dashboard')
            ->icon('heroicon-m-arrow-right-on-rectangle')
            ->submit('authenticate');
    }
}

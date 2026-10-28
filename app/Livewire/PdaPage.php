<?php

namespace App\Livewire;

use Livewire\Component;

/**
 * Base of the PDA (handheld) pages built with Livewire. A page renders its own header / main / footer
 * (<x-pda.page>) into layouts/pda, so every part stays in sync after an update.
 */
abstract class PdaPage extends Component
{
    public ?string $notice = null;

    public ?string $failure = null;

    public function boot(): void
    {
        abort_unless(auth()->check() && auth()->user()->is_active, 401);
    }

    protected function page(string $view, array $data = [])
    {
        return view($view, $data)->extends('layouts.pda');
    }

    protected function ok(string $message): void
    {
        $this->notice = $message;
        $this->failure = null;
    }

    protected function fail(string $message): void
    {
        $this->failure = $message;
        $this->notice = null;
    }
}

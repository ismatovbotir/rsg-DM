<?php

namespace App\Livewire;

use Livewire\Component;

/**
 * Base of the admin (desktop) pages built with Livewire. Pages render into layouts/admin (sections), and
 * only admins may load or update them: boot() runs on every Livewire request, not just the first page load.
 */
abstract class AdminPage extends Component
{
    public ?string $notice = null;

    public ?string $failure = null;

    public function boot(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
    }

    protected function page(string $view, array $data = [])
    {
        return view($view, $data)->extends('layouts.admin');
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

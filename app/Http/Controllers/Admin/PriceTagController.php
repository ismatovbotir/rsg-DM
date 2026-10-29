<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PriceCheck;

/** Printable sheet of the queued price tags (the queue page itself is the Livewire PriceTags\Index). */
class PriceTagController extends Controller
{
    public function print()
    {
        abort_unless(auth()->user()->canAdmin('admin.pricetags'), 403);

        return view('admin.pricetags.print', [
            'checks' => PriceCheck::queued()->with('item')->oldest()->get(),
        ]);
    }
}
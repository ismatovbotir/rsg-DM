<?php

use App\Http\Controllers\Admin\PriceTagController as AdminPriceTagController;
use App\Livewire\Admin\Checkers as CheckersLw;
use App\Livewire\Admin\Devices as DevicesLw;
use App\Livewire\Admin\Exchange as ExchangeLw;
use App\Livewire\Admin\PriceTags as PriceTagsLw;
use App\Livewire\Admin\Documents as DocumentsLw;
use App\Livewire\Admin\Items as ItemsLw;
use App\Livewire\Admin\Labels as LabelsLw;
use App\Livewire\Admin\Messages as MessagesLw;
use App\Livewire\Admin\Tasks as TasksLw;
use App\Livewire\Admin\Printers as PrintersLw;
use App\Livewire\Admin\Users as UsersLw;
use App\Livewire\Admin\Counterparties as CounterpartiesLw;
use App\Livewire\Admin\PriceTypes as PriceTypesLw;
use App\Livewire\Admin\Warehouses as WarehousesLw;
use App\Http\Controllers\PriceCheckerController;
use App\Http\Controllers\DevLoginController;
use App\Http\Controllers\LoginController;
use App\Livewire\Pda;
use Illuminate\Support\Facades\Route;

// Local dev only: the controller returns 404 unless DevAutoLogin::enabled().
Route::get('/dev/login/{role}', DevLoginController::class)->name('dev.login');

Route::middleware(['guest', 'device'])->group(function () {
    Route::view('/login', 'login')->name('login'); // hosts the Livewire login form
});
Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

// Customer-facing price checker terminal: needs the token from the QR code the server generates (see EnsurePriceChecker).
Route::get('/pricechecker/activate', [PriceCheckerController::class, 'activateShow'])->name('checker.activate.show');
Route::post('/pricechecker/activate', [PriceCheckerController::class, 'activate'])->middleware('throttle:10,1')->name('checker.activate');
Route::get('/pricechecker/activate/{code}', [PriceCheckerController::class, 'activate'])->middleware('throttle:10,1');
Route::middleware('checker')->group(function () {
    Route::get('/pricechecker', [PriceCheckerController::class, 'screen'])->name('checker.screen');
    Route::get('/pricechecker/price', [PriceCheckerController::class, 'price'])->name('checker.price');
    Route::get('/pricechecker/version', [PriceCheckerController::class, 'versionJson'])->name('checker.version');
});

// Main route: the authorization page for guests, the main menu once signed in.
Route::get('/', Pda\Menu::class)->middleware(['device', 'active', 'login.guests'])->name('menu');

// PDA (mobile) interface, Livewire pages. `menu:<key>` = the admin can switch the menu item off per user.
Route::middleware(['auth', 'active', 'device'])->group(function () {
    Route::get('/m/{key}', Pda\Module::class)->name('module');
    Route::get('/settings', Pda\Settings::class)->name('settings');

    Route::middleware('menu:directories')->group(function () {
        Route::get('/directory', Pda\Directory::class)->name('directory');
        Route::get('/browse/directory', Pda\Browse::class)->name('browse.directory');
    });

    Route::get('/docs/type/{type}', Pda\Docs\Index::class)->name('docs.index');
    Route::get('/docs/type/{type}/create', Pda\Docs\Create::class)->name('docs.create');
    Route::get('/docs/{record}', Pda\Docs\Show::class)->name('docs.show');
    Route::get('/docs/{record}/browse', Pda\Browse::class)->name('docs.browse');
    Route::get('/docs/{record}/entry/{item}', Pda\Docs\Entry::class)->name('docs.entry');

    Route::get('/tasks', Pda\Tasks\Index::class)->name('tasks.index');
    Route::get('/tasks/{record}', Pda\Tasks\Show::class)->name('tasks.show');

    Route::get('/messages', Pda\Messages\Index::class)->name('messages.index');
    Route::get('/messages/{record}', Pda\Messages\Show::class)->name('messages.show');
});
// Admin (desktop) interface
Route::middleware(['auth', 'active', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::redirect('/', '/admin/documents');

    Route::get('documents', DocumentsLw\Index::class)->name('documents.index');
    Route::get('documents/{record}', DocumentsLw\Show::class)->name('documents.show');

    Route::get('exchange', ExchangeLw\Index::class)->name('exchange.index');
    Route::get('price-tags', PriceTagsLw\Index::class)->name('pricetags.index');
    Route::get('price-tags/print', [AdminPriceTagController::class, 'print'])->name('pricetags.print'); // printable sheet, not an interactive page

    Route::get('items', ItemsLw\Index::class)->name('items.index');
    Route::get('items/create', ItemsLw\Form::class)->name('items.create');
    Route::get('items/{record}/edit', ItemsLw\Form::class)->name('items.edit');

    // Master data and people are deactivated, never deleted: documents reference them.
    Route::get('price-types', PriceTypesLw\Index::class)->name('pricetypes.index');
    Route::get('price-types/create', PriceTypesLw\Form::class)->name('pricetypes.create');
    Route::get('price-types/{record}/edit', PriceTypesLw\Form::class)->name('pricetypes.edit');
    Route::get('warehouses', WarehousesLw\Index::class)->name('warehouses.index');
    Route::get('warehouses/create', WarehousesLw\Form::class)->name('warehouses.create');
    Route::get('warehouses/{record}/edit', WarehousesLw\Form::class)->name('warehouses.edit');
    Route::get('counterparties', CounterpartiesLw\Index::class)->name('counterparties.index');
    Route::get('counterparties/create', CounterpartiesLw\Form::class)->name('counterparties.create');
    Route::get('counterparties/{record}/edit', CounterpartiesLw\Form::class)->name('counterparties.edit');
    Route::get('users', UsersLw\Index::class)->name('users.index');
    Route::get('users/create', UsersLw\Form::class)->name('users.create');
    Route::get('users/{record}/edit', UsersLw\Form::class)->name('users.edit');

    Route::get('tasks', TasksLw\Index::class)->name('tasks.index');
    Route::get('tasks/create', TasksLw\Form::class)->name('tasks.create');

    Route::get('messages', MessagesLw\Index::class)->name('messages.index');
    Route::get('messages/create', MessagesLw\Form::class)->name('messages.create');
    Route::get('messages/{record}', MessagesLw\Show::class)->name('messages.show');

    Route::get('label-templates', LabelsLw\Index::class)->name('labels.index');
    Route::get('label-templates/create', LabelsLw\Designer::class)->name('labels.create');
    Route::get('label-templates/{record}/edit', LabelsLw\Designer::class)->name('labels.edit');
    Route::get('printers', PrintersLw\Index::class)->name('printers.index');
    Route::get('printers/create', PrintersLw\Form::class)->name('printers.create');
    Route::get('printers/{record}/edit', PrintersLw\Form::class)->name('printers.edit');
    Route::get('checkers', CheckersLw\Index::class)->name('checkers.index');
    Route::get('checkers/create', CheckersLw\Form::class)->name('checkers.create');
    Route::get('checkers/{checker}/edit', CheckersLw\Form::class)->name('checkers.edit');

    Route::get('devices', DevicesLw\Index::class)->name('devices.index');
    Route::get('devices/{record}/edit', DevicesLw\Form::class)->name('devices.edit');
});

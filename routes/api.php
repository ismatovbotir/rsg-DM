<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Adines pushes master data here (its 1C code decides the paths). No login unless ONEC_API_TOKEN is set. Max 500 rows per request.
// New style, body {"items":[...]}:  POST /api/v1/sync/{products|groups|counterparties|barcodes}
//   and GET /api/v1/sync/{products|groups|counterparties}/max-code, which Adines asks first to send only records with a bigger code.
// Old style, body {"data":[...]}:   POST /api/{shop|price|priceData|stock}
Route::middleware('onec.token')->group(function () {
    Route::get('/v1/sync/{entity}/max-code', [\App\Http\Controllers\Api\OneCImportController::class, 'maxCode'])
        ->whereIn('entity', ['products', 'groups', 'counterparties'])->name('onec.maxcode');
    Route::post('/v1/sync/{entity}', \App\Http\Controllers\Api\OneCImportController::class)
        ->whereIn('entity', ['products', 'groups', 'counterparties', 'barcodes'])->name('onec.import');
    Route::post('/{entity}', \App\Http\Controllers\Api\OneCImportController::class)
        ->whereIn('entity', ['shop', 'price', 'priceData', 'stock'])->name('onec.import.legacy');
});

// GET /api — index of the API: what Adines can send, the row limit and whether a token is required.
Route::get('/', function () {
    $url = fn ($e) => in_array($e, ['shop', 'price', 'priceData', 'stock']) ? url("/api/{$e}") : url("/api/v1/sync/{$e}");

    return response()->json([
        'name' => config('menu.app_name').' API',
        'auth' => filled(config('onec.api_token')) ? 'Authorization: Bearer <token> (or X-Api-Key)' : 'none',
        'max_rows_per_request' => \App\Http\Controllers\Api\OneCImportController::MAX_ROWS,
        'body' => '{"items":[...]} for /v1/sync/*, {"data":[...]} for shop/price/priceData/stock',
        'endpoints' => collect(array_keys(\App\Http\Controllers\Api\OneCImportController::ROUTES))->mapWithKeys(fn ($e) => [$e => ['method' => 'POST', 'url' => $url($e)]])->all(),
        'max_code' => collect(['products', 'groups', 'counterparties'])->mapWithKeys(fn ($e) => [$e => ['method' => 'GET', 'url' => url("/api/v1/sync/{$e}/max-code")]])->all(),
        'notes' => ['shop = warehouse; stock qty is multiplied by 1000', 'ids are Adines codes; barcodes refer to products and units by uuid; group parent_id empty = first level'],
    ]);
})->name('index');
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

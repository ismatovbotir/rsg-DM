<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExchangeLog;
use App\Services\OneCImporter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Inbound exchange: Adines pushes master data, one entity per endpoint (see ROUTES for the URLs).
 * Body: {"items": [...]} (/api/v1/sync/*) or {"data": [...]} (/api/shop, price, priceData, stock), or a bare array; at most MAX_ROWS rows per request.
 * Before pushing, Adines asks GET /api/v1/sync/{products|groups|counterparties}/max-code and sends only records with a bigger code.
 * Rows are upserted by their natural key (see OneCImporter); a bad row is reported in "errors" and does not stop the others.
 * No login by default; set ONEC_API_TOKEN to require one (see EnsureOneCToken).
 */
class OneCImportController extends Controller
{
    public const MAX_ROWS = 500;

    /** URL name => importer entity */
    public const ROUTES = [
        'products' => 'items', 'groups' => 'groups', 'counterparties' => 'partners', 'barcodes' => 'barcodes',
        'shop' => 'warehouses', 'price' => 'price-types', 'priceData' => 'item-prices', 'stock' => 'item-stocks',
    ];

    public function __construct(private OneCImporter $importer)
    {
    }

    public function __invoke(Request $request, string $entity): JsonResponse
    {
        $entity = self::ROUTES[$entity];
        $started = microtime(true);
        $response = $this->handle($request, $entity);
        $body = $response->getData(true);

        ExchangeLog::create([
            'direction' => 'push',
            'entity' => $entity,
            'ip' => $request->ip(),
            'http_status' => $response->getStatusCode(),
            'rows' => $body['received'] ?? 0,
            'created' => $body['created'] ?? 0,
            'updated' => $body['updated'] ?? 0,
            'errors_count' => count($body['errors'] ?? []) ?: (isset($body['message']) ? 1 : 0),
            'details' => array_slice($body['errors'] ?? (isset($body['message']) ? [['message' => $body['message']]] : []), 0, 50),
            'duration_ms' => (int) round((microtime(true) - $started) * 1000),
        ]);

        return $response;
    }

    /** Highest id we already have, so Adines sends only newer records. 0 when none. The value is under both keys: Adines reads "data" for products and "max_code" for the rest. */
    public function maxCode(string $entity): JsonResponse
    {
        $max = (int) match ($entity) {
            'products' => \App\Models\Item::max('id'),
            'groups' => \App\Models\Group::max('id'),
            'counterparties' => \App\Models\Counterparty::max('id'),
        };

        return response()->json(['max_code' => $max, 'data' => $max]);
    }

    private function handle(Request $request, string $entity): JsonResponse
    {
        $body = $request->json()->all();
        $rows = array_is_list($body) ? $body : ($body['items'] ?? $body['data'] ?? null);

        if (! is_array($rows) || ! array_is_list($rows) || $rows === []) {
            return response()->json(['message' => 'Body must be a non-empty JSON array (or {"items": [...]} / {"data": [...]}).'], 422);
        }

        if (count($rows) > self::MAX_ROWS) {
            return response()->json(['message' => 'Too many rows: max '.self::MAX_ROWS.' per request, got '.count($rows).'.'], 413);
        }

        $stats = ['received' => count($rows), 'created' => 0, 'updated' => 0, 'errors' => []];

        foreach ($this->importer->order($entity, $rows) as $i => $row) {
            try {
                $stats[$this->importer->import($entity, $row) ? 'created' : 'updated']++;
            } catch (\Throwable $e) {
                $e instanceof RuntimeException || report($e); // unexpected: keep the real cause in the log
                $stats['errors'][] = ['index' => $i, 'message' => $e instanceof RuntimeException ? $e->getMessage() : 'Could not save the row.'];
            }
        }

        usort($stats['errors'], fn ($a, $b) => $a['index'] <=> $b['index']);
        $nothingSaved = $stats['errors'] && ! ($stats['created'] + $stats['updated']);

        return response()->json($stats, $nothingSaved ? 422 : 200);
    }
}

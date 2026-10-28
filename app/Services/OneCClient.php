<?php

namespace App\Services;

use App\Models\Document;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class OneCClient
{
    public function enabled(): bool
    {
        return filled(config('onec.base_url'));
    }

    /** Send one completed document to Adines and record the outcome on it. */
    public function pushDocument(Document $doc): bool
    {
        $this->ensureEnabled();

        $doc->loadMissing('lines.item', 'user', 'counterparty', 'warehouse', 'warehouseTo');

        $payload = [
            'number' => $doc->number,
            'type' => $doc->type,
            'date' => $doc->doc_date?->toDateString(),
            'completed_at' => $doc->completed_at?->toIso8601String(),
            'user' => $doc->user?->email,
            'counterparty' => $doc->counterparty?->name,
            'counterparty_stir' => $doc->counterparty?->stir,
            'counterparty_ref' => $doc->counterparty?->onec_ref,
            'warehouse' => $doc->warehouse?->name,
            'warehouse_code' => $doc->warehouse?->code,
            'warehouse_to' => $doc->warehouseTo?->name,
            'warehouse_to_code' => $doc->warehouseTo?->code,
            'price_type' => $doc->priceType?->name,
            'price_type_code' => $doc->priceType?->code,
            'reason' => $doc->reason,
            'comment' => $doc->comment,
            'lines' => $doc->lines->map(fn ($l) => [
                'ref' => $l->item->onec_ref,
                'code' => $l->item->code,
                'article' => $l->item->article,
                'barcode' => $l->item->barcode,
                'qty' => (float) $l->qty,
                'price' => (float) $l->price,
                'check_action' => $l->check_action,
                'batch_no' => $l->batch_no,
                'production_date' => $l->production_date?->toDateString(),
                'expiry_date' => $l->expiry_date?->toDateString(),
            ])->all(),
        ];

        try {
            $this->http()->post('documents', $payload)->throw();
        } catch (\Throwable $e) {
            $doc->update(['onec_status' => Document::SYNC_ERROR, 'onec_error' => Str::limit($e->getMessage(), 1000)]);

            return false;
        }

        $doc->update(['onec_status' => Document::SYNC_DONE, 'synced_at' => now(), 'onec_error' => null]);

        return true;
    }

    /** Push every completed document not yet synced. Returns [sent, failed]. */
    public function pushPending(): array
    {
        $sent = $failed = 0;

        Document::where('status', Document::COMPLETED)
            ->where('onec_status', '!=', Document::SYNC_DONE)
            ->where('onec_status', '!=', Document::SYNC_NONE)
            ->each(function (Document $doc) use (&$sent, &$failed) {
                $this->pushDocument($doc) ? $sent++ : $failed++;
            });

        return [$sent, $failed];
    }

    private function ensureEnabled(): void
    {
        if (! $this->enabled()) {
            throw new RuntimeException('Adines ulanmagan: .env faylida ONEC_BASE_URL ni kiriting.');
        }
    }

    public function http()
    {
        return Http::baseUrl(rtrim(config('onec.base_url'), '/').'/')
            ->timeout(config('onec.timeout'))
            ->withBasicAuth((string) config('onec.user'), (string) config('onec.password'))
            ->acceptJson();
    }
}

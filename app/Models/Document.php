<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    public const DRAFT = 'draft';
    public const COMPLETED = 'completed';

    public const SYNC_PENDING = 'pending';
    public const SYNC_DONE = 'synced';
    public const SYNC_ERROR = 'error';
    public const SYNC_NONE = 'none'; // types that are never sent to Adines (price check)

    protected $fillable = [
        'type', 'status', 'onec_status', 'print_count', 'printed_at', 'user_id',
        'doc_date', 'counterparty_id', 'warehouse_id', 'warehouse_to_id', 'price_type_id', 'reason', 'comment',
        'completed_at', 'synced_at', 'onec_error', 'scope', 'group_id',
    ];

    protected $casts = [
        'printed_at' => 'datetime','doc_date' => 'date', 'completed_at' => 'datetime', 'synced_at' => 'datetime'];

    protected static function booted(): void
    {
        // Number needs the id, so it is assigned right after insert: REC-261026-00012
        static::created(function (Document $doc) {
            $doc->number = strtoupper(substr($doc->type, 0, 3)).'-'.now()->format('ymd').'-'.str_pad($doc->id, 5, '0', STR_PAD_LEFT);
            $doc->saveQuietly();
        });
    }

    /** Document types = menu items flagged with 'doc' => true. [key => title] */
    public static function types(): array
    {
        return collect(config('menu.items'))->where('doc', true)->pluck('title', 'key')->all();
    }

    /** Header fields of a type: [name => [label, type, required]]. */
    public static function headerFieldsFor(string $type): array
    {
        $defs = config('documents.fields');

        return collect(config("documents.types.{$type}.header", []))
            ->mapWithKeys(fn ($required, $name) => [$name => $defs[$name] + ['required' => (bool) $required]])
            ->all();
    }

    /** Header fields that are set once at creation and shown read-only afterwards. */
    public const LOCKED_AFTER_CREATE = ['doc_date', 'counterparty_id'];

    /** Validation rules for the header form of a type; $editing drops the locked fields. */
    public static function headerRules(string $type, bool $editing = false): array
    {
        return collect(self::headerFieldsFor($type))->except($editing ? self::LOCKED_AFTER_CREATE : [])->map(function ($f, $name) {
            $rules = [$f['required'] ? 'required' : 'nullable'];

            $rules = array_merge($rules, match ($f['type']) {
                'date' => ['date'],
                'counterparty' => ['integer', 'exists:counterparties,id'],
                'warehouse' => ['integer', 'exists:warehouses,id'],
                'pricetype' => ['integer', 'exists:price_types,id'],
                default => ['string', 'max:255'],
            });

            if ($name === 'warehouse_to_id') {
                $rules[] = 'different:warehouse_id'; // movement needs two different warehouses
            }

            return $rules;
        })->all();
    }

    /** Field labels for validation messages. */
    public static function headerLabels(string $type): array
    {
        return collect(self::headerFieldsFor($type))->map(fn ($f) => $f['label'])->all();
    }

    public static function validationMessages(): array
    {
        return [
            'required' => ':attribute to‘ldirilishi shart.',
            'exists' => ':attribute noto‘g‘ri tanlangan.',
            'date' => ':attribute noto‘g‘ri sana.',
            'different' => ':attribute va :other bir xil bo‘lmasligi kerak.',
            'max' => ':attribute juda uzun.',
        ];
    }

    public function headerFields(): array
    {
        return self::headerFieldsFor($this->type);
    }

    /** Current header values for form inputs (ids for dropdown / picker fields). */
    public function headerValues(): array
    {
        return collect($this->headerFields())->mapWithKeys(fn ($f, $name) => [
            $name => $name === 'doc_date' ? $this->doc_date?->format('Y-m-d') : $this->{$name},
        ])->all();
    }

    /** Human readable header values (names instead of ids). */
    public function headerDisplay(): array
    {
        return collect($this->headerFields())->mapWithKeys(fn ($f, $name) => [$name => match ($name) {
            'doc_date' => $this->doc_date?->format('d.m.Y'),
            'counterparty_id' => $this->counterparty?->label,
            'warehouse_id' => $this->warehouse?->name,
            'warehouse_to_id' => $this->warehouseTo?->name,
            'price_type_id' => $this->priceType?->name,
            default => $this->{$name},
        }])->all();
    }

    /** Price check: lines carry a verdict (confirmed / print tag) instead of a quantity. */
    public function isPriceCheck(): bool
    {
        return (bool) config("documents.types.{$this->type}.check", false);
    }

    /** Every scanned or picked item opens the entry window first: qty / price / production date, or for a price check the shelf-price confirmation. */
    public function usesEntryWindow(): bool
    {
        return true;
    }

    /** Production date is asked for stock-moving documents only. */
    public function tracksProduction(): bool
    {
        return ! $this->isPriceCheck() && (bool) config("documents.types.{$this->type}.production", true);
    }

    public function canEditQty(): bool
    {
        return (bool) config("documents.types.{$this->type}.qty", true);
    }

    public function canEditPrice(): bool
    {
        return (bool) config("documents.types.{$this->type}.price", false);
    }

    public function getTypeLabelAttribute(): string
    {
        return self::types()[$this->type] ?? $this->type;
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status === self::COMPLETED ? 'Yakunlangan' : 'Qoralama';
    }

    /** Whether documents of this type go to Adines. Price checks do not: they are printed instead. */
    public static function syncsType(string $type): bool
    {
        return (bool) config("documents.types.{$type}.sync", true);
    }

    public function syncs(): bool
    {
        return static::syncsType($this->type);
    }

    /** Price check status: nothing to print / waiting for print / printed (with the time). Empty for other types. */
    public function getPrintLabelAttribute(): string
    {
        if (! $this->isPriceCheck() || $this->isDraft()) {
            return '';
        }

        return match (true) {
            $this->print_count === 0 => 'Chop etish shart emas',
            $this->printed_at !== null => 'Chop etilgan '.$this->printed_at->format('d.m.Y H:i'),
            default => 'Chop etilmagan',
        };
    }

    public function getPrintStateAttribute(): string
    {
        return match (true) {
            $this->print_count === 0 => 'none',
            $this->printed_at !== null => 'completed',
            default => 'error',
        };
    }

    public function getSyncLabelAttribute(): string
    {
        return match ($this->onec_status) {
            self::SYNC_NONE => '',
            self::SYNC_DONE => 'Adines ga yuborilgan',
            self::SYNC_ERROR => 'Xato',
            default => 'Kutilmoqda',
        };
    }

    public function isDraft(): bool
    {
        return $this->status === self::DRAFT;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function counterparty()
    {
        return $this->belongsTo(Counterparty::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function warehouseTo()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_to_id');
    }

    public const SCOPES = ['all' => 'Butun do‘kon', 'group' => 'Guruh', 'free' => 'Erkin skanerlash'];

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    /** True when the check has a list of items to go through (the whole shop or a group), so it has progress. */
    public function hasCheckList(): bool
    {
        return $this->isPriceCheck() && in_array($this->scope, ['all', 'group'], true);
    }

    /**
     * Items that belong on the shelf for this check: active items of the group (or all), and, when the shop has stock data,
     * only those in stock there. A shop without stock data lists every active item.
     */
    public function checkItemsQuery()
    {
        $shop = $this->warehouse_id;

        return Item::active()
            ->when($this->scope === 'group' && $this->group_id, fn ($q) => $q->whereIn('group_id', Group::branchIds((int) $this->group_id)))
            ->when($shop && ItemStock::where('warehouse_id', $shop)->exists(), fn ($q) => $q->whereHas('stocks', fn ($s) => $s->where('warehouse_id', $shop)->where('qty', '>', 0)));
    }

    /** @return array{0: int, 1: int} items checked so far, items to check */
    public function checkProgress(): array
    {
        if (! $this->hasCheckList()) {
            return [0, 0];
        }

        $total = $this->checkItemsQuery()->count();
        $checked = $this->checkItemsQuery()->whereIn('items.id', $this->lines()->select('item_id'))->count();

        return [$checked, $total];
    }

    /** The document's price list, else the one of its warehouse (a shop has its own price list). */
    public function effectivePriceTypeId(): ?int
    {
        return $this->price_type_id ?: ($this->warehouse?->price_type_id ?: null);
    }

    public function priceType()
    {
        return $this->belongsTo(PriceType::class);
    }

    public function lines()
    {
        return $this->hasMany(DocumentLine::class);
    }

    public function tasks()
    {
        return $this->belongsToMany(Task::class, 'task_documents');
    }
}

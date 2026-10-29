<?php

namespace App\Support;

use App\Models\Document;

/**
 * Everything a role can grant: the admin sidebar groups with their items ("admin.items") and the PDA menu items ("pda.receipt").
 * The same structure draws the admin sidebar and the checkbox tree on the role page.
 */
class Permissions
{
    /** Admin component namespace segment -> permission key (App\Livewire\Admin\<Segment>\...). */
    private const COMPONENTS = [
        'Groups' => 'admin.groups', 'Items' => 'admin.items', 'Counterparties' => 'admin.counterparties', 'Warehouses' => 'admin.warehouses', 'PriceTypes' => 'admin.pricetypes',
        'Documents' => 'admin.documents', 'Tasks' => 'admin.tasks', 'Messages' => 'admin.messages',
        'Labels' => 'admin.labels', 'Themes' => 'admin.themes',
        'Devices' => 'admin.devices', 'Checkers' => 'admin.checkers', 'Printers' => 'admin.printers', 'PriceTags' => 'admin.pricetags',
        'Exchange' => 'admin.exchange', 'Users' => 'admin.users', 'Roles' => 'admin.roles', 'Badges' => 'admin.badges',
    ];

    private static function item(string $key, string $title, string $route, ?string $pattern = null, array $params = [], ?string $type = null): array
    {
        return ['key' => $key, 'title' => $title, 'route' => $route, 'pattern' => $pattern ?? $route, 'params' => $params, 'type' => $type];
    }

    /** @return list<array{title: string, items: list<array>}> */
    public static function adminGroups(): array
    {
        $documents = [self::item('admin.documents', 'Barcha hujjatlar', 'admin.documents.index', 'admin.documents.*')];
        foreach (Document::types() as $type => $title) {
            $documents[] = self::item('admin.documents.'.$type, $title, 'admin.documents.index', 'admin.documents.index', ['type' => $type], $type);
        }

        return [
            ['title' => 'Ma’lumotnomalar', 'items' => [
                self::item('admin.groups', 'Tovar guruhlari', 'admin.groups.index', 'admin.groups.*'),
                self::item('admin.items', 'Tovarlar', 'admin.items.index', 'admin.items.*'),
                self::item('admin.counterparties', 'Kontragentlar', 'admin.counterparties.index', 'admin.counterparties.*'),
                self::item('admin.warehouses', 'Omborlar', 'admin.warehouses.index', 'admin.warehouses.*'),
                self::item('admin.pricetypes', 'Narx turlari', 'admin.pricetypes.index', 'admin.pricetypes.*'),
            ]],
            ['title' => 'Hujjatlar', 'items' => $documents],
            ['title' => 'Operatsiyalar', 'items' => [
                self::item('admin.tasks', 'Vazifalar', 'admin.tasks.index', 'admin.tasks.*'),
                self::item('admin.messages', 'Xabarlar', 'admin.messages.index', 'admin.messages.*'),
            ]],
            ['title' => 'Dizaynlar', 'items' => [
                self::item('admin.labels', 'Yorliq dizayneri', 'admin.labels.index', 'admin.labels.*'),
                self::item('admin.themes', 'Narx tekshirgich dizaynlari', 'admin.themes.index', 'admin.themes.*'),
            ]],
            ['title' => 'Qurilmalar', 'items' => [
                self::item('admin.devices', 'PDA qurilmalar', 'admin.devices.index', 'admin.devices.*'),
                self::item('admin.checkers', 'Narx tekshirgichlar', 'admin.checkers.index', 'admin.checkers.*'),
                self::item('admin.printers', 'Printerlar', 'admin.printers.index', 'admin.printers.*'),
                self::item('admin.pricetags', 'Narx yorliqlari', 'admin.pricetags.index', 'admin.pricetags.*'),
            ]],
            ['title' => 'Almashinuv', 'items' => [
                self::item('admin.exchange', 'Adines almashinuvi', 'admin.exchange.index', 'admin.exchange.*'),
            ]],
            ['title' => 'Tizim', 'items' => [
                self::item('admin.users', 'Foydalanuvchilar', 'admin.users.index', 'admin.users.*'),
                self::item('admin.roles', 'Rollar', 'admin.roles.index', 'admin.roles.*'),
                self::item('admin.badges', 'Badge chop etish', 'admin.badges.index', 'admin.badges.*'),
            ]],
        ];
    }

    /** PDA menu items a role can open ("Vazifalarim" and messages are always available). */
    public static function pdaItems(): array
    {
        return collect(config('menu.items'))->reject(fn ($i) => $i['key'] === 'tasks')->mapWithKeys(fn ($i) => ['pda.'.$i['key'] => $i['title']])->all();
    }

    /** @return list<string> */
    public static function adminKeys(): array
    {
        return collect(self::adminGroups())->flatMap(fn ($g) => collect($g['items'])->pluck('key'))->all();
    }

    /** @return list<string> */
    public static function all(): array
    {
        return array_merge(self::adminKeys(), array_keys(self::pdaItems()));
    }

    /** The permission an admin Livewire page needs, from its class (null = admins only). */
    public static function forComponent(string $class): ?string
    {
        return preg_match('/^App\\\\Livewire\\\\Admin\\\\(\w+)\\\\/', $class, $m) ? (self::COMPONENTS[$m[1]] ?? null) : null;
    }
}

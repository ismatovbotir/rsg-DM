<?php

namespace Tests\Feature;

use App\Models\Counterparty;
use App\Models\Group;
use App\Models\Item;
use App\Models\ItemStock;
use App\Models\PriceType;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OneCImportApiTest extends TestCase
{
    use RefreshDatabase;

    private const UNIT = ['uuid' => '1d72c161-9b8f-11f0-994c-e0071bffdaa8', 'name' => 'шт', 'qty' => 1, 'weight' => 0, 'is_stackable' => false, 'shape' => 1];
    private const BOX = ['uuid' => '2e83d272-9b8f-11f0-994c-e0071bffdaa8', 'name' => 'коробка', 'qty' => 12, 'weight' => 0, 'is_stackable' => false, 'shape' => 1];

    private function item(array $over = []): array
    {
        return $over + [
            'id' => 2656, 'uuid' => 'aaaa0000-0000-0000-0000-000000000001', 'group_id' => 654, 'name' => 'Полка 241', 'full_name' => 'Полка 241', 'article' => '1060-239-18',
            'life' => 0, 'gtin' => [], 'unit_main' => self::UNIT, 'unit_order' => self::UNIT, 'unit_report' => self::UNIT,
            'counterparty_id' => '', 'is_weight' => false, 'is_active' => true, 'class_code' => '', 'package_code' => '', 'is_labeled' => 0,
        ];
    }

    private function seedGroups(): void
    {
        $this->postJson('/api/v1/sync/groups', ['items' => [
            ['id' => 654, 'uuid' => 'g-654', 'parent_id' => 100, 'name' => 'Metall', 'is_active' => true],
            ['id' => 100, 'uuid' => 'g-100', 'parent_id' => '', 'name' => 'Asosiy', 'is_active' => true],
        ]])->assertOk()->assertJson(['created' => 2]);
    }

    public function test_adines_pushes_master_data_without_login(): void
    {
        $this->seedGroups();
        $this->assertNull(Group::find(100)->parent_id);
        $this->assertSame(100, Group::find(654)->parent_id);

        $this->postJson('/api/shop', ['data' => [['id' => 5, 'company_id' => 1, 'name' => 'Do‘kon 5', 'price_id' => 2], ['id' => 6, 'company_id' => 1, 'name' => 'Do‘kon 6', 'price_id' => '']]])->assertOk()->assertJson(['created' => 2]);
        $this->assertSame(2, (int) \App\Models\Warehouse::find(5)->price_type_id);
        $this->assertNull(\App\Models\Warehouse::find(6)->price_type_id);

        $this->postJson('/api/price', ['data' => [['id' => 2, 'name' => 'Chakana', 'company_id' => 1]]])->assertOk();
        $this->postJson('/api/v1/sync/counterparties', ['items' => [
            ['id' => 7, 'uuid' => 'p-7', 'type' => 'contragent', 'name' => 'ACME', 'full_name' => 'ACME LLC', 'inn' => '301111111', 'phone' => '1', 'address' => 'x', 'is_vat_payer' => true, 'is_active' => true],
            ['id' => 8, 'uuid' => 'p-8', 'type' => 'employee', 'name' => 'Ali', 'full_name' => 'Ali', 'inn' => '301111111', 'phone' => '', 'address' => '', 'is_vat_payer' => false, 'is_active' => true],
        ]])->assertOk()->assertJson(['created' => 2]);
        $this->assertSame('301111111', Counterparty::find(7)->stir);
        $this->assertNull(Counterparty::find(8)->stir); // repeated STIR is not stored
        $this->assertSame('employee', Counterparty::find(8)->type);

        $this->postJson('/api/v1/sync/products', ['items' => [$this->item(['life' => 30, 'gtin' => ['4780001', '4780002']])]])->assertOk()->assertJson(['created' => 1]);
        $item = Item::find(2656);
        $this->assertSame('2656', $item->code);
        $this->assertSame(654, $item->group_id);
        $this->assertSame(30, $item->shelf_life_days);
        $this->assertSame('шт', $item->unit);
        $this->assertSame(1, Unit::count()); // main/order/report share one unit
        [$byGtin] = Item::resolve('4780002');
        $this->assertSame(2656, $byGtin->id);

        // pack barcode by uuids: a box books 12
        $this->postJson('/api/v1/sync/products', ['items' => [$this->item(['unit_order' => self::BOX])]])->assertOk()->assertJson(['updated' => 1]);
        $this->postJson('/api/v1/sync/barcodes', ['items' => [['product_id' => $item->uuid, 'unit_id' => self::BOX['uuid'], 'barcode' => '999', 'active' => true]]])->assertOk();
        [$packItem, $packQty] = Item::resolve('999');
        $this->assertSame(2656, $packItem->id);
        $this->assertSame(12.0, $packQty);
        $this->postJson('/api/v1/sync/barcodes', ['items' => [['product_id' => $item->uuid, 'unit_id' => self::BOX['uuid'], 'barcode' => '999', 'active' => false]]])->assertOk()->assertJson(['updated' => 1]);
        $this->assertNull(Item::resolve('999'));

        $this->postJson('/api/priceData', ['data' => [['item_id' => 2656, 'price_id' => 2, 'value' => 11000]]])->assertOk();
        $this->assertSame(11000.0, (float) $item->priceFor(2));

        $this->postJson('/api/stock', ['data' => [['shop_id' => 5, 'item_id' => 2656, 'qty' => 5500, 'cost' => 900, 'stock_date' => '2026-10-27']]])->assertOk();
        $this->assertSame(5.5, (float) ItemStock::first()->qty); // sent x 1000
        $this->postJson('/api/stock', ['data' => [['shop_id' => 5, 'item_id' => 2656, 'qty' => 7000, 'cost' => 900, 'stock_date' => '2026-10-28']]])->assertJson(['updated' => 1]);
        $this->assertSame(1, ItemStock::count());
    }

    public function test_max_code_lets_adines_send_only_newer_records(): void
    {
        foreach (['products', 'groups', 'counterparties'] as $e) {
            $this->getJson("/api/v1/sync/{$e}/max-code")->assertOk()->assertJson(['max_code' => 0, 'data' => 0]);
        }

        $this->seedGroups();
        $this->postJson('/api/v1/sync/products', ['items' => [$this->item(['id' => 2995, 'uuid' => 'u-2995'])]])->assertOk();

        $this->getJson('/api/v1/sync/groups/max-code')->assertJson(['max_code' => 654]);
        $this->getJson('/api/v1/sync/products/max-code')->assertJson(['data' => 2995]);
        $this->getJson('/api/v1/sync/nothing/max-code')->assertNotFound();
    }

    public function test_bad_rows_are_reported_and_the_rest_saved(): void
    {
        $this->seedGroups();
        $response = $this->postJson('/api/v1/sync/products', ['items' => [$this->item(), $this->item(['id' => 2, 'uuid' => 'u2', 'name' => ''])]])->assertOk();
        $response->assertJson(['created' => 1])->assertJsonPath('errors.0.index', 1);

        $this->postJson('/api/v1/sync/products', ['items' => [$this->item(['id' => 3, 'uuid' => 'u3', 'group_id' => 99999])]])->assertStatus(422)
            ->assertJsonPath('errors.0.message', 'Unknown group: 99999');
        $this->postJson('/api/stock', ['data' => [['shop_id' => 1, 'item_id' => 2656, 'qty' => 1]]])->assertStatus(422);
        $this->postJson('/api/v1/sync/products', ['items' => []])->assertStatus(422);
        $this->postJson('/api/v1/sync/products', [$this->item(['id' => 4, 'uuid' => 'u4'])])->assertOk(); // a bare array works too
    }

    public function test_calls_are_logged_and_stock_shows_on_the_pda(): void
    {
        $this->seedGroups();
        $this->postJson('/api/shop', ['data' => [['id' => 5, 'name' => 'Do‘kon 5']]]);
        $this->postJson('/api/v1/sync/products', ['items' => [$this->item(), $this->item(['id' => 2, 'name' => ''])]]);
        $this->postJson('/api/stock', ['data' => [['shop_id' => 5, 'item_id' => 2656, 'qty' => 5000]]]);
        $this->postJson('/api/v1/sync/products', ['items' => []]);

        $this->assertDatabaseHas('exchange_logs', ['entity' => 'items', 'rows' => 2, 'created' => 1, 'errors_count' => 1, 'http_status' => 200]);
        $this->assertDatabaseHas('exchange_logs', ['entity' => 'items', 'http_status' => 422, 'errors_count' => 1]);

        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $this->actingAs($admin)->get('/admin/exchange')->assertOk()->assertSee('<td>item-stocks</td>', false);
        $this->actingAs($admin)->get('/admin/exchange?errors=1')->assertOk()->assertDontSee('<td>item-stocks</td>', false);
        $this->actingAs($user)->get('/admin/exchange')->assertForbidden();

        $this->actingAs($user)->get('/directory?q=2656')->assertOk()->assertSee('Qoldiq: 5');
        $doc = \App\Models\Document::create(['type' => 'stocktaking', 'user_id' => $user->id, 'doc_date' => now()]);
        $this->actingAs($user)->get("/docs/{$doc->id}/entry/2656")->assertOk()->assertSee('Qoldiq (Adines)')->assertSee('Do‘kon 5');
    }

    public function test_api_index_lists_endpoints(): void
    {
        $this->getJson('/api')->assertOk()->assertJsonPath('max_rows_per_request', 500)->assertJsonPath('auth', 'none')
            ->assertJsonStructure(['endpoints' => ['products' => ['method', 'url'], 'groups', 'stock', 'counterparties', 'shop'], 'max_code' => ['products']]);
    }

    public function test_max_500_rows_per_request(): void
    {
        $rows = fn (int $n) => array_map(fn ($i) => ['id' => $i, 'name' => "Group {$i}"], range(1, $n));

        $this->postJson('/api/v1/sync/groups', ['items' => $rows(501)])->assertStatus(413);
        $this->assertSame(0, Group::count());
        $this->postJson('/api/v1/sync/groups', ['items' => $rows(500)])->assertOk()->assertJson(['created' => 500]);
    }

    public function test_optional_token_locks_the_api(): void
    {
        config(['onec.api_token' => 'secret']);

        $this->postJson('/api/price', ['data' => [['id' => 1, 'name' => 'S']]])->assertUnauthorized();
        $this->getJson('/api/v1/sync/groups/max-code')->assertUnauthorized();
        $this->postJson('/api/price', ['data' => [['id' => 1, 'name' => 'S']]], ['Authorization' => 'Bearer secret'])->assertOk();
        $this->assertSame('1', PriceType::find(1)->code);
    }
}

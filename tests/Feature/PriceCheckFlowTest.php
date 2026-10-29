<?php

namespace Tests\Feature;

use App\Livewire\Pda\Browse;
use App\Livewire\Pda\Docs\Index as PdaIndex;
use App\Livewire\Pda\Docs\Show as PdaShow;
use App\Models\Document;
use App\Models\Group;
use App\Models\Item;
use App\Models\ItemStock;
use App\Models\PriceType;
use App\Models\Role;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** Price check flow: one start screen (scope), the to-check list of the shop, progress and the remaining items. */
class PriceCheckFlowTest extends TestCase
{
    use RefreshDatabase;

    private function setup_shop(): array
    {
        $retail = PriceType::create(['name' => 'Chakana']);
        $shop = Warehouse::create(['code' => 'A', 'name' => 'Do‘kon A', 'price_type_id' => $retail->id]);
        $milk = Group::create(['id' => 10, 'name' => 'Sut mahsulotlari']);
        $kefir = Group::create(['id' => 11, 'name' => 'Kefir', 'parent_id' => 10]);
        $bread = Group::create(['id' => 20, 'name' => 'Non']);

        $mk = fn ($code, $name, $group) => Item::create(['code' => $code, 'barcode' => '47800000'.$code, 'name' => $name, 'unit' => 'dona', 'price' => 5000, 'group_id' => $group->id, 'is_active' => true]);
        $items = ['sut' => $mk('001', 'Sut', $milk), 'kefir' => $mk('002', 'Kefir', $kefir), 'yogurt' => $mk('003', 'Yogurt', $milk), 'non' => $mk('004', 'Non', $bread)];

        // stock in the shop: milk, kefir and bread are there, yogurt is not (0)
        ItemStock::create(['item_id' => $items['sut']->id, 'warehouse_id' => $shop->id, 'qty' => 4]);
        ItemStock::create(['item_id' => $items['kefir']->id, 'warehouse_id' => $shop->id, 'qty' => 2]);
        ItemStock::create(['item_id' => $items['yogurt']->id, 'warehouse_id' => $shop->id, 'qty' => 0]);
        ItemStock::create(['item_id' => $items['non']->id, 'warehouse_id' => $shop->id, 'qty' => 9]);

        $user = User::factory()->create(['role' => 'user', 'role_id' => Role::where('name', 'PDA operator')->value('id'), 'warehouse_id' => $shop->id]);

        return [$user, $shop, $retail, $milk, $items];
    }

    public function test_one_tap_starts_a_check_for_a_group_of_the_users_shop(): void
    {
        [$user, $shop, $retail, $milk] = $this->setup_shop();

        $page = Livewire::actingAs($user)->test(PdaIndex::class, ['type' => 'pricecheck'])->assertSee('Boshlash')->assertSee('Do‘kon A');

        // a group is required for the group scope
        $page->set('scope', 'group')->call('startCheck')->assertHasErrors('group_id');

        $page->set('group_id', (string) $milk->id)->call('startCheck')->assertRedirect();
        $doc = Document::latest('id')->first();
        $this->assertSame('pricecheck', $doc->type);
        $this->assertSame('group', $doc->scope);
        $this->assertSame($milk->id, $doc->group_id);
        $this->assertSame($shop->id, $doc->warehouse_id);       // the user's shop
        $this->assertSame($retail->id, $doc->price_type_id);    // the price list of that shop
        $this->assertTrue($doc->isDraft());
    }

    public function test_the_to_check_list_is_the_groups_items_in_stock_in_the_shop_with_progress(): void
    {
        [$user, , , $milk, $items] = $this->setup_shop();
        $doc = Document::create(['type' => 'pricecheck', 'user_id' => $user->id, 'doc_date' => now(), 'warehouse_id' => $user->warehouse_id, 'scope' => 'group', 'group_id' => $milk->id]);

        // milk + kefir (a subgroup); yogurt is out of stock and bread is another group
        $this->assertEqualsCanonicalizing([$items['sut']->id, $items['kefir']->id], $doc->checkItemsQuery()->pluck('id')->all());
        $this->assertSame([0, 2], $doc->checkProgress());

        $doc->lines()->create(['item_id' => $items['sut']->id, 'qty' => 1, 'price' => 5000, 'shelf_price' => 5000, 'check_action' => 'confirmed']);
        $this->assertSame([1, 2], $doc->checkProgress());

        // the item screen shows the counter, and the remaining list holds only what is left
        Livewire::actingAs($user)->test(PdaShow::class, ['record' => $doc->id])->assertSee('1 / 2')->assertSee('Qolganlar (1)');
        Livewire::actingAs($user)->test(Browse::class, ['record' => $doc->id, 'remaining' => true])->assertSee('Kefir')->assertDontSee('Sut ')->assertDontSee('Yogurt')->assertDontSee('Non');

        // the whole shop: everything in stock; free scanning has no list
        $all = Document::create(['type' => 'pricecheck', 'user_id' => $user->id, 'doc_date' => now(), 'warehouse_id' => $user->warehouse_id, 'scope' => 'all']);
        $this->assertSame([0, 3], $all->checkProgress());
        $free = Document::create(['type' => 'pricecheck', 'user_id' => $user->id, 'doc_date' => now(), 'scope' => 'free']);
        $this->assertFalse($free->hasCheckList());
        $this->assertSame([0, 0], $free->checkProgress());
    }

    public function test_a_shop_without_stock_data_lists_every_active_item_and_free_scanning_needs_no_shop(): void
    {
        $retail = PriceType::create(['name' => 'Chakana']);
        $shop = Warehouse::create(['code' => 'B', 'name' => 'Do‘kon B', 'price_type_id' => $retail->id]);
        Warehouse::create(['code' => 'C', 'name' => 'Ombor C']); // two warehouses: nothing is preselected
        Item::create(['code' => '1', 'barcode' => '4780000000001', 'name' => 'A', 'unit' => 'dona', 'price' => 1, 'is_active' => true]);
        Item::create(['code' => '2', 'barcode' => '4780000000002', 'name' => 'B', 'unit' => 'dona', 'price' => 1, 'is_active' => true]);
        $doc = Document::create(['type' => 'pricecheck', 'user_id' => User::factory()->create()->id, 'doc_date' => now(), 'warehouse_id' => $shop->id, 'scope' => 'all']);
        $this->assertSame([0, 2], $doc->checkProgress());

        // a user with no shop must pick one for a list; free scanning starts without
        $user = User::factory()->create(['role' => 'user']);
        $page = Livewire::actingAs($user)->test(PdaIndex::class, ['type' => 'pricecheck']);
        $page->set('scope', 'all')->call('startCheck')->assertHasErrors('warehouse_id');
        $page->set('scope', 'free')->set('price_type_id', (string) $retail->id)->call('startCheck')->assertRedirect();
        $this->assertSame('free', Document::latest('id')->first()->scope);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Item;
use App\Models\PriceCheck;
use App\Models\Printer;
use App\Models\PriceType;
use App\Models\User;
use App\Services\OneCClient;
use App\Livewire\Admin\Documents\Show as DocumentShow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Livewire\Pda\Docs\Create as PdaCreate;
use App\Livewire\Pda\Docs\Entry as PdaEntry;
use App\Livewire\Pda\Docs\Show as PdaShow;
use Livewire\Livewire;
use Tests\TestCase;

class PriceCheckPrintTest extends TestCase
{
    use RefreshDatabase;

    /** A completed price check with one wrong tag (print) and one correct (confirmed). */
    private function completedCheck(User $user): Document
    {
        $type = PriceType::create(['name' => 'R']);
        $a = Item::create(['code' => '1', 'barcode' => '111', 'name' => 'Sut', 'unit' => 'dona', 'price' => 1000, 'is_active' => true]);
        $b = Item::create(['code' => '2', 'barcode' => '222', 'name' => 'Non', 'unit' => 'dona', 'price' => 500, 'is_active' => true]);

        Livewire::actingAs($user)->test(PdaCreate::class, ['type' => 'pricecheck'])->set('header.price_type_id', (string) $type->id)->call('save');
        $doc = Document::where('type', 'pricecheck')->firstOrFail();
        $entry = fn ($item, $shelf) => Livewire::actingAs($user)->test(PdaEntry::class, ['record' => $doc->id, 'item' => $item->id])->set('shelf_price', (string) $shelf)->call('save');
        $entry($a, 900); // differs -> print
        $entry($b, 500); // same -> confirmed
        Livewire::actingAs($user)->test(PdaShow::class, ['record' => $doc->id])->call('saveHeader', 'complete');

        return $doc->fresh();
    }

    public function test_price_check_is_not_synced_to_adines(): void
    {
        config(['onec.base_url' => 'http://adines.test']);
        \Illuminate\Support\Facades\Http::fake();
        $user = User::factory()->create(['role' => 'user']);

        $doc = $this->completedCheck($user);

        $this->assertSame(Document::SYNC_NONE, $doc->onec_status);
        $this->assertFalse($doc->syncs());
        $this->assertSame([0, 0], app(OneCClient::class)->pushPending());
        \Illuminate\Support\Facades\Http::assertNothingSent();

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get("/admin/documents/{$doc->id}")->assertOk()->assertDontSee('Adines ga yuborish')->assertSee('Chop etilmagan');
        Livewire::actingAs($admin)->test(DocumentShow::class, ['record' => $doc->id])->call('sync')->assertSet('failure', 'Bu hujjat turi Adines ga yuborilmaydi.');
        $this->actingAs($admin)->get('/admin/documents')->assertOk()->assertSee('Chop etilmagan');
        $this->actingAs($user)->get("/docs/{$doc->id}")->assertOk()->assertSee('Chop etish')->assertSee('(1)');
        $this->actingAs($user)->get('/docs/type/pricecheck')->assertOk()->assertSee('Chop etilmagan');
    }

    public function test_print_button_sends_the_tags_and_marks_the_document_printed(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $admin = User::factory()->create(['role' => 'admin']);
        $doc = $this->completedCheck($user);
        $this->assertSame(1, $doc->print_count);
        $this->assertNull($doc->printed_at);

        // no printer chosen: a readable error, still not printed
        Livewire::actingAs($user)->test(PdaShow::class, ['record' => $doc->id])->call('print')->assertSet('failure', 'Printer tanlanmagan: administrator «Printerlar» bo‘limida sozlashi kerak.');

        $server = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
        $port = (int) substr(strrchr(stream_socket_get_name($server, false), ':'), 1);
        Printer::create(['name' => 'Lab', 'host' => '127.0.0.1', 'port' => $port, 'is_price_tag' => true]);

        Livewire::actingAs($user)->test(PdaShow::class, ['record' => $doc->id])->call('print')->assertSet('failure', null)->assertNotSet('notice', null);
        $conn = stream_socket_accept($server, 2);
        $data = stream_get_contents($conn, -1, -1);
        $this->assertSame(1, substr_count($data, '^XA')); // only the wrong tag
        $this->assertStringContainsString('Sut', $data);
        $this->assertNotNull($doc->fresh()->printed_at);
        $this->assertSame(0, PriceCheck::queued()->count()); // the queue entry is marked printed too

        $this->actingAs($admin)->get("/admin/documents/{$doc->id}")->assertSee('Chop etilgan')->assertSee('Qayta chop etish');
        fclose($conn);
        fclose($server);

        // printer unreachable: error shown, status untouched
        $printed = $doc->fresh()->printed_at;
        Livewire::actingAs($admin)->test(DocumentShow::class, ['record' => $doc->id])->call('print')->assertNotSet('failure', null);
        $this->assertEquals($printed, $doc->fresh()->printed_at);

        Livewire::actingAs(User::factory()->create(['role' => 'user']))->test(PdaShow::class, ['record' => $doc->id])->assertForbidden();
    }

    public function test_marking_the_queue_printed_updates_the_document_status(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $admin = User::factory()->create(['role' => 'admin']);
        $doc = $this->completedCheck($user);

        Livewire::actingAs($admin)->test(\App\Livewire\Admin\PriceTags\Index::class)->call('markPrinted');

        $this->assertNotNull($doc->fresh()->printed_at);
    }

    public function test_other_document_types_still_sync(): void
    {
        $this->assertTrue(Document::syncsType('receiving'));
        $this->assertFalse(Document::syncsType('pricecheck'));
    }

    public function test_a_bluetooth_printer_gets_the_labels_from_the_browser_not_the_server(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $doc = $this->completedCheck($user);
        Printer::create(['name' => 'BT-58', 'kind' => 'bluetooth', 'host' => '', 'is_price_tag' => true]);

        // the print button hands the ZPL to the browser (event) and marks the document printed; nothing is sent from the server
        Livewire::actingAs($user)->test(PdaShow::class, ['record' => $doc->id])->call('print')
            ->assertDispatched('bt-print', fn ($name, $params) => str_contains(json_encode($params), '^XA') && str_contains(json_encode($params), 'BT-58'))
            ->assertSet('failure', null);
        $this->assertNotNull($doc->fresh()->printed_at);

        // completing a document does not try the network for a Bluetooth printer
        $this->assertTrue(Printer::priceTag()->isBluetooth());
        $this->assertSame('Bluetooth', Printer::priceTag()->address);
    }

    public function test_the_pda_printer_page_chooses_a_printer_and_test_prints(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $lan = Printer::create(['name' => 'Kassa LAN', 'host' => '127.0.0.1', 'port' => 1, 'is_price_tag' => true]);
        $bt = Printer::create(['name' => 'Telefon BT', 'kind' => 'bluetooth', 'host' => '']);

        $this->actingAs($user)->get('/')->assertSee(route('module', 'printer'), false); // burger menu entry
        $this->actingAs($user)->get('/printer')->assertOk()->assertSee('Kassa LAN')->assertSee('Telefon BT')->assertSee('RawBT');
        $this->actingAs($user)->get(route('module', 'printer'))->assertRedirect(route('printer')); // open to every role

        // no device record in this test: the price-tag printer (LAN, unreachable) reports a readable error
        Livewire::actingAs($user)->test(\App\Livewire\Pda\PrinterSettings::class)->call('testPrint')->assertNotSet('failure', null)->assertNotDispatched('bt-print');

        // middleware does not run in Livewire tests: bind the device by hand
        $device = \App\Models\Device::create(['uuid' => (string) \Illuminate\Support\Str::uuid(), 'token_hash' => str_repeat('a', 64), 'name' => 'PDA-1']);
        app()->instance('device.current', $device);
        $pda = fn () => Livewire::actingAs($user)->test(\App\Livewire\Pda\PrinterSettings::class);

        $pda()->call('choose', $bt->id)->assertSet('notice', 'Printer tanlandi.');
        $this->assertSame($bt->id, $device->fresh()->printer_id);
        $pda()->call('testPrint')->assertDispatched('bt-print')->assertSet('failure', null);

        $bt->update(['is_active' => false]);
        $pda()->call('choose', $bt->id)->assertStatus(404);
    }

    public function test_one_tap_verdicts_confirm_or_queue_a_new_tag(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $type = PriceType::create(['name' => 'R']);
        $a = Item::create(['code' => '1', 'barcode' => '111', 'name' => 'Sut', 'unit' => 'dona', 'price' => 1000, 'is_active' => true]);
        $b = Item::create(['code' => '2', 'barcode' => '222', 'name' => 'Non', 'unit' => 'dona', 'price' => 500, 'is_active' => true]);
        $doc = Document::create(['type' => 'pricecheck', 'user_id' => $user->id, 'doc_date' => now(), 'price_type_id' => $type->id, 'scope' => 'free']);
        $tap = fn ($item) => Livewire::actingAs($user)->test(PdaEntry::class, ['record' => $doc->id, 'item' => $item->id]);

        $tap($a)->call('verdict', 'wrong')->assertRedirect(route('docs.show', $doc->id));
        $tap($b)->call('verdict', 'ok')->assertRedirect(route('docs.show', $doc->id));

        $this->assertSame('print', $doc->lines()->where('item_id', $a->id)->value('check_action'));
        $this->assertSame('confirmed', $doc->lines()->where('item_id', $b->id)->value('check_action'));
        $tap($a)->call('verdict', 'maybe')->assertStatus(422);
    }

    public function test_the_item_card_shows_tabs_with_prices_stock_and_barcodes(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $retail = PriceType::create(['name' => 'Chakana']);
        $item = Item::create(['code' => '7', 'barcode' => '4780001', 'name' => 'Kefir', 'unit' => 'dona', 'price' => 9000, 'is_active' => true]);
        $item->barcodes()->create(['barcode' => '4780002', 'qty_in_pack' => 12, 'is_active' => true]);
        \App\Models\ItemPrice::create(['item_id' => $item->id, 'price_type_id' => $retail->id, 'price' => 9500]);
        $wh = \App\Models\Warehouse::create(['code' => 'A', 'name' => 'Do‘kon A']);
        \App\Models\ItemStock::create(['item_id' => $item->id, 'warehouse_id' => $wh->id, 'qty' => 5]);

        $this->actingAs($user)->get(route('item', $item->id))->assertOk()
            ->assertSee('Kefir')->assertSee('Chakana')->assertSee('9 500')->assertSee('Do‘kon A')->assertSee('4780002');
        $this->actingAs($user)->get('/')->assertSee('Mahsulotlar');

        Livewire::actingAs($user)->test(\App\Livewire\Pda\ItemCard::class, ['record' => $item->id])
            ->set('code', '4780002')->call('scan')->assertRedirect(route('item', $item->id))
            ->set('code', 'nope')->call('scan')->assertNotSet('failure', null);
    }

    public function test_a_pda_saves_its_own_lan_or_bluetooth_printer(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $device = \App\Models\Device::create(['uuid' => (string) \Illuminate\Support\Str::uuid(), 'token_hash' => str_repeat('b', 64), 'name' => 'PDA-7']);
        app()->instance('device.current', $device);
        $page = fn () => Livewire::actingAs($user)->test(\App\Livewire\Pda\PrinterSettings::class);

        // LAN: an address is required
        $page()->set('kind', 'lan')->set('host', '')->call('saveOwn')->assertHasErrors('host');
        $page()->set('kind', 'lan')->set('host', '192.168.1.50')->set('port', '9100')->call('saveOwn')->assertSet('notice', 'Printer saqlandi.');
        $own = Printer::where('owner_device_id', $device->id)->first();
        $this->assertSame(['lan', '192.168.1.50'], [$own->kind, $own->host]);
        $this->assertSame($own->id, $device->fresh()->printer_id);

        // Bluetooth: the same record switches to the paired device picked on the page
        $page()->set('kind', 'bluetooth')->set('bt_name', '')->call('saveOwn')->assertHasErrors('bt_name');
        $page()->set('kind', 'bluetooth')->set('bt_name', 'ZQ320')->set('bt_address', 'AA:BB:CC:DD:EE:FF')->call('saveOwn');
        $this->assertSame(1, Printer::where('owner_device_id', $device->id)->count());
        $this->assertSame(['bluetooth', 'ZQ320', 'AA:BB:CC:DD:EE:FF'], [$own->fresh()->kind, $own->fresh()->bt_name, $own->fresh()->bt_address]);
        $page()->call('testPrint')->assertDispatched('bt-print', fn ($n, $p) => ($p['address'] ?? null) === 'AA:BB:CC:DD:EE:FF');

        // own printers stay out of the shared list, so no other PDA can pick them
        $this->assertSame(0, Printer::shared()->count());
        $page()->call('choose', $own->id)->assertStatus(404);
    }

    public function test_admin_item_card_prints_or_queues_the_price_tag(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $item = Item::create(['code' => '1', 'barcode' => '111', 'name' => 'Sut', 'unit' => 'dona', 'price' => 1000, 'is_active' => true]);
        $card = fn () => Livewire::actingAs($admin)->test(\App\Livewire\Admin\Items\Form::class, ['record' => $item->id]);

        $card()->call('printTag')->assertSet('failure', 'Narx yorliqlari printeri tanlanmagan (Printerlar bo‘limi).');

        Printer::create(['name' => 'BT', 'kind' => 'bluetooth', 'host' => '', 'is_price_tag' => true]);
        $card()->call('printTag')->assertNotSet('failure', null);

        $card()->call('queueTag')->assertSet('notice', 'Yorliq chop etish navbatiga qo‘shildi.');
        $card()->call('queueTag')->assertNotSet('failure', null); // already queued
        $this->assertSame(1, \App\Models\PriceCheck::queued()->where('item_id', $item->id)->count());
        $card()->set('copies', 0)->call('printTag')->assertHasErrors('copies');
    }

    public function test_group_colour_scheme_overrides_the_theme_colours_and_is_inherited(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $parent = \App\Models\Group::create(['id' => 1, 'name' => 'Oziq-ovqat']);
        $child = \App\Models\Group::create(['id' => 2, 'name' => 'Sut', 'parent_id' => 1]);
        $item = Item::create(['code' => '1', 'barcode' => '111', 'name' => 'Sut', 'unit' => 'dona', 'price' => 1000, 'is_active' => true, 'group_id' => 2]);
        $design = \App\Support\CheckerDesign::merge(null);

        $this->assertSame('', \App\Support\CheckerItem::payload($item->fresh(), $design, null)['scheme_vars']); // default: the theme's colours

        Livewire::actingAs($admin)->test(\App\Livewire\Admin\Groups\Form::class, ['record' => 1])->call('choose', 'forest')->call('save')->assertRedirect(route('admin.groups.index'));
        $vars = \App\Support\CheckerItem::payload($item->fresh(), $design, null)['scheme_vars']; // the subgroup inherits
        $this->assertStringContainsString('--bg:#0b3d2e', $vars);

        Livewire::actingAs($admin)->test(\App\Livewire\Admin\Groups\Form::class, ['record' => 2])->call('choose', 'pink')->call('save');
        $this->assertStringContainsString('--bg:#fce4ec', \App\Support\CheckerItem::payload($item->fresh(), $design, null)['scheme_vars']); // own scheme wins
        Livewire::actingAs($admin)->test(\App\Livewire\Admin\Groups\Form::class, ['record' => 2])->call('choose', 'nope')->assertSet('color_scheme', 'pink'); // unknown keys are ignored

        $this->actingAs($admin)->get('/admin/groups')->assertOk()->assertSee('Oziq-ovqat');
    }

    public function test_user_warehouse_and_price_type_access_from_the_user_form(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $a = \App\Models\Warehouse::create(['code' => 'A', 'name' => 'Do‘kon A']);
        $b = \App\Models\Warehouse::create(['code' => 'B', 'name' => 'Do‘kon B']);
        $c = \App\Models\Warehouse::create(['code' => 'C', 'name' => 'Do‘kon C']);
        $retail = PriceType::create(['name' => 'Chakana']);
        $whole = PriceType::create(['name' => 'Ulgurji']);
        $role = \App\Models\Role::create(['name' => 'Do‘kon', 'permissions' => ['admin.items']]);
        $user = User::factory()->create(['role' => 'user', 'role_id' => $role->id, 'warehouse_id' => $a->id]);

        $form = fn () => Livewire::actingAs($admin)->test(\App\Livewire\Admin\Users\Form::class, ['record' => $user->id]);
        $form()->set('warehouse_ids', [(string) $b->id])->set('price_type_ids', [(string) $retail->id, (string) $whole->id])->call('save');

        $user = $user->fresh();
        $this->assertEqualsCanonicalizing([$a->id, $b->id], $user->accessibleWarehouseIds()); // ticked + own shop
        $this->assertEqualsCanonicalizing([$retail->id, $whole->id], $user->accessiblePriceTypeIds());

        // the form shows them again, and the Ruxsatlar tab lists the role's items
        $form()->assertSet('warehouse_ids', [(string) $b->id])->assertSee('Ruxsatlar')->assertSee('Tovarlar')->assertSee('Do‘kon C');

        // stock of a warehouse without access is hidden (1 + 2, not the 3 of warehouse C)
        $item = Item::create(['code' => '1', 'barcode' => '111', 'name' => 'Sut', 'unit' => 'dona', 'price' => 10, 'is_active' => true]);
        foreach ([$a, $b, $c] as $i => $w) {
            \App\Models\ItemStock::create(['item_id' => $item->id, 'warehouse_id' => $w->id, 'qty' => $i + 1]);
        }
        Livewire::actingAs($user)->test(\App\Livewire\Pda\ItemCard::class, ['record' => $item->id])->assertViewHas('stockTotal', 3.0);

        // nothing ticked and no shop = no restriction
        $free = User::factory()->create(['role' => 'user']);
        $this->assertNull($free->accessibleWarehouseIds());
        $this->assertNull($free->accessiblePriceTypeIds());
    }

    public function test_a_permission_of_the_role_can_be_switched_off_for_one_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $role = \App\Models\Role::create(['name' => 'Menejer', 'permissions' => ['admin.items', 'admin.warehouses', 'pda.pricecheck']]);
        $user = User::factory()->create(['role' => 'user', 'role_id' => $role->id]);
        $form = fn () => Livewire::actingAs($admin)->test(\App\Livewire\Admin\Users\Form::class, ['record' => $user->id]);

        $this->assertTrue($user->fresh()->hasPermission('admin.warehouses'));
        $form()->assertSet('perms', ['admin.items', 'admin.warehouses', 'pda.pricecheck']);

        // untick one: only this user loses it, the role keeps it
        $form()->set('perms', ['admin.items', 'pda.pricecheck'])->call('save');
        $user = $user->fresh();
        $this->assertSame(['admin.warehouses'], $user->denied_permissions);
        $this->assertFalse($user->hasPermission('admin.warehouses'));
        $this->assertTrue($user->hasPermission('admin.items'));
        $this->assertSame(['admin.items', 'admin.warehouses', 'pda.pricecheck'], $role->fresh()->permissions);
        $this->actingAs($user)->get('/admin/warehouses')->assertForbidden();
        $this->actingAs($user)->get('/admin/items')->assertOk();

        // another user of the same role is untouched
        $this->assertTrue(User::factory()->create(['role' => 'user', 'role_id' => $role->id])->hasPermission('admin.warehouses'));

        // a different role switches everything back on
        $other = \App\Models\Role::create(['name' => 'Boshqa', 'permissions' => ['admin.warehouses']]);
        $form()->set('role_id', (string) $other->id)->assertSet('perms', ['admin.warehouses'])->call('save');
        $this->assertNull($user->fresh()->denied_permissions);
        $this->assertTrue($user->fresh()->hasPermission('admin.warehouses'));
    }
}

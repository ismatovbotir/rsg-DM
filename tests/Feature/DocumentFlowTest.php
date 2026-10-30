<?php

namespace Tests\Feature;

use App\Livewire\Admin\Documents\Show as AdminDocumentShow;
use App\Livewire\Admin\PriceTags\Index as PriceTagsIndex;
use App\Livewire\Pda\Browse;
use App\Livewire\Pda\Directory;
use App\Livewire\Pda\Docs\Create;
use App\Livewire\Pda\Docs\Entry;
use App\Livewire\Pda\Docs\Index as DocsIndex;
use App\Livewire\Pda\Docs\Show;
use App\Models\Counterparty;
use App\Models\Document;
use App\Models\Item;
use App\Models\PriceCheck;
use App\Models\PriceType;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class DocumentFlowTest extends TestCase
{
    use RefreshDatabase;

    /** Header of a receiving document (date, counterparty, warehouse) using real master-data rows. */
    private function receivingHeader(): array
    {
        return [
            'doc_date' => '2026-10-26',
            'counterparty_id' => Counterparty::firstOrCreate(['stir' => '301234567'], ['name' => 'ACME'])->id,
            'warehouse_id' => Warehouse::firstOrCreate(['code' => 'MAIN'], ['name' => 'Asosiy ombor'])->id,
        ];
    }

    /** Step 1 the way the PDA does it: fill the header form of a type and save. */
    private function create(User $user, string $type, array $header = [], array $component = [])
    {
        $form = Livewire::actingAs($user)->test(Create::class, ['type' => $type]);

        foreach ($header as $name => $value) {
            $form->set("header.{$name}", (string) $value);
        }
        foreach ($component as $name => $value) {
            $form->set($name, $value);
        }

        return $form->call('save');
    }

    /** Adds a line the way the PDA does: scan/pick opens the entry window, saving it writes the line. */
    private function enter(User $user, Document $doc, string $code, array $extra = [])
    {
        [$item, $qty] = Item::resolve($code);
        $form = Livewire::actingAs($user)->test(Entry::class, ['record' => $doc->id, 'item' => $item->id]);

        foreach ($extra + ['qty' => $qty, 'price' => $item->price] as $name => $value) {
            $form->set($name, (string) $value);
        }

        return $form->call('save');
    }

    private function item(array $attrs = []): Item
    {
        return Item::create($attrs + ['code' => '0001', 'article' => 'SUT-1L', 'barcode' => '4780000000011', 'name' => 'Sut', 'unit' => 'dona', 'price' => 100]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/')->assertOk()->assertSee('Badge QR kodni skanerlang')->assertDontSee('Bosh menyu');
        $this->get('/admin/documents')->assertRedirect('/login');
    }

    public function test_regular_user_cannot_open_admin(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'user']))
            ->get('/admin/documents')->assertForbidden();
    }

    public function test_step_one_header_uses_dropdown_warehouse_and_counterparty_picker(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $header = $this->receivingHeader();
        Warehouse::create(['code' => 'SHOP', 'name' => 'Do‘kon']);

        $this->actingAs($user)->get('/docs/type/receiving/create')->assertOk()
            ->assertSee('<select class="field" wire:model="header.warehouse_id"', false)
            ->assertSee('Asosiy ombor')->assertSee('Do‘kon')
            ->assertSee('wire:model.live.debounce.250ms="cp_q"', false);
        $this->actingAs($user)->get('/docs/type/movement/create')->assertOk()->assertSee('header.warehouse_to_id', false);

        // required header fields are enforced
        $this->create($user, 'receiving')->assertHasErrors(['counterparty_id', 'warehouse_id']);
        $this->assertSame(0, Document::count());

        // picked from the list
        $this->create($user, 'receiving', $header)->assertRedirect();
        $doc = Document::first();
        $this->assertSame('draft', $doc->status);
        $this->assertSame('ACME', $doc->counterparty->name);
        $this->assertStringStartsWith('REC-', $doc->number);
    }

    public function test_counterparty_can_be_typed_by_stir_or_name(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $header = $this->receivingHeader();
        unset($header['counterparty_id']);

        $this->create($user, 'receiving', $header, ['cp_q' => '301-234-567'])->assertRedirect();
        $this->assertSame('ACME', Document::latest('id')->first()->counterparty->name);

        $this->create($user, 'receiving', $header, ['cp_q' => 'ACME'])->assertRedirect();
        $this->assertSame(2, Document::count());

        $this->create($user, 'receiving', $header, ['cp_q' => 'Noma’lum MCHJ'])->assertHasErrors('counterparty_id');
        $this->assertSame(2, Document::count());

        // an inactive counterparty cannot be picked by typing either
        Counterparty::where('stir', '301234567')->update(['is_active' => false]);
        $this->create($user, 'receiving', $header, ['cp_q' => '301234567'])->assertHasErrors('counterparty_id');
    }

    public function test_counterparty_picker_searches_name_and_stir(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        Counterparty::create(['name' => 'ACME MCHJ', 'stir' => '301234567']);
        $beta = Counterparty::create(['name' => 'Beta Savdo', 'stir' => '302345678']);
        Counterparty::create(['name' => 'Gamma yopiq', 'stir' => '303456789', 'is_active' => false]);

        $form = Livewire::actingAs($user)->test(Create::class, ['type' => 'receiving']);
        $form->set('cp_q', 'acme')->assertSee('ACME MCHJ')->assertSee('STIR 301234567')->assertDontSee('Beta Savdo');
        $form->set('cp_q', '3023')->assertSee('Beta Savdo')->assertDontSee('ACME MCHJ');

        // the "Tanlash" button lists everyone active; picking fills the field
        $form->set('cp_q', '')->call('toggleCpList')->assertSee('ACME MCHJ')->assertSee('Beta Savdo')->assertDontSee('Gamma yopiq');
        $form->call('pickCounterparty', $beta->id)->assertSet('header.counterparty_id', (string) $beta->id)->assertSet('cp_q', 'Beta Savdo')->assertSet('cp_list', false);
    }

    public function test_movement_needs_two_different_warehouses(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $a = Warehouse::create(['code' => 'A', 'name' => 'A ombor']);
        $b = Warehouse::create(['code' => 'B', 'name' => 'B ombor']);

        $this->create($user, 'movement', ['warehouse_id' => $a->id, 'warehouse_to_id' => $a->id])->assertHasErrors('warehouse_to_id');
        $this->create($user, 'movement', ['warehouse_id' => $a->id, 'warehouse_to_id' => $b->id])->assertRedirect();
        $this->assertSame($b->id, Document::first()->warehouse_to_id);
    }

    public function test_step_two_scan_browse_edit_and_complete(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $item = $this->item();
        $other = $this->item(['code' => '0002', 'article' => 'NON-01', 'barcode' => null, 'name' => 'Non', 'price' => 50]);
        $doc = Document::create(['type' => 'receiving', 'user_id' => $user->id] + $this->receivingHeader());
        $screen = fn () => Livewire::actingAs($user)->test(Show::class, ['record' => $doc->id]);

        // the items screen has the shared scan bar, a browse button and the header drawer with its buttons
        $this->actingAs($user)->get("/docs/{$doc->id}")->assertOk()
            ->assertSee('Tanlash')->assertSee('wire:model="code"', false)
            ->assertSee('Saqlash va chiqish')->assertSee('Bekor qilish')->assertSee('Yakunlash va saqlash')
            ->assertSee('id="docExit"', false)->assertSee(route('docs.index', 'receiving'), false);

        // completing an empty document is refused
        $screen()->call('saveHeader', 'complete')->assertHasErrors('lines');

        // scan by barcode, code and article opens the entry window; the second scan increments; price defaults from the item
        $screen()->set('code', '4780000000011')->call('scan')->assertRedirect(route('docs.entry', ['record' => $doc->id, 'item' => $item->id, 'qty' => 1]));
        $this->assertSame(0, $doc->lines()->count());
        $this->enter($user, $doc, '4780000000011');
        $this->enter($user, $doc, 'SUT-1L');
        $screen()->set('code', 'nope')->call('scan')->assertSet('failure', 'Tovar topilmadi: nope');
        $line = $doc->lines()->where('item_id', $item->id)->first();
        $this->assertEquals(2, $line->qty);
        $this->assertEquals(100, $line->price);

        // browse: search by name / article, then add from the list
        Livewire::actingAs($user)->test(Browse::class, ['record' => $doc->id])
            ->set('q', 'NON-01')->assertSee('Non')->assertDontSee('Sut')
            ->call('pick', $other->id)->assertRedirect(route('docs.entry', ['record' => $doc->id, 'item' => $other->id]));
        $this->enter($user, $doc, $other->code);
        $this->assertSame(2, $doc->lines()->count());

        // the list shows qty as information; the entry page (?line=) corrects it (replaces, does not add)
        $screen()->assertDontSee('wire:change="updateQty', false)->assertSee('O‘zgartirish');
        $this->actingAs($user)->get(route('docs.entry', [$doc->id, $item->id]).'?line='.$line->id)->assertOk()->assertSee('Tahrirlash');
        Livewire::actingAs($user)->withQueryParams(['line' => $line->id])->test(\App\Livewire\Pda\Docs\Entry::class, ['record' => $doc->id, 'item' => $item->id])
            ->assertSet('qty', '2')->set('qty', '7')->call('save')->assertRedirect(route('docs.show', $doc->id));
        $this->assertEquals(7, $line->fresh()->qty);
        $line->update(['qty' => 2]);

        // receiving lets the operator edit both qty and price
        $screen()->call('updateQty', $line->id, '5')->call('updatePrice', $line->id, '90');
        $this->assertEquals(5, $line->fresh()->qty);
        $this->assertEquals(90, $line->fresh()->price);
        $screen()->call('updateQty', $line->id, '0')->assertHasErrors('qty'); // not below the minimum
        $this->assertEquals(5, $line->fresh()->qty);

        // drawer: saving a draft keeps it editable and updates the header
        $screen()->set('header.comment', 'Izoh')->call('saveHeader', 'draft')->assertRedirect(route('docs.index', 'receiving'));
        $this->assertSame('draft', $doc->fresh()->status);
        $this->assertSame('Izoh', $doc->fresh()->comment);

        // required header fields are enforced when saving too
        $screen()->set('header.warehouse_id', '')->call('saveHeader', 'complete')->assertHasErrors('warehouse_id');
        $this->assertSame('draft', $doc->fresh()->status);

        $screen()->call('saveHeader', 'complete')->assertRedirect(route('docs.index', 'receiving'));
        $this->assertSame('completed', $doc->fresh()->status);
        $this->assertSame('pending', $doc->fresh()->onec_status);

        // completed documents are read-only
        $screen()->set('code', '0001')->call('scan')->assertForbidden();
        $screen()->call('saveHeader')->assertForbidden();
        $screen()->call('cancelDocument')->assertForbidden();
        $screen()->call('removeLine', $line->id)->assertForbidden();
        $this->actingAs($user)->get("/docs/{$doc->id}")->assertOk()->assertSee('ACME')->assertSee('Asosiy ombor');
    }

    public function test_editable_columns_depend_on_document_type(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $this->item();

        // price changes: only the price is editable; rescanning does not bump the quantity
        $prices = Document::create(['type' => 'prices', 'user_id' => $user->id, 'doc_date' => '2026-10-26']);
        $this->enter($user, $prices, '0001', ['price' => 100]);
        $this->enter($user, $prices, '0001', ['price' => 100]);
        $line = $prices->lines()->first();
        $this->assertEquals(1, $line->qty);
        $screen = fn ($doc) => Livewire::actingAs($user)->test(Show::class, ['record' => $doc->id]); // a refused call ends that component instance
        $screen($prices)->call('updateQty', $line->id, '9')->assertForbidden();
        $screen($prices)->call('updatePrice', $line->id, '150');
        $this->assertEquals(1, $line->fresh()->qty);
        $this->assertEquals(150, $line->fresh()->price);

        // stock taking: only the quantity is editable
        $count = Document::create(['type' => 'stocktaking', 'user_id' => $user->id, 'doc_date' => '2026-10-26', 'warehouse_id' => Warehouse::create(['name' => 'A'])->id]);
        $this->enter($user, $count, '0001');
        $line = $count->lines()->first();
        $screen($count)->call('updateQty', $line->id, '7');
        $screen($count)->call('updatePrice', $line->id, '1')->assertForbidden();
        $this->assertEquals(7, $line->fresh()->qty);
        $this->assertEquals(100, $line->fresh()->price);
    }

    public function test_cancel_discards_a_draft_with_its_lines(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $this->item();
        $doc = Document::create(['type' => 'receiving', 'user_id' => $user->id] + $this->receivingHeader());
        $this->enter($user, $doc, '0001');

        Livewire::actingAs($user)->test(Show::class, ['record' => $doc->id])->call('cancelDocument')->assertRedirect(route('docs.index', 'receiving'));

        $this->assertDatabaseMissing('documents', ['id' => $doc->id]);
        $this->assertDatabaseCount('document_lines', 0);
    }

    public function test_removing_a_line_only_touches_the_open_document(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $this->item();
        $mine = Document::create(['type' => 'receiving', 'user_id' => $user->id] + $this->receivingHeader());
        $theirs = Document::create(['type' => 'receiving', 'user_id' => $user->id] + $this->receivingHeader());
        $this->enter($user, $mine, '0001');
        $this->enter($user, $theirs, '0001');

        $theirLine = $theirs->lines()->first();
        $screen = fn () => Livewire::actingAs($user)->test(Show::class, ['record' => $mine->id]);
        $screen()->call('removeLine', $theirLine->id)->assertNotFound(); // a line of another document is not reachable from here
        $this->assertSame(1, $theirs->lines()->count());

        $screen()->call('removeLine', $mine->lines()->first()->id);
        $this->assertSame(0, $mine->lines()->count());
    }

    public function test_other_users_cannot_open_someones_document(): void
    {
        $doc = Document::create(['type' => 'receiving', 'user_id' => User::factory()->create()->id]);
        $stranger = User::factory()->create(['role' => 'user']);

        $this->actingAs($stranger)->get("/docs/{$doc->id}")->assertForbidden();
        Livewire::actingAs($stranger)->test(Show::class, ['record' => $doc->id])->assertForbidden();
    }

    public function test_document_list_shows_own_documents_with_status(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $mine = Document::create(['type' => 'receiving', 'user_id' => $user->id] + $this->receivingHeader());
        Document::create(['type' => 'receiving', 'user_id' => User::factory()->create()->id]);

        Livewire::actingAs($user)->test(DocsIndex::class, ['type' => 'receiving'])->assertSee($mine->number)->assertSee('Qoralama')->assertSee('+ Yangi hujjat')
            ->assertViewHas('docs', fn ($docs) => $docs->count() === 1);
        Livewire::actingAs($user)->test(DocsIndex::class, ['type' => 'nope'])->assertNotFound();
    }

    public function test_admin_syncs_completed_document_to_1c(): void
    {
        config(['onec.base_url' => 'http://1c.test/hs']);
        Http::fake(['1c.test/*' => Http::response(['ok' => true])]);

        $admin = User::factory()->create(['role' => 'admin']);
        $item = $this->item();
        $doc = Document::create(['type' => 'receiving', 'user_id' => $admin->id, 'status' => 'completed', 'completed_at' => now()] + $this->receivingHeader());
        $doc->lines()->create(['item_id' => $item->id, 'qty' => 3, 'price' => 12]);

        Livewire::actingAs($admin)->test(AdminDocumentShow::class, ['record' => $doc->id])->call('sync')->assertSet('notice', 'Adines ga yuborildi.');

        $this->assertSame('synced', $doc->fresh()->onec_status);
        Http::assertSent(fn ($r) => $r->url() === 'http://1c.test/hs/documents'
            && $r['counterparty'] === 'ACME' && $r['counterparty_stir'] === '301234567'
            && $r['warehouse'] === 'Asosiy ombor' && $r['date'] === '2026-10-26'
            && $r['lines'][0]['qty'] === 3.0 && $r['lines'][0]['price'] === 12.0);
    }

    public function test_failed_1c_push_is_recorded_and_retryable(): void
    {
        config(['onec.base_url' => 'http://1c.test/hs']);
        Http::fake(['1c.test/*' => Http::response('boom', 500)]);

        $admin = User::factory()->create(['role' => 'admin']);
        $doc = Document::create(['type' => 'receiving', 'user_id' => $admin->id, 'status' => 'completed', 'completed_at' => now()]);

        Livewire::actingAs($admin)->test(AdminDocumentShow::class, ['record' => $doc->id])->call('sync')->assertSet('notice', null)->assertNotSet('failure', null);

        $this->assertSame('error', $doc->fresh()->onec_status);
        $this->assertNotEmpty($doc->fresh()->onec_error);
    }

    public function test_every_scan_screen_shares_the_scan_input_and_browse_button(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $this->item();
        $doc = Document::create(['type' => 'receiving', 'user_id' => $user->id] + $this->receivingHeader());

        $pc = Document::create(['type' => 'pricecheck', 'user_id' => $user->id, 'doc_date' => now(), 'price_type_id' => PriceType::create(['name' => 'R'])->id]);
        foreach (["/docs/{$doc->id}", "/docs/{$pc->id}", '/directory'] as $url) {
            $this->actingAs($user)->get($url)->assertOk()->assertSee('class="btn browse"', false)->assertSee('Tanlash');
        }

        // the directory: scan or type, and the browse page for it links back to the lookup with the picked item
        Livewire::actingAs($user)->test(Directory::class)->set('q', 'Sut')->call('search')->assertSee('Sut')->assertSee('0001'); // a name lists matches
        Livewire::actingAs($user)->test(Directory::class)->set('q', '0001')->call('search')->assertRedirect(route('item', Item::first()->id)); // a scan opens the card
        Livewire::actingAs($user)->test(Directory::class)->set('q', 'zzz')->call('search')->assertSee('Topilmadi');
        $item = Item::first();
        Livewire::actingAs($user)->test(Browse::class)->set('q', 'SUT-1L')->assertSee('Sut')->call('pick', $item->id)->assertRedirect(route('item', $item->id));
    }

    public function test_price_check_document_confirm_and_print_queue(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $admin = User::factory()->create(['role' => 'admin']);
        $item = $this->item(['price' => 12000]);
        $wholesale = PriceType::create(['name' => 'Ulgurji']);
        PriceType::create(['name' => 'Chakana']);
        $item->prices()->create(['price_type_id' => $wholesale->id, 'price' => 9000]);

        // step 1: the header needs a price list; the date is always today
        $this->actingAs($user)->get('/docs/type/pricecheck/create')->assertOk()->assertSee('Chakana')->assertSee('Ulgurji');
        $this->create($user, 'pricecheck', ['doc_date' => '2001-01-01'])->assertHasErrors('price_type_id');
        $this->create($user, 'pricecheck', ['price_type_id' => $wholesale->id]);
        $doc = Document::where('type', 'pricecheck')->firstOrFail();
        $this->assertSame(now()->toDateString(), $doc->doc_date->toDateString());
        $screen = fn () => Livewire::actingAs($user)->test(Show::class, ['record' => $doc->id]);
        $entry = fn ($i) => Livewire::actingAs($user)->test(Entry::class, ['record' => $doc->id, 'item' => $i->id]);

        // step 2: scanning opens the confirmation page with the price of the chosen list (base price as fallback); no line yet
        $screen()->set('code', '4780000000011')->call('scan')->assertRedirect(route('docs.entry', ['record' => $doc->id, 'item' => $item->id, 'qty' => 1]));
        $this->assertSame(0, $doc->lines()->count());
        $this->actingAs($user)->get("/docs/{$doc->id}/entry/{$item->id}")->assertOk()->assertSee('9 000')->assertSee('Javondagi narx');
        $entry($item)->call('save')->assertHasErrors('shelf_price');

        // shelf price differs -> the line goes to the print list
        $entry($item)->set('shelf_price', '12000')->call('save')->assertRedirect(route('docs.show', $doc->id));
        $line = $doc->lines()->firstOrFail();
        $this->assertSame(9000.0, (float) $line->price);
        $this->assertSame(12000.0, (float) $line->shelf_price);
        $this->assertSame('print', $line->check_action);
        $this->actingAs($user)->get("/docs/{$doc->id}")->assertOk()->assertSee('9 000')->assertSee('Javondagi narx: 12 000');

        // shelf price matches -> confirmed; browse "select" goes through the same page
        $other = $this->item(['code' => '0002', 'barcode' => '4780000000028', 'name' => 'Non', 'price' => 4000]);
        Livewire::actingAs($user)->test(Browse::class, ['record' => $doc->id])->call('pick', $other->id)->assertRedirect(route('docs.entry', ['record' => $doc->id, 'item' => $other->id]));
        $entry($other)->set('shelf_price', '4000')->call('save');
        $this->assertSame(4000.0, (float) $doc->lines()->where('item_id', $other->id)->value('price'));
        $this->assertSame('confirmed', $doc->lines()->where('item_id', $other->id)->value('check_action'));

        // the operator can change a verdict by hand
        $screen()->call('setCheck', $line->id, 'confirmed');
        $this->assertSame('confirmed', $line->fresh()->check_action);
        $screen()->call('setCheck', $line->id, 'print');

        // completing queues the "print" ones
        $screen()->call('saveHeader', 'complete');
        $this->assertSame(Document::COMPLETED, $doc->fresh()->status);
        $this->assertSame(1, PriceCheck::queued()->count());
        $this->assertDatabaseHas('price_checks', ['item_id' => $other->id, 'action' => 'confirmed', 'document_id' => $doc->id]);

        $this->actingAs($admin)->get('/admin/price-tags')->assertOk()->assertSee('Sut');
        $this->actingAs($admin)->get('/admin/price-tags/print')->assertOk()->assertSee('Sut');
        $this->actingAs($user)->get('/admin/price-tags')->assertForbidden();
        $this->actingAs($admin)->get('/admin/price-types')->assertOk()->assertSee('Ulgurji');
        Livewire::actingAs($admin)->test(PriceTagsIndex::class)->call('markPrinted');
        $this->assertSame(0, PriceCheck::queued()->count());
    }

    public function test_entry_window_lines_are_unique_per_item_and_production_date(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $item = $this->item(['shelf_life_days' => 30]);
        $doc = Document::create(['type' => 'receiving', 'user_id' => $user->id] + $this->receivingHeader());

        // the window shows qty, price and production date; nothing is written until it is saved
        $this->actingAs($user)->get("/docs/{$doc->id}/entry/{$item->id}")->assertOk()
            ->assertSee('wire:model="qty"', false)->assertSee('wire:model="price"', false)->assertSee('production_date', false);
        $this->assertSame(0, $doc->lines()->count());

        // no date, same date twice (adds), another date (new line)
        $this->enter($user, $doc, '0001', ['qty' => 2, 'price' => 100])->assertRedirect(route('docs.show', $doc->id));
        $this->enter($user, $doc, '0001', ['qty' => 3, 'price' => 100]);
        $this->enter($user, $doc, '0001', ['qty' => 4, 'price' => 90, 'production_date' => '2026-10-01']);
        $this->enter($user, $doc, '0001', ['qty' => 1, 'price' => 90, 'production_date' => '2026-10-01']);
        $this->enter($user, $doc, '0001', ['qty' => 6, 'price' => 90, 'production_date' => '2026-10-05']);
        $this->assertSame(3, $doc->lines()->count());
        $this->assertEquals(5, $doc->lines()->whereNull('production_date')->value('qty'));
        $dated = $doc->lines()->whereDate('production_date', '2026-10-01')->firstOrFail();
        $this->assertEquals(5, $dated->qty);
        $this->assertSame('2026-10-31', $dated->expiry_date->toDateString()); // production date + shelf life days

        // the window shows the expiry date as soon as a production date is picked
        Livewire::actingAs($user)->test(Entry::class, ['record' => $doc->id, 'item' => $item->id])
            ->set('production_date', '2026-10-01')->assertSee('Yaroqlilik muddati: 31.10.2026');

        // validation: qty required, no future production date
        $this->enter($user, $doc, '0001', ['qty' => '', 'price' => 1])->assertHasErrors('qty');
        $this->enter($user, $doc, '0001', ['qty' => 1, 'price' => 1, 'production_date' => now()->addDay()->toDateString()])->assertHasErrors('production_date');
        $this->assertSame(3, $doc->lines()->count());
    }

    public function test_header_date_and_counterparty_are_locked_after_creation(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $header = $this->receivingHeader();
        $this->create($user, 'receiving', ['doc_date' => '2001-01-01'] + $header);
        $doc = Document::firstOrFail();
        $this->assertSame(now()->toDateString(), $doc->doc_date->toDateString());

        // tampering with the locked fields from the drawer changes nothing
        $other = Counterparty::create(['name' => 'Boshqa', 'stir' => '309999999']);
        Livewire::actingAs($user)->test(Show::class, ['record' => $doc->id])
            ->set('header.doc_date', '2001-01-01')->set('header.counterparty_id', (string) $other->id)->call('saveHeader', 'draft');
        $doc->refresh();
        $this->assertSame($header['counterparty_id'], $doc->counterparty_id);
        $this->assertSame(now()->toDateString(), $doc->doc_date->toDateString());
    }

    public function test_admin_pages_render_and_manage_items(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/admin/documents')->assertOk();
        $this->actingAs($admin)->get('/admin/items')->assertOk();
        // item data comes from Adines: no create route, the card only shows it
        $this->actingAs($admin)->get('/admin/items/create')->assertNotFound();
        $item = \App\Models\Item::create(['code' => 'A1', 'article' => 'ART-A1', 'name' => 'Test', 'unit' => 'dona', 'price' => 5, 'is_active' => true]);
        $this->actingAs($admin)->get("/admin/items/{$item->id}/edit")->assertOk()->assertSee('ART-A1')->assertDontSee('name="name"', false);
    }
}

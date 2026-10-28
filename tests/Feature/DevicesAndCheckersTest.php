<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\Item;
use App\Models\PriceCheck;
use App\Models\PriceChecker;
use App\Models\PriceType;
use App\Models\Printer;
use App\Models\User;
use App\Services\PriceTagPrinter;
use App\Livewire\Admin\Checkers\Designer as CheckerDesigner;
use App\Livewire\Admin\Checkers\Form as CheckerForm;
use App\Livewire\Admin\Checkers\Index as CheckerIndex;
use App\Support\CheckerDesign;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Livewire\Admin\Devices\Form as DeviceForm;
use App\Livewire\Admin\Items\Form as ItemForm;
use App\Livewire\Admin\Printers\Form as PrinterForm;
use App\Livewire\Admin\Printers\Index as PrinterIndex;
use Livewire\Livewire;
use Tests\TestCase;

class DevicesAndCheckersTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function item(array $over = []): Item
    {
        return Item::create($over + ['code' => '1', 'barcode' => '4780000000011', 'name' => 'Sut', 'unit' => 'dona', 'price' => 12000, 'is_active' => true]);
    }

    public function test_price_checker_gets_a_token_on_create_and_the_qr_can_be_previewed_and_regenerated(): void
    {
        $admin = $this->admin();
        $retail = PriceType::create(['name' => 'Chakana']);
        $this->item()->prices()->create(['price_type_id' => $retail->id, 'price' => 11000]);

        // create: the token is generated at once
        $this->actingAs($admin);
        Livewire::test(CheckerForm::class)
            ->set('name', 'Kirish')->set('price_type_id', (string) $retail->id)
            ->call('save')->assertRedirect();
        $checker = PriceChecker::firstOrFail();
        $token = $checker->token;
        $this->assertSame(48, strlen($token));
        $this->assertNotSame($token, $checker->getRawOriginal('token')); // encrypted at rest
        $this->assertSame(hash('sha256', $token), $checker->token_hash);

        // edit: the QR code (an SVG of the activation URL) is shown again, any time
        $this->get(route('admin.checkers.edit', $checker))->assertOk()->assertSee('<svg', false)->assertSee('/pricechecker/activate/'.$token, false);

        // regenerate: a new token, the old one stops working
        $component = Livewire::test(CheckerForm::class, ['checker' => $checker])->call('regenerate');
        $new = $checker->fresh()->token;
        $this->assertNotSame($token, $new);
        $component->assertSee('/pricechecker/activate/'.$new, false)->assertDontSee($token, false);

        auth()->logout();

        // a terminal without a token only sees the "scan the QR" page
        $this->get('/pricechecker')->assertRedirect(route('checker.activate.show'));
        $this->getJson('/pricechecker/price?code=4780000000011')->assertUnauthorized();
        $this->get('/pricechecker/activate')->assertOk()->assertSee('Terminalni faollashtiring');

        // old / wrong tokens do nothing
        $this->get('/pricechecker/activate/'.$token)->assertRedirect(route('checker.activate.show'));
        $this->get('/pricechecker/activate/wrong')->assertRedirect(route('checker.activate.show'));
        $this->get('/pricechecker')->assertRedirect(route('checker.activate.show'));

        // the QR URL activates; the same token also works on another terminal and via a wedge scanner typing the URL
        $this->get('/pricechecker/activate/'.$new)->assertRedirect(route('checker.screen'));
        $this->get('/pricechecker')->assertOk()->assertSee('Kirish');
        $this->getJson('/pricechecker/price?code=4780000000011')->assertOk()->assertJson(['found' => true, 'name' => 'Sut', 'price' => 11000.0, 'code' => '1']);
        $this->getJson('/pricechecker/price?code=nope')->assertNotFound();
        $this->assertNotNull($checker->fresh()->activated_at);

        $this->flushSession();
        $this->post('/pricechecker/activate', ['code' => url('/pricechecker/activate/'.$new)])->assertRedirect(route('checker.screen'));
        $this->get('/pricechecker')->assertOk();

        // regenerating signs the terminal out
        $checker->fresh()->regenerateToken();
        $this->get('/pricechecker')->assertRedirect(route('checker.activate.show'));
    }

    public function test_deactivated_checker_stops_working_and_non_admin_cannot_manage(): void
    {
        $checker = PriceChecker::create(['name' => 'X', 'is_active' => true]);
        $this->get('/pricechecker/activate/'.$checker->regenerateToken());
        $this->get('/pricechecker')->assertOk();

        $checker->update(['is_active' => false]);
        $this->get('/pricechecker')->assertRedirect(route('checker.activate.show'));

        $user = User::factory()->create(['role' => 'user']);
        $this->actingAs($user)->get('/admin/checkers')->assertForbidden();
        Livewire::actingAs($user)->test(CheckerIndex::class)->assertForbidden();
    }

    public function test_the_screen_design_is_edited_with_a_live_preview_and_reaches_the_terminal(): void
    {
        $admin = $this->admin();
        $this->item();
        $checker = PriceChecker::create(['name' => 'Zal', 'is_active' => true]);
        $token = $checker->regenerateToken();
        $before = $checker->fresh()->screenVersion();

        $designer = Livewire::actingAs($admin)->test(CheckerDesigner::class, ['checkerId' => $checker->id]);
        $designer->assertSee('data-state="result"', false)->assertSee('Sut');

        // every change shows in the preview at once (no save needed), and only validated values reach the CSS
        $designer->set('design.bg', '#112233')->assertSee('--bg:#112233', false);
        $designer->set('design.bg', 'red;background:url(x)')->assertDontSee('url(x)', false)->assertSee('--bg:'.CheckerDesign::DEFAULTS['bg'], false);
        $designer->set('design.bg', '#112233')->set('design.hint_text', 'Skanerlang')->set('design.layout', 'split')->set('design.rotation', 90)
            ->set('state', 'idle')->assertSee('Skanerlang')->assertSee('data-layout="split"', false)->assertSee('rotate(90deg)', false);
        $this->assertSame($before, $checker->fresh()->screenVersion()); // not published yet

        $designer->call('save')->assertSee('Dizayn saqlandi');
        $checker = $checker->fresh();
        $this->assertSame('#112233', $checker->screen['bg']);
        $this->assertNotSame($before, $checker->screenVersion());

        // the terminal renders the saved design and notices when it changes
        auth()->logout();
        $this->get('/pricechecker/activate/'.$token);
        $this->get('/pricechecker')->assertOk()->assertSee('--bg:#112233', false)->assertSee('Skanerlang')->assertSee('data-layout="split"', false);
        $this->getJson('/pricechecker/version')->assertJson(['v' => $checker->screenVersion()]);

        // resetting goes back to the defaults; a design can be copied from another checker
        $other = PriceChecker::create(['name' => 'Boshqa', 'is_active' => true, 'design' => ['bg' => '#abcdef']]);
        Livewire::actingAs($admin)->test(CheckerDesigner::class, ['checkerId' => $checker->id])
            ->call('copyFrom', $other->id)->assertSet('design.bg', '#abcdef')
            ->call('resetDefaults')->assertSet('design.bg', CheckerDesign::DEFAULTS['bg']);
    }
    public function test_only_one_printer_prints_price_tags_and_pdas_can_get_their_own(): void
    {
        $admin = $this->admin();
        $payload = ['host' => '192.168.1.50', 'port' => 9100, 'label_width_mm' => 58, 'label_height_mm' => 40, 'is_active' => 1];

        $this->actingAs($admin);
        $make = fn (array $values) => Livewire::test(PrinterForm::class)->set('name', $values['name'])->set('host', $values['host'] ?? '192.168.1.50')
            ->set('is_price_tag', $values['is_price_tag'] ?? false)->call('save');

        $make(['name' => 'A', 'is_price_tag' => true])->assertRedirect('/admin/printers');
        $make(['name' => 'B', 'is_price_tag' => true, 'host' => 'printer-b.lan']);
        $this->assertSame('B', Printer::priceTag()->name);
        $this->assertSame(1, Printer::where('is_price_tag', true)->count());
        $make(['name' => 'C', 'host' => 'bad host!'])->assertHasErrors('host');

        $device = Device::create(['uuid' => 'u1', 'token_hash' => 'h1', 'keyboard_mode' => 'auto']);
        $printerA = Printer::where('name', 'A')->first();
        Livewire::test(DeviceForm::class, ['record' => $device->id])->set('printer_id', (string) $printerA->id)->call('save')->assertRedirect('/admin/devices');
        $this->assertSame($printerA->id, $device->fresh()->printer_id);

        $this->actingAs($admin)->get('/admin/printers')->assertOk()->assertSee('printer-b.lan');
        $this->actingAs($admin)->get('/admin/devices')->assertOk()->assertSee('A');
        Livewire::test(PrinterIndex::class)->call('delete', $printerA->id);
        $this->assertNull($device->fresh()->printer_id);
    }

    public function test_queued_tags_are_sent_to_the_printer_over_tcp(): void
    {
        $server = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
        $this->assertNotFalse($server, (string) $error);
        $port = (int) substr(strrchr(stream_socket_get_name($server, false), ':'), 1);

        $printer = Printer::create(['name' => 'Lab', 'host' => '127.0.0.1', 'port' => $port, 'is_price_tag' => true]);
        $item = $this->item(['name' => 'Non ^ issiq']);
        PriceCheck::create(['item_id' => $item->id, 'user_id' => User::factory()->create()->id, 'price' => 12000, 'action' => 'print']);

        $this->assertSame(1, app(PriceTagPrinter::class)->printQueued($printer));

        $conn = stream_socket_accept($server, 2);
        $data = stream_get_contents($conn, -1, -1);
        $this->assertStringContainsString('^XA', $data);
        $this->assertStringContainsString('12 000', $data);
        $this->assertStringContainsString('Non   issiq', $data); // control characters stripped
        $this->assertSame(0, PriceCheck::queued()->count());

        // unreachable printer: readable error, tags stay queued
        PriceCheck::create(['item_id' => $item->id, 'user_id' => User::first()->id, 'price' => 1, 'action' => 'print']);
        fclose($conn);
        fclose($server);
        $this->expectExceptionMessage('Printerga ulanib bo‘lmadi');
        app(PriceTagPrinter::class)->printQueued($printer);
    }

    public function test_admin_sidebar_groups_and_item_tabs_with_barcode_table(): void
    {
        $admin = $this->admin();
        $item = $this->item();
        $item->barcodes()->create(['barcode' => '999', 'qty_in_pack' => 12]);
        $item->barcodes()->create(['barcode' => '888', 'qty_in_pack' => 1, 'is_active' => false]);

        $page = $this->actingAs($admin)->get("/admin/items/{$item->id}/edit")->assertOk();
        $page->assertSee("tab = 'barcodes'", false)->assertSee("tab = 'stock'", false)
            ->assertSee('999')->assertSee('888');
        Livewire::test(ItemForm::class, ['record' => $item->id])->assertSet('barcodes.0.barcode', '999')->assertSet('barcodes.1.barcode', '888')->assertSet('barcodes.1.is_active', false);

        $page->assertSee('Hujjatlar')->assertSee('Qurilmalar')->assertSee('Ma’lumotnomalar', false);
        foreach (\App\Models\Document::types() as $key => $title) {
            $page->assertSee("documents?type={$key}", false);
        }

        // inactive pack barcode is not scannable, the active one books its quantity
        $this->assertNull(Item::resolve('888'));
        $this->assertSame(12.0, Item::resolve('999')[1]);
    }
}

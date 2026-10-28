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
}

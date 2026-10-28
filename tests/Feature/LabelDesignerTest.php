<?php

namespace Tests\Feature;

use App\Livewire\Admin\Labels\Designer;
use App\Livewire\Admin\Labels\Index as LabelIndex;
use App\Models\Item;
use App\Models\LabelTemplate;
use App\Models\PriceCheck;
use App\Models\Printer;
use App\Models\User;
use App\Services\PriceTagPrinter;
use App\Services\ZplLabel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LabelDesignerTest extends TestCase
{
    use RefreshDatabase;

    private function item(): Item
    {
        return Item::create(['code' => '1', 'barcode' => '4780000000011', 'name' => 'Sut 1 l', 'unit' => 'dona', 'price' => 12500, 'is_active' => true]);
    }

    private function element(array $over = []): array
    {
        return LabelTemplate::sanitize([$over + ['type' => 'text', 'source' => 'name', 'x' => 2, 'y' => 2, 'w' => 40, 'h' => 10, 'size' => 4]])[0];
    }

    private function template(array $elements, array $over = []): LabelTemplate
    {
        return new LabelTemplate($over + ['name' => 'T', 'width_mm' => 50, 'height_mm' => 30, 'rotation' => 0, 'dpi' => 203, 'font' => '0', 'elements' => $elements]);
    }

    public function test_label_rotation_moves_and_turns_every_element(): void
    {
        $zpl = new ZplLabel();
        $e = $this->element(['x' => 0, 'y' => 0, 'w' => 20, 'h' => 10]); // 50 x 30 mm label

        // no rotation: box stays put
        [$box, $rot] = $zpl->place($e, 50, 30, 0);
        $this->assertSame([[0.0, 0.0, 20.0, 10.0], 0], [array_map('floatval', $box), $rot]);

        // label turned 90 deg clockwise: the top-left element lands at the top-right, now 10 wide and 20 tall, turned 90
        [$box, $rot] = $zpl->place($e, 50, 30, 90);
        $this->assertSame(90, $rot);
        $this->assertEqualsWithDelta([20.0, 0.0, 10.0, 20.0], $box, 0.001);

        // 180: bottom-right corner
        [$box, $rot] = $zpl->place($e, 50, 30, 180);
        $this->assertSame(180, $rot);
        $this->assertEqualsWithDelta([30.0, 20.0, 20.0, 10.0], $box, 0.001);

        // an element already turned 90 plus a label turned 270 ends up upright again (360)
        $turned = $this->element(['x' => 0, 'y' => 0, 'w' => 20, 'h' => 10, 'rotation' => 90]);
        [, $rot] = $zpl->place($turned, 50, 30, 270);
        $this->assertSame(0, $rot);
    }

    public function test_zpl_has_the_label_size_orientation_fields_and_barcode(): void
    {
        $item = $this->item();
        $template = $this->template(LabelTemplate::sanitize([
            ['type' => 'text', 'source' => 'name', 'x' => 2, 'y' => 2, 'w' => 40, 'h' => 10, 'size' => 4, 'align' => 'C'],
            ['type' => 'text', 'source' => 'price', 'text' => ' so‘m', 'x' => 2, 'y' => 14, 'w' => 40, 'h' => 8, 'size' => 6, 'rotation' => 90],
            ['type' => 'barcode', 'source' => 'barcode', 'symbology' => 'ean13', 'show_text' => true, 'x' => 2, 'y' => 20, 'w' => 40, 'h' => 8],
            ['type' => 'box', 'x' => 0, 'y' => 0, 'w' => 50, 'h' => 30],
        ]));

        $out = (new ZplLabel())->render($template, $item);

        $this->assertStringStartsWith('^XA^CI28^PW400^LL240', $out); // 50 x 30 mm at 203 dpi
        $this->assertStringContainsString('^A0N,32,0^FDSut 1 l^FS', $out);
        $this->assertStringContainsString('^FB320,', $out);
        $this->assertStringContainsString(',C,0', $out);
        $this->assertStringContainsString('^A0R,48,0^FD12 500 so‘m^FS', $out); // rotated 90 -> orientation R
        $this->assertStringContainsString('^BEN,64,Y,N^FD478000000001^FS', $out);
        $this->assertStringContainsString('^GB400,240,2^FS', $out);
        $this->assertStringEndsWith('^XZ', $out);

        // whole label turned 90: width and height swap
        $this->assertStringStartsWith('^XA^CI28^PW240^LL400', (new ZplLabel())->render($this->template($template->elements, ['rotation' => 90]), $item));
    }

    public function test_control_characters_and_bad_designs_are_neutralised(): void
    {
        $clean = LabelTemplate::sanitize([
            ['type' => 'evil', 'x' => 1],
            ['type' => 'text', 'source' => 'static', 'text' => "a^b~c\nd", 'x' => 'x', 'w' => -5, 'h' => 9999, 'rotation' => 45, 'align' => 'Z'],
        ] + array_fill(2, 60, ['type' => 'line']));

        $this->assertCount(LabelTemplate::MAX_ELEMENTS - 1, $clean); // unknown type dropped, list capped
        $this->assertSame('a b c d', $clean[0]['text']);
        $this->assertSame([0, 'L', 0.0], [$clean[0]['rotation'], $clean[0]['align'], (float) $clean[0]['x']]);
        $this->assertGreaterThan(0, $clean[0]['w']);
        $this->assertLessThanOrEqual(400, $clean[0]['h']);
    }

    public function test_admin_designs_saves_and_chooses_the_default_template(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $item = $this->item();
        $this->actingAs($admin);

        $this->get('/admin/label-templates/create')->assertOk()->assertSee('id="sheet"', false)->assertSee('data-add="barcode"', false);

        $design = fn (array $over = []) => Livewire::test(Designer::class)
            ->set('name', $over['name'] ?? 'Asosiy')->set('width_mm', '58')->set('height_mm', '40')->set('rotation', '90')->set('dpi', '203')
            ->set('font', $over['font'] ?? '0')->set('elements', LabelTemplate::starter());

        $design()->call('save')->assertRedirect();
        $first = LabelTemplate::firstOrFail();
        $this->assertTrue($first->is_default); // the first design becomes the default
        $this->assertSame(90, $first->rotation);
        $this->assertCount(3, $first->elements);

        $design(['name' => 'Ikkinchi', 'font' => 'bad font'])->call('save')->assertHasErrors('font');
        $design(['name' => 'Ikkinchi', 'font' => 'E:ARIAL.TTF'])->call('save');
        $second = LabelTemplate::where('name', 'Ikkinchi')->firstOrFail();
        Livewire::test(LabelIndex::class)->call('makeDefault', $second->id)->assertSee('Ikkinchi');
        $this->assertSame(1, LabelTemplate::where('is_default', true)->count());
        $this->assertTrue($second->fresh()->is_default);

        // the editor loads the saved design; the ZPL tab shows the design as it is right now (unsaved changes too)
        $editor = Livewire::test(Designer::class, ['record' => $first->id])->assertSet('name', 'Asosiy')->assertSet('rotation', '90')->assertSee('^XA', false)->assertSee('Sut 1 l');
        $editor->set('elements', [])->assertSee('Dizaynni ko‘rish uchun elementlar qo‘shing.')->set('name', 'Yangi nom')->call('save');
        $this->assertSame('Yangi nom', $first->fresh()->name);
        $this->assertSame([], $first->fresh()->elements);

        $this->get("/admin/label-templates/{$first->id}/edit")->assertOk()->assertSee('Sinov chop etish');
        $this->get('/admin/label-templates')->assertOk()->assertSee('Ikkinchi');
        Livewire::test(LabelIndex::class)->call('delete', $second->id);
        $this->assertNull(LabelTemplate::find($second->id));

        $this->actingAs(User::factory()->create(['role' => 'user']))->get('/admin/label-templates')->assertForbidden();
    }

    public function test_test_print_sends_the_saved_design_to_the_chosen_printer(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $item = $this->item();
        $label = LabelTemplate::create(['name' => 'D', 'width_mm' => 50, 'height_mm' => 30, 'rotation' => 0, 'dpi' => 203, 'font' => '0',
            'elements' => LabelTemplate::sanitize([['type' => 'text', 'source' => 'name', 'x' => 1, 'y' => 1, 'w' => 40, 'h' => 6, 'size' => 3]])]);

        $server = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
        $port = (int) substr(strrchr(stream_socket_get_name($server, false), ':'), 1);
        $printer = Printer::create(['name' => 'Lab', 'host' => '127.0.0.1', 'port' => $port]);

        Livewire::actingAs($admin)->test(Designer::class, ['record' => $label->id])
            ->call('test')->assertHasErrors(['printer_id', 'item_id'])
            ->set('printer_id', (string) $printer->id)->set('item_id', (string) $item->id)->call('test')->assertSet('notice', 'Sinov yorlig‘i yuborildi.');

        $conn = stream_socket_accept($server, 2);
        $this->assertStringContainsString('Sut 1 l', stream_get_contents($conn, -1, -1));
        fclose($conn);
        fclose($server);
    }
    public function test_queued_price_tags_use_the_default_design(): void
    {
        $item = $this->item();
        $design = LabelTemplate::create(['name' => 'D', 'width_mm' => 50, 'height_mm' => 30, 'rotation' => 0, 'dpi' => 203, 'font' => '0',
            'elements' => LabelTemplate::sanitize([['type' => 'text', 'source' => 'code', 'x' => 1, 'y' => 1, 'w' => 20, 'h' => 5, 'size' => 3]]), 'is_default' => true]);
        $printer = Printer::create(['name' => 'L', 'host' => '127.0.0.1', 'port' => 9100, 'is_price_tag' => true]);

        $this->assertStringContainsString('^PW400^LL240', app(PriceTagPrinter::class)->label($printer, $item));
        $design->update(['is_default' => false]);
        $this->assertStringContainsString('^BC', app(PriceTagPrinter::class)->label($printer, $item)); // the plain built-in label again
    }
}

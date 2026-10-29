<?php

namespace Tests\Feature;

use App\Models\Counterparty;
use App\Models\Device;
use App\Models\Document;
use App\Models\Item;
use App\Models\Message;
use App\Models\MessageRecipient;
use App\Models\Task;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Livewire\Admin\Counterparties\Form as CounterpartyForm;
use App\Livewire\Admin\Counterparties\Index as CounterpartyIndex;
use App\Livewire\Admin\Items\Form as ItemForm;
use App\Livewire\Admin\Messages\Form as MessageForm;
use App\Livewire\Admin\Tasks\Form as TaskForm;
use App\Livewire\Admin\Tasks\Index as TaskIndex;
use App\Livewire\Admin\Users\Form as UserForm;
use App\Livewire\Admin\Warehouses\Form as WarehouseForm;
use App\Livewire\Pda\Docs\Create as PdaCreate;
use App\Livewire\Pda\Docs\Entry as PdaEntry;
use App\Livewire\Pda\Docs\Show as PdaShow;
use App\Livewire\Pda\Login as PdaLogin;
use App\Livewire\Pda\Tasks\Show as PdaTaskShow;
use Livewire\Livewire;
use Tests\TestCase;

/** Admin-managed users, menu access, tasks, messages, item packs, master data and PDA devices. */
class TeamFeaturesTest extends TestCase
{
    use RefreshDatabase;

    /** Adds a line the way the PDA does: scan/pick opens the entry window, saving it writes the line. */
    private function enter(User $user, Document $doc, string $code, array $extra = []): void
    {
        [$item, $qty] = Item::resolve($code);
        $form = Livewire::actingAs($user)->test(PdaEntry::class, ['record' => $doc->id, 'item' => $item->id]);

        foreach ($extra + ['qty' => $qty, 'price' => $item->price] as $name => $value) {
            $form->set($name, (string) $value);
        }

        $form->call('save');
    }

    /** Step 1 of a document the way the PDA does it. */
    private function createDoc(User $user, string $type, array $header, ?int $task = null)
    {
        $form = Livewire::actingAs($user)->test(PdaCreate::class, ['type' => $type, 'task' => $task]);

        foreach ($header as $name => $value) {
            $form->set("header.{$name}", (string) $value);
        }

        return $form->call('save');
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function operator(array $attrs = []): User
    {
        return User::factory()->create($attrs + ['role' => 'user']);
    }

    // ---------------------------------------------------------------- users & menu access

    public function test_admin_creates_and_edits_operators(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);
        $pdaRole = \App\Models\Role::where('name', 'PDA operator')->first();

        $this->get('/admin/users')->assertOk();
        Livewire::test(UserForm::class)
            ->set('name', 'Ali')->set('email', 'ali@rsg.uz')->set('role_id', (string) $pdaRole->id)->set('password', 'secret123')->set('is_active', true)
            ->call('save')->assertRedirect();

        // a new user gets a PIN and a badge at once
        $ali = User::where('email', 'ali@rsg.uz')->first();
        $this->assertTrue($ali->is_active);
        $this->assertSame($pdaRole->id, $ali->role_id);
        $this->assertSame('user', $ali->role);
        $this->assertNotNull($ali->pin);
        $this->assertNotNull($ali->badge_token);

        // blank password keeps the old one; the role can be changed
        $hash = $ali->password;
        $admins = \App\Models\Role::where('is_admin', true)->first();
        Livewire::test(UserForm::class, ['record' => $ali->id])
            ->assertSet('name', 'Ali')->assertSet('role_id', (string) $pdaRole->id)
            ->set('name', 'Ali V.')->set('password', '')->set('role_id', (string) $admins->id)
            ->call('save')->assertRedirect('/admin/users');
        $this->assertSame($hash, $ali->fresh()->password);
        $this->assertSame('Ali V.', $ali->fresh()->name);
        $this->assertSame('admin', $ali->fresh()->role);

        // a new user needs a password and a role; duplicate login is refused; operators cannot reach the screen
        Livewire::test(UserForm::class)->set('name', 'X')->set('email', 'x@rsg.uz')->call('save')->assertHasErrors('password');
        Livewire::test(UserForm::class)->set('name', 'X')->set('email', 'ali@rsg.uz')->set('password', 'secret123')->call('save')->assertHasErrors('email');
        $this->actingAs($this->operator())->get('/admin/users')->assertForbidden();
        Livewire::actingAs($this->operator())->test(UserForm::class)->assertForbidden();
    }

    public function test_admin_cannot_lock_themselves_out(): void
    {
        $admin = $this->admin();

        Livewire::actingAs($admin)->test(UserForm::class, ['record' => $admin->id])->set('role_id', (string) \App\Models\Role::where('name', 'PDA operator')->value('id'))->set('is_active', false)->call('save');

        $this->assertSame('admin', $admin->fresh()->role);
        $this->assertTrue($admin->fresh()->is_active);
    }
    public function test_deactivated_user_cannot_log_in_or_keep_a_session(): void
    {
        $user = $this->operator(['email' => 'off@rsg.uz', 'password' => 'secret123', 'is_active' => false]);

        Livewire::test(PdaLogin::class)->set('email', 'off@rsg.uz')->set('password', 'secret123')->call('login')->assertHasErrors('email');
        $this->assertGuest();

        $this->actingAs($user)->get('/')->assertRedirect('/login');
    }

    public function test_active_user_logs_in_and_last_login_is_recorded(): void
    {
        $user = $this->operator(['email' => 'on@rsg.uz', 'password' => 'secret123']);

        Livewire::test(PdaLogin::class)->set('email', 'on@rsg.uz')->set('password', 'secret123')->call('login')->assertRedirect(route('menu'));
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->last_login_at);

        // wrong password: a message, nothing changes
        auth()->logout();
        Livewire::test(PdaLogin::class)->set('email', 'on@rsg.uz')->set('password', 'nope')->call('login')->assertHasErrors('email');
        $this->assertGuest();
    }

    public function test_menu_access_hides_and_blocks_items(): void
    {
        $role = \App\Models\Role::create(['name' => 'Faqat qabul', 'permissions' => ['pda.receiving']]);
        $user = $this->operator(['role_id' => $role->id]);
        $stock = Warehouse::create(['name' => 'A']);

        $this->actingAs($user)->get('/')->assertOk()->assertSee('Qabul qilish')->assertDontSee('Narxni tekshirish')->assertDontSee('Inventarizatsiya');

        $this->actingAs($user)->get('/docs/type/receiving')->assertOk();
        $this->actingAs($user)->get('/docs/type/stocktaking')->assertForbidden();
        Livewire::actingAs($user)->test(PdaCreate::class, ['type' => 'stocktaking'])->assertForbidden();
        $this->actingAs($user)->get('/m/pricecheck')->assertForbidden();
        $this->actingAs($user)->get('/docs/type/pricecheck/create')->assertForbidden();
        $this->actingAs($user)->get('/directory')->assertForbidden();

        // an existing document of a type that was switched off is closed too
        $doc = Document::create(['type' => 'stocktaking', 'user_id' => $user->id]);
        $this->actingAs($user)->get("/docs/{$doc->id}")->assertForbidden();
        Livewire::actingAs($user)->test(PdaShow::class, ['record' => $doc->id])->assertForbidden();

        // assigned tasks, messages and settings stay available
        $this->actingAs($user)->get('/tasks')->assertOk();
        $this->actingAs($user)->get('/messages')->assertOk();
        $this->actingAs($user)->get('/settings')->assertOk();
    }

    public function test_unrestricted_users_and_admins_see_everything(): void
    {
        $this->actingAs($this->operator())->get('/')->assertSee('Narxni tekshirish')->assertSee('Qabul qilish');
        $this->actingAs($this->admin())->get('/docs/type/pricecheck/create')->assertOk();
    }

    // ---------------------------------------------------------------- tasks

    public function test_task_lifecycle_from_assignment_to_completed_document(): void
    {
        $admin = $this->admin();
        $user = $this->operator();
        Item::create(['code' => '1', 'barcode' => '111', 'name' => 'Sut', 'unit' => 'dona', 'price' => 10]);
        $stock = Warehouse::create(['name' => 'A']);

        // no tasks yet: the menu row is hidden
        $this->actingAs($user)->get('/')->assertDontSee('Vazifalarim');

        $this->actingAs($admin)->get('/admin/tasks/create')->assertOk()->assertSee($user->name);
        Livewire::actingAs($admin)->test(TaskForm::class)
            ->set('title', 'Ombor sanog‘i')->set('assignee_id', (string) $user->id)->set('priority', 'high')->set('doc_type', 'stocktaking')
            ->call('save')->assertRedirect('/admin/tasks');
        $task = Task::first();
        $this->assertSame('new', $task->status);

        // menu: row appears with a red counter; hardware key 0
        $this->actingAs($user)->get('/')->assertSee('Vazifalarim')->assertSee('data-key="0"', false);
        $this->actingAs($user)->get('/tasks')->assertSee('Ombor sanog‘i')->assertSee('Shoshilinch');

        // only the assignee sees it
        $this->actingAs($this->operator())->get("/tasks/{$task->id}")->assertForbidden();

        // cannot start before accepting
        $this->createDoc($user, 'stocktaking', ['warehouse_id' => $stock->id], $task->id);
        $this->assertSame('new', $task->fresh()->status);
        Document::query()->delete();

        Livewire::actingAs($user)->test(PdaTaskShow::class, ['record' => $task->id])->call('accept')->assertSet('notice', 'Vazifa qabul qilindi.');
        $this->assertSame('accepted', $task->fresh()->status);

        // starting from the task links the new document and moves the task to in progress
        $this->actingAs($user)->get("/tasks/{$task->id}")->assertSee('Boshlash');
        $this->createDoc($user, 'stocktaking', ['warehouse_id' => $stock->id], $task->id);
        $doc = Document::first();
        $this->assertSame('in_progress', $task->fresh()->status);
        $this->assertTrue($task->documents()->whereKey($doc->id)->exists());

        // completing the linked document completes the task
        $this->enter($user, $doc, '111');
        Livewire::actingAs($user)->test(PdaShow::class, ['record' => $doc->id])->call('saveHeader', 'complete');
        $this->assertSame('done', $task->fresh()->status);
        $this->assertNotNull($task->fresh()->completed_at);
    }

    public function test_task_with_several_documents_is_done_only_when_none_is_draft(): void
    {
        $user = $this->operator();
        Item::create(['code' => '1', 'barcode' => '111', 'name' => 'Sut', 'unit' => 'dona', 'price' => 10]);
        $stock = Warehouse::create(['name' => 'A']);
        $task = Task::create(['title' => 'Zonalar', 'assignee_id' => $user->id, 'created_by' => $user->id, 'status' => 'accepted', 'doc_type' => 'stocktaking']);
        $header = ['warehouse_id' => $stock->id];

        $this->createDoc($user, 'stocktaking', $header, $task->id);
        $this->createDoc($user, 'stocktaking', $header, $task->id);
        [$first, $second] = Document::orderBy('id')->get();

        foreach ([$first, $second] as $doc) {
            $this->enter($user, $doc, '111');
        }

        Livewire::actingAs($user)->test(PdaShow::class, ['record' => $first->id])->call('saveHeader', 'complete');
        $this->assertSame('in_progress', $task->fresh()->status);

        Livewire::actingAs($user)->test(PdaShow::class, ['record' => $second->id])->call('saveHeader', 'complete');
        $this->assertSame('done', $task->fresh()->status);
    }

    public function test_task_can_be_declined_with_a_reason_and_simple_tasks_completed(): void
    {
        $user = $this->operator();
        $make = fn (array $a = []) => Task::create($a + ['title' => 'T', 'assignee_id' => $user->id, 'created_by' => $user->id]);

        $declined = $make();
        $task = fn ($t) => Livewire::actingAs($user)->test(PdaTaskShow::class, ['record' => $t->id]);
        $task($declined)->call('decline')->assertHasErrors('reason');
        $task($declined)->set('reason', 'Ta’tilda')->call('decline')->assertRedirect('/tasks');
        $this->assertSame('declined', $declined->fresh()->status);
        $this->assertSame('Ta’tilda', $declined->fresh()->cancel_reason);

        $simple = $make(['status' => 'accepted']); // no document to execute
        $task($simple)->call('complete')->assertRedirect('/tasks');
        $this->assertSame('done', $simple->fresh()->status);

        $withDoc = $make(['status' => 'accepted', 'doc_type' => 'receiving']);
        $task($withDoc)->call('complete')->assertForbidden();
    }

    public function test_admin_cancels_open_tasks_only(): void
    {
        $admin = $this->admin();
        $user = $this->operator();
        $open = Task::create(['title' => 'A', 'assignee_id' => $user->id, 'created_by' => $admin->id]);
        $done = Task::create(['title' => 'B', 'assignee_id' => $user->id, 'created_by' => $admin->id, 'status' => 'done']);

        $this->actingAs($admin)->get('/admin/tasks')->assertOk()->assertSee('A');
        $list = Livewire::actingAs($admin)->test(TaskIndex::class)->set("reasons.{$open->id}", 'Kerak emas')->call('cancel', $open->id);
        $this->assertSame('cancelled', $open->fresh()->status);
        $this->assertSame('Kerak emas', $open->fresh()->cancel_reason);
        $list->call('cancel', $done->id)->assertSet('failure', 'Faqat ochiq vazifani bekor qilish mumkin.');
        $this->assertSame('done', $done->fresh()->status);
    }

    public function test_tasks_can_only_be_assigned_to_active_users(): void
    {
        $inactive = $this->operator(['is_active' => false]);

        Livewire::actingAs($this->admin())->test(TaskForm::class)->set('title', 'T')->set('assignee_id', (string) $inactive->id)->call('save')->assertHasErrors('assignee_id');
    }

    // ---------------------------------------------------------------- messages

    public function test_broadcast_message_reaches_active_operators_with_read_receipts(): void
    {
        $admin = $this->admin();
        $a = $this->operator();
        $b = $this->operator();
        $off = $this->operator(['is_active' => false]);

        Livewire::actingAs($admin)->test(MessageForm::class)
            ->set('title', 'Ogohlantirish')->set('body', 'Ombor yopiladi')->set('priority', 'urgent')->set('audience', 'all')
            ->call('send')->assertRedirect('/admin/messages');

        $message = Message::first();
        $this->assertSame(2, $message->recipients()->count());
        $this->assertFalse($message->recipients()->where('user_id', $off->id)->exists());

        // bell counter and urgent banner on the home screen
        $this->actingAs($a)->get('/')->assertSee('<span class="dot">1</span>', false)->assertSee('Ogohlantirish');

        // opening marks it read; the other recipient is unaffected
        $this->actingAs($a)->get("/messages/{$message->id}")->assertOk()->assertSee('Ombor yopiladi');
        $this->assertNotNull(MessageRecipient::where('user_id', $a->id)->first()->read_at);
        $this->assertNull(MessageRecipient::where('user_id', $b->id)->first()->read_at);
        $this->actingAs($a)->get('/')->assertDontSee('<span class="dot">', false);

        // an active operator who was not a recipient (joined later) cannot read it
        $this->actingAs($this->operator())->get("/messages/{$message->id}")->assertNotFound();

        $this->actingAs($admin)->get("/admin/messages/{$message->id}")->assertOk()->assertSee($a->name)->assertSee('o‘qilmagan');
        $this->actingAs($admin)->get('/admin/messages')->assertOk()->assertSee('Ogohlantirish');
    }

    public function test_message_to_selected_operators_and_validation(): void
    {
        $admin = $this->admin();
        $a = $this->operator();
        $b = $this->operator();

        $form = fn () => Livewire::actingAs($admin)->test(MessageForm::class)->set('title', 'Maxsus')->set('body', 'B')->set('audience', 'selected');
        $form()->call('send')->assertHasErrors('users');
        $form()->set('users', [(string) $b->id])->call('send')->assertRedirect('/admin/messages');
        $this->assertSame([$b->id], MessageRecipient::pluck('user_id')->all());

        $this->actingAs($a)->get('/messages')->assertOk()->assertDontSee('Maxsus');
        Livewire::actingAs($a)->test(MessageForm::class)->assertForbidden();
    }

    // ---------------------------------------------------------------- items: packs, flags

    public function test_pack_barcode_books_the_pack_quantity(): void
    {
        $user = $this->operator();
        $item = Item::create(['code' => '1', 'barcode' => '111', 'name' => 'Sut', 'unit' => 'dona', 'price' => 10]);
        $item->barcodes()->create(['barcode' => '999', 'qty_in_pack' => 12]);
        $doc = Document::create(['type' => 'receiving', 'user_id' => $user->id]);

        $this->enter($user, $doc, '999');
        $this->assertEquals(12, $doc->lines()->first()->qty);

        $this->enter($user, $doc, '111');
        $this->assertEquals(13, $doc->lines()->first()->qty);

        // searching finds the item by its pack barcode too
        $this->actingAs($user)->get("/docs/{$doc->id}/browse?q=999")->assertSee('Sut');
    }

    public function test_admin_item_card_is_view_only_and_inactive_items_are_not_scannable(): void
    {
        $admin = $this->admin();
        $item = Item::create(['code' => 'A1', 'barcode' => '100', 'name' => 'Sut', 'unit' => 'dona', 'price' => 5, 'is_active' => true]);
        $item->barcodes()->create(['barcode' => '200', 'qty_in_pack' => 12]);
        $this->actingAs($admin);

        // the card shows the Adines data; it has no save action
        Livewire::test(ItemForm::class, ['record' => $item->id])->assertSee('200')->assertSee('12');
        $this->assertFalse(method_exists(ItemForm::class, 'save'));
        $this->get("/admin/items/{$item->id}/edit")->assertOk()->assertDontSee('wire:submit', false);

        // an inactive item is hidden from the PDA
        $this->assertNotNull(Item::resolve('100'));
        $item->update(['is_active' => false]);
        $this->assertNull(Item::resolve('100'));
        $this->assertNull(Item::resolve('200'));
    }

    // ---------------------------------------------------------------- master data

    public function test_master_data_cards_are_view_only_with_no_new_button(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);
        $main = Warehouse::create(['code' => 'MAIN', 'name' => 'Asosiy']);
        $cp = \App\Models\Counterparty::create(['name' => 'ACME', 'stir' => '301234567']);
        $pt = \App\Models\PriceType::create(['name' => 'Chakana']);

        foreach (['warehouses' => $main, 'counterparties' => $cp, 'price-types' => $pt] as $url => $record) {
            $this->get("/admin/{$url}")->assertOk()->assertSee($record->name)->assertDontSee('+ Yangi')->assertDontSee("/admin/{$url}/create", false);
            $this->get("/admin/{$url}/create")->assertNotFound();
            $this->get("/admin/{$url}/{$record->id}/edit")->assertOk()->assertSee('faqat ko‘rish')->assertDontSee('wire:submit', false)->assertDontSee('Saqlash');
        }

        $this->assertFalse(method_exists(WarehouseForm::class, 'save'));
        $this->get('/admin/items')->assertDontSee('+ Yangi');
    }

    // ---------------------------------------------------------------- devices & keyboard

    public function test_first_visit_registers_the_device_and_keeps_the_keyboard_hidden(): void
    {
        $user = $this->operator();
        $sdoc = Document::create(["type" => "stocktaking", "user_id" => $user->id, "doc_date" => now()]);
        $response = $this->actingAs($user)->get("/docs/{$sdoc->id}")->assertOk();
        $response->assertCookie('device_token');
        $response->assertSee('autocomplete="off" inputmode="none"', false)->assertSee('data-kb="auto"', false);

        $device = Device::first();
        $this->assertSame('auto', $device->keyboard_mode);
        $this->assertNull($device->name);
        $this->assertSame(64, strlen($device->token_hash)); // only the hash of the secret is stored
    }

    public function test_known_device_is_recognised_and_keyboard_on_mode_shows_the_native_keyboard(): void
    {
        $user = $this->operator();
        Device::create(['uuid' => (string) \Illuminate\Support\Str::uuid(), 'token_hash' => hash('sha256', 'secret-token'), 'keyboard_mode' => 'on']);

        $this->withCookie('device_token', 'secret-token')->actingAs($user)->get('/docs/type/pricecheck/create')
            ->assertOk()->assertDontSee('autocomplete="off" inputmode="none"', false)->assertSee('data-kb="on"', false);

        $this->assertSame(1, Device::count()); // not registered twice
    }

    public function test_admin_names_a_device_and_chooses_the_keyboard_mode(): void
    {
        $admin = $this->admin();
        $device = Device::create(['uuid' => (string) \Illuminate\Support\Str::uuid(), 'token_hash' => hash('sha256', 'x'), 'keyboard_mode' => 'auto']);

        $this->actingAs($admin)->get('/admin/devices')->assertOk()->assertSee('nomsiz');
        Livewire::actingAs($admin)->test(\App\Livewire\Admin\Devices\Form::class, ['record' => $device->id])->set('name', 'Ombor-1')->set('keyboard_mode', 'off')->call('save')->assertRedirect('/admin/devices');
        $this->assertSame('off', $device->fresh()->keyboard_mode);
        Livewire::actingAs($admin)->test(\App\Livewire\Admin\Devices\Form::class, ['record' => $device->id])->set('name', 'X')->set('keyboard_mode', 'weird')->call('save')->assertHasErrors('keyboard_mode');
        $this->actingAs($this->operator())->get('/admin/devices')->assertForbidden();
    }

    public function test_every_window_has_a_top_and_a_bottom_bar(): void
    {
        $user = $this->operator();

        // even the login window has the bottom bar (checked first: no one is signed in yet)
        $this->get('/login')->assertSee('<div class="footer bar-default">', false);

        // screens without their own buttons get the default bottom bar; the home screen shows who is signed in
        $this->actingAs($user)->get('/tasks')->assertSee('<div class="footer bar-default">', false)->assertSee('Bosh menyu');
        $this->actingAs($user)->get('/')->assertSee('<div class="footer bar-default">', false)->assertSee($user->name);
        $this->actingAs($user)->get('/settings')->assertSee('<header>', false)->assertSee('<main>', false)->assertSee('Bosh menyu');

        // screens with their own buttons keep them instead of the default bar
        $this->actingAs($user)->get('/docs/type/receiving')->assertSee('+ Yangi hujjat')->assertDontSee('<div class="footer bar-default">', false);

        // the viewport follows the on-screen keyboard so the bottom bar is not covered
        $this->actingAs($user)->get('/')->assertSee('interactive-widget=resizes-content', false);

        // desktop admin: top bar with the title, scrolling content, bottom bar
        $this->actingAs($this->admin())->get('/admin/items')->assertSee('class="topbar"', false)->assertSee('class="scroll"', false)->assertSee('class="botbar"', false);
    }

    public function test_settings_page_shows_the_admin_choice_and_the_local_override(): void
    {
        $this->actingAs($this->operator())->get('/settings')->assertOk()
            ->assertSee('Ekran klaviaturasi')->assertSee('Administrator sozlamasi')->assertSee('kb_mode', false);
    }
}

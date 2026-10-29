<?php

namespace Tests\Feature;

use App\Livewire\Admin\Roles\Form as RoleForm;
use App\Livewire\Admin\Roles\Index as RoleIndex;
use App\Livewire\Admin\Users\Form as UserForm;
use App\Livewire\Pda\Login;
use App\Models\Document;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** Sign in by picking a user + PIN or by the badge QR code (PDA and admin panel alike), and roles that decide what a user may open. */
class RolesAndPinLoginTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $name, ?Role $role = null, ?string $pin = '2580'): User
    {
        $role ??= Role::where('name', 'PDA operator')->first();
        $user = User::factory()->create(['name' => $name, 'role_id' => $role->id, 'role' => $role->is_admin ? 'admin' : 'user']);
        $pin && $user->issuePin($pin);
        $user->regenerateBadge();

        return $user;
    }

    private function adminUser(): User
    {
        return $this->user('Boss', Role::where('is_admin', true)->first());
    }

    // ---------------------------------------------------------------- PIN

    public function test_pick_a_user_and_enter_the_pin(): void
    {
        $ali = $this->user('Ali');
        $this->user('Vali', null, null); // no PIN yet
        User::factory()->create(['name' => 'Off', 'is_active' => false]);

        $login = Livewire::test(Login::class)->assertSee('Ali')->assertSee('Vali')->assertDontSee('Off');

        // someone without a PIN cannot be picked
        $login->call('choose', User::where('name', 'Vali')->value('id'))->assertHasErrors('pin')->assertSet('mode', 'pick');

        // wrong PIN first, then the right one
        $login->call('choose', $ali->id)->assertSet('mode', 'pin');
        $login->call('submitPin', '1119')->assertHasErrors('pin');
        $this->assertGuest();

        $login->call('submitPin', '2580')->assertRedirect(route('menu'));
        $this->assertAuthenticatedAs($ali);
        $this->assertNotNull($ali->fresh()->last_login_at);
    }

    public function test_five_wrong_pins_lock_pin_sign_in_for_that_user(): void
    {
        $ali = $this->user('Ali');
        $login = Livewire::test(Login::class)->call('choose', $ali->id);

        for ($i = 0; $i < 5; $i++) {
            $login->call('submitPin', '0001');
        }
        $this->assertTrue($ali->fresh()->isPinLocked());

        // even the right PIN is refused while locked; a new PIN from the admin lifts it
        $login->call('submitPin', '2580')->assertHasErrors('pin');
        $this->assertGuest();

        $ali->fresh()->issuePin('2580');
        $this->assertFalse($ali->fresh()->isPinLocked());
    }

    public function test_admin_panel_users_sign_in_the_same_way_and_land_in_the_admin_panel(): void
    {
        $boss = $this->adminUser();
        $login = Livewire::test(Login::class)->assertSee('Boss')->call('choose', $boss->id);
        $login->call('submitPin', '2580')->assertRedirect(route('admin.home'));
        $this->assertAuthenticatedAs($boss);
    }

    // ---------------------------------------------------------------- badge QR

    public function test_scanning_the_badge_signs_in_and_a_renewed_badge_kills_the_old_one(): void
    {
        $ali = $this->user('Ali');
        $old = $ali->badge_token;

        // a wedge scanner types the URL held in the QR code
        Livewire::test(Login::class)->set('scan', $ali->badge_url)->call('scanBadge')->assertRedirect(route('menu'));
        $this->assertAuthenticatedAs($ali);
        auth()->logout();

        Livewire::test(Login::class)->set('scan', 'nonsense')->call('scanBadge')->assertHasErrors('scan');
        $this->assertGuest();

        // the camera opens the URL itself
        $this->get('/login/badge/'.$old)->assertRedirect(route('menu'));
        $this->assertAuthenticatedAs($ali);
        auth()->logout();
        $this->flushSession();

        $ali->regenerateBadge();
        $this->get('/login/badge/'.$old)->assertRedirect(route('login'));
        $this->assertGuest();
        $this->get('/login/badge/'.$ali->fresh()->badge_token)->assertRedirect();
        $this->assertAuthenticatedAs($ali);
    }

    public function test_inactive_users_badge_does_not_work(): void
    {
        $ali = $this->user('Ali');
        $ali->update(['is_active' => false]);

        $this->get('/login/badge/'.$ali->badge_token)->assertRedirect(route('login'));
        $this->assertGuest();
    }

    // ---------------------------------------------------------------- admin: PIN, badge, print

    public function test_admin_issues_a_pin_and_badge_and_prints_it(): void
    {
        $boss = $this->adminUser();
        $ali = $this->user('Ali');
        $oldHash = $ali->pin;
        $oldBadge = $ali->badge_token;

        $form = Livewire::actingAs($boss)->test(UserForm::class, ['record' => $ali->id]);
        $form->call('generatePin')->assertSet('shownPin', fn ($pin) => preg_match('/^\d{4}$/', $pin) === 1);
        $this->assertNotSame($oldHash, $ali->fresh()->pin);

        $form->set('pinInput', '12')->call('setPin')->assertHasErrors('pinInput');
        $form->set('pinInput', '7391')->call('setPin')->assertHasNoErrors()->assertSet('shownPin', '7391');
        $this->assertTrue($ali->fresh()->checkPin('7391'));

        $form->call('regenerateBadge');
        $this->assertNotSame($oldBadge, $ali->fresh()->badge_token);

        // the printable sheet carries the name, role and QR code, never the PIN
        $this->actingAs($boss)->get(route('admin.badges.print', ['ids' => [$ali->id]]))
            ->assertOk()->assertSee('Ali')->assertSee('PDA operator')->assertSee('<svg', false)->assertDontSee('7391');
        $this->actingAs($boss)->get(route('admin.badges.index'))->assertOk()->assertSee('Ali');

        $this->actingAs($ali)->get(route('admin.badges.print'))->assertForbidden();
    }

    // ---------------------------------------------------------------- roles

    public function test_roles_decide_what_a_user_may_open(): void
    {
        $boss = $this->adminUser();

        // the admin ticks a group and a few items; unknown keys are dropped
        Livewire::actingAs($boss)->test(RoleForm::class)
            ->set('name', 'Ombor mudiri')->set('perms', ['admin.items', 'admin.warehouses', 'admin.documents.receiving', 'pda.receiving', 'admin.nonsense'])
            ->call('save')->assertRedirect(route('admin.roles.index'));
        $role = Role::where('name', 'Ombor mudiri')->first();
        $this->assertEqualsCanonicalizing(['admin.items', 'admin.warehouses', 'admin.documents.receiving', 'pda.receiving'], $role->permissions);

        Livewire::actingAs($boss)->test(RoleForm::class)->set('name', 'Ombor mudiri')->call('save')->assertHasErrors('name'); // names are unique

        $clerk = $this->user('Clerk', $role);

        // the admin panel opens on the first allowed section and only shows what was ticked
        $this->actingAs($clerk)->get('/admin')->assertRedirect(route('admin.items.index'));
        $this->actingAs($clerk)->get('/admin/items')->assertOk()->assertSee('Tovarlar')->assertSee('Omborlar')->assertDontSee('Foydalanuvchilar')->assertDontSee('Narx tekshirgichlar')->assertDontSee('Vazifalar');
        $this->actingAs($clerk)->get('/admin/warehouses')->assertOk();
        $this->actingAs($clerk)->get('/admin/users')->assertForbidden();
        $this->actingAs($clerk)->get('/admin/checkers')->assertForbidden();
        $this->actingAs($clerk)->get('/admin/roles')->assertForbidden();
        Livewire::actingAs($clerk)->test(UserForm::class)->assertForbidden(); // also on Livewire requests

        // documents: only the ticked type
        $receiving = Document::create(['type' => 'receiving', 'user_id' => $clerk->id]);
        $stock = Document::create(['type' => 'stocktaking', 'user_id' => $clerk->id]);
        $this->actingAs($clerk)->get('/admin/documents')->assertOk()->assertSee('Qabul qilish')->assertDontSee('Inventarizatsiya');
        $this->actingAs($clerk)->get("/admin/documents/{$receiving->id}")->assertOk();
        $this->actingAs($clerk)->get("/admin/documents/{$stock->id}")->assertForbidden();

        // the PDA menu follows the role too
        $this->actingAs($clerk)->get('/')->assertOk()->assertSee('Qabul qilish')->assertDontSee('Narxni tekshirish')->assertSee('Boshqaruv paneli');
        $this->actingAs($clerk)->get('/m/pricecheck')->assertForbidden();

        // a PDA-only role has no admin panel and lands on the PDA menu
        $pda = $this->user('Pda');
        $this->actingAs($pda)->get('/admin')->assertForbidden();
        $this->assertSame(route('menu'), $pda->homeUrl());
        $this->assertSame(route('admin.home'), $this->adminUser()->homeUrl());
    }

    public function test_the_administrator_role_is_fixed_and_a_role_in_use_cannot_be_deleted(): void
    {
        $boss = $this->adminUser();
        $adminRole = Role::where('is_admin', true)->first();
        $custom = Role::create(['name' => 'Vaqtinchalik', 'permissions' => ['admin.items']]);
        $this->user('Someone', $custom);

        // saving the administrator role changes only its name, never its permissions
        Livewire::actingAs($boss)->test(RoleForm::class, ['record' => $adminRole->id])->set('perms', ['admin.items'])->call('save');
        $this->assertTrue($adminRole->fresh()->is_admin);
        $this->assertSame([], $adminRole->fresh()->permissions ?? []);

        Livewire::actingAs($boss)->test(RoleIndex::class)->call('delete', $adminRole->id)->assertSee('o‘chirib bo‘lmaydi');
        Livewire::actingAs($boss)->test(RoleIndex::class)->call('delete', $custom->id)->assertSee('foydalanuvchilarga berilgan');
        $this->assertNotNull(Role::find($custom->id));

        User::where('role_id', $custom->id)->update(['role_id' => Role::where('name', 'PDA operator')->value('id')]);
        Livewire::actingAs($boss)->test(RoleIndex::class)->call('delete', $custom->id);
        $this->assertNull(Role::find($custom->id));

        // only administrators manage roles
        Livewire::actingAs($this->user('Pda'))->test(RoleIndex::class)->assertForbidden();
    }

    // ---------------------------------------------------------------- PDA user flag

    public function test_a_pda_user_always_lands_on_the_pda_view(): void
    {
        $role = Role::create(['name' => 'Ikkala', 'permissions' => ['admin.items', 'pda.receiving']]);
        $both = $this->user('Both', $role);
        $this->assertSame(route('menu'), $both->homeUrl()); // has a PDA menu, so PDA anyway

        $deskOnly = $this->user('Desk', Role::create(['name' => 'Faqat stol', 'permissions' => ['admin.items']]));
        $this->assertSame(route('admin.home'), $deskOnly->homeUrl());

        $admin = $this->adminUser();
        $this->assertSame(route('admin.home'), $admin->homeUrl());
        $admin->update(['pda_user' => true]);
        $this->assertSame(route('menu'), $admin->fresh()->homeUrl());

        // PIN, badge and the URL all respect it, even with a remembered admin address
        session()->put('url.intended', url('/admin/items'));
        Livewire::test(Login::class)->call('choose', $admin->id)->call('submitPin', '2580')->assertRedirect(route('menu'));
        auth()->logout();
        $this->get('/login/badge/'.$admin->badge_token)->assertRedirect(route('menu'));

        // the user form has the tick
        Livewire::actingAs($this->adminUser())->test(UserForm::class, ['record' => $deskOnly->id])->assertSet('pda_user', false)->set('pda_user', true)->call('save');
        $this->assertTrue($deskOnly->fresh()->pda_user);
    }

    // ---------------------------------------------------------------- the user's own shop

    public function test_a_shop_user_works_from_their_shop_but_can_pick_the_destination(): void
    {
        $shopA = \App\Models\Warehouse::create(['code' => 'A', 'name' => 'Do‘kon A', 'price_type_id' => ($retail = \App\Models\PriceType::create(['name' => 'Chakana']))->id]);
        $shopB = \App\Models\Warehouse::create(['code' => 'B', 'name' => 'Do‘kon B']);
        $user = $this->user('Ali');
        $user->update(['warehouse_id' => $shopA->id]);

        $this->actingAs($user);
        $page = Livewire::test(\App\Livewire\Pda\Docs\Create::class, ['type' => 'movement']);
        $page->assertSet('header.warehouse_id', (string) $shopA->id)->assertSee('disabled');

        // whatever the browser sends, the source is the shop; the destination is any other warehouse
        $page->set('header.warehouse_id', (string) $shopB->id)->set('header.warehouse_to_id', (string) $shopA->id)->call('save')->assertHasErrors(); // the destination equals the shop (the forced source) -> refused
        $page->set('header.warehouse_to_id', (string) $shopB->id)->assertSee('Do‘kon B');
        $other = \App\Models\Warehouse::create(['code' => 'C', 'name' => 'Ombor C']);
        $page->set('header.warehouse_to_id', (string) $other->id)->call('save');
        $doc = Document::latest('id')->first();
        $this->assertSame($shopA->id, $doc->warehouse_id);
        $this->assertSame($other->id, $doc->warehouse_to_id);

        // a user without a shop still chooses freely
        $free = $this->user('Free');
        $this->actingAs($free);
        Livewire::test(\App\Livewire\Pda\Docs\Create::class, ['type' => 'movement'])->set('header.warehouse_id', (string) $shopB->id)->set('header.warehouse_to_id', (string) $other->id)->call('save');
        $this->assertSame($shopB->id, Document::latest('id')->first()->warehouse_id);
    }

    public function test_a_shop_user_sees_only_the_stock_and_prices_of_their_shop(): void
    {
        $retail = \App\Models\PriceType::create(['name' => 'Chakana']);
        $shopA = \App\Models\Warehouse::create(['code' => 'A', 'name' => 'Do‘kon A', 'price_type_id' => $retail->id]);
        $shopB = \App\Models\Warehouse::create(['code' => 'B', 'name' => 'Do‘kon B']);
        $item = \App\Models\Item::create(['code' => '7', 'barcode' => '4780000000077', 'name' => 'Sut', 'unit' => 'dona', 'price' => 10000, 'is_active' => true]);
        \App\Models\ItemPrice::create(['item_id' => $item->id, 'price_type_id' => $retail->id, 'price' => 12000]);
        \App\Models\ItemStock::create(['item_id' => $item->id, 'warehouse_id' => $shopA->id, 'qty' => 3]);
        \App\Models\ItemStock::create(['item_id' => $item->id, 'warehouse_id' => $shopB->id, 'qty' => 40]);

        $ali = $this->user('Ali');
        $ali->update(['warehouse_id' => $shopA->id]);
        $free = $this->user('Free');

        // directory: the shop's stock and price list
        Livewire::actingAs($ali)->test(\App\Livewire\Pda\Directory::class, ['q' => 'Sut'])->assertSee('Qoldiq: 3')->assertSee('12 000')->assertDontSee('43');
        Livewire::actingAs($free)->test(\App\Livewire\Pda\Directory::class, ['q' => 'Sut'])->assertSee('Qoldiq: 43')->assertSee('10 000');

        // item entry: the shop's price is proposed and only its stock is listed
        $doc = Document::create(['type' => 'receiving', 'user_id' => $ali->id, 'warehouse_id' => $shopA->id]);
        Livewire::actingAs($ali)->test(\App\Livewire\Pda\Docs\Entry::class, ['record' => $doc->id, 'item' => $item->id])
            ->assertSet('price', '12000')->assertSee('Do‘kon A')->assertDontSee('Do‘kon B');

        // admin item card: other shops' stock and price lists stay hidden and untouched
        $other = \App\Models\PriceType::create(['name' => 'Ulgurji']);
        \App\Models\ItemPrice::create(['item_id' => $item->id, 'price_type_id' => $other->id, 'price' => 9000]);
        $role = Role::create(['name' => 'Do‘kon admini', 'permissions' => ['admin.items']]);
        $manager = $this->user('Mgr', $role);
        $manager->update(['warehouse_id' => $shopA->id]);
        Livewire::actingAs($manager)->test(\App\Livewire\Admin\Items\Form::class, ['record' => $item->id])
            ->assertSee('Chakana')->assertDontSee('Ulgurji')->assertDontSee('Do‘kon B');

        // the user form saves the shop
        Livewire::actingAs($this->adminUser())->test(UserForm::class, ['record' => $free->id])->set('warehouse_id', (string) $shopB->id)->call('save');
        $this->assertSame($shopB->id, $free->fresh()->warehouse_id);
    }
}

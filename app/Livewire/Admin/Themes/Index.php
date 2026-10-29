<?php

namespace App\Livewire\Admin\Themes;

use App\Livewire\AdminPage;
use App\Models\CheckerTheme;

/** Price checker screen designs: list, choose the default, delete unused ones. */
class Index extends AdminPage
{
    public function makeDefault(int $id): void
    {
        CheckerTheme::findOrFail($id)->makeDefault();
        $this->ok('Asosiy dizayn o‘zgartirildi.');
    }

    public function delete(int $id): void
    {
        $theme = CheckerTheme::withCount('checkers')->findOrFail($id);

        if ($theme->checkers_count > 0) {
            $this->fail('Bu dizayn narx tekshirgichlarda ishlatilmoqda. Avval ularga boshqa dizayn tanlang.');

            return;
        }

        $wasDefault = $theme->is_default;
        $theme->delete();
        $wasDefault && CheckerTheme::default()?->makeDefault();
        $this->ok('Dizayn o‘chirildi.');
    }

    public function render()
    {
        return $this->page('livewire.admin.themes.index', ['themes' => CheckerTheme::withCount('checkers')->orderBy('name')->get()]);
    }
}

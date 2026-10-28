<?php

namespace App\Livewire\Admin\Messages;

use App\Livewire\AdminPage;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/** Admin -> operators messages (one-way). A broadcast creates one recipient row per user, so read receipts are per user. */
class Form extends AdminPage
{
    public string $title = '';

    public string $body = '';

    public string $priority = 'normal';

    public string $audience = 'all';

    /** @var list<int|string> */
    public array $users = [];

    public function send()
    {
        $data = $this->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string|max:5000',
            'priority' => 'required|in:normal,urgent',
            'audience' => 'required|in:all,selected',
            'users' => 'required_if:audience,selected|array',
            'users.*' => 'integer',
        ], [], ['title' => 'Sarlavha', 'body' => 'Matn', 'users' => 'Qabul qiluvchilar']);

        $recipients = User::operators()
            ->when($data['audience'] === 'selected', fn ($q) => $q->whereIn('id', $data['users'] ?? []))
            ->pluck('id');

        if ($recipients->isEmpty()) {
            $this->addError('users', 'Qabul qiluvchi topilmadi.');

            return;
        }

        DB::transaction(function () use ($data, $recipients) {
            $message = Message::create([
                'sender_id' => auth()->id(),
                'title' => $data['title'],
                'body' => $data['body'],
                'priority' => $data['priority'],
            ]);

            $message->recipients()->createMany($recipients->map(fn ($id) => ['user_id' => $id])->all());
        });

        session()->flash('status', "Xabar {$recipients->count()} ta foydalanuvchiga yuborildi.");

        return $this->redirect(route('admin.messages.index'));
    }

    public function render()
    {
        return $this->page('livewire.admin.messages.form', ['operators' => User::operators()->orderBy('name')->get()]);
    }
}
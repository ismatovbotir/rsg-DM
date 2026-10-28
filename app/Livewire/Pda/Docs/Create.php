<?php

namespace App\Livewire\Pda\Docs;

use App\Livewire\Pda\Concerns\AuthorizesDocuments;
use App\Livewire\Pda\Concerns\HasDocumentHeader;
use App\Livewire\PdaPage;
use App\Models\Document;
use App\Models\Task;
use Illuminate\Support\Arr;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;

/** Step 1 of a document: the header form. The date is always today. */
class Create extends PdaPage
{
    use AuthorizesDocuments, HasDocumentHeader;

    #[Locked]
    public string $type = '';

    /** Started from an assigned task: link it to the new document. */
    #[Url]
    public ?int $task = null;

    public function mount(string $type): void
    {
        $this->assertType($type);
        $this->type = $type;
        $this->fillHeader($type, ['doc_date' => now()->toDateString()]);
    }

    public function save()
    {
        $this->assertType($this->type);

        $data = $this->validatedHeader($this->type);
        $doc = Document::create(['type' => $this->type, 'user_id' => auth()->id(), 'doc_date' => now()->toDateString()] + $data);

        if ($task = Task::openFor(auth()->id())->whereIn('status', [Task::ACCEPTED, Task::IN_PROGRESS])->find($this->task)) {
            $task->documents()->attach($doc->id);
            $task->update(['status' => Task::IN_PROGRESS]);
        }

        return $this->redirect(route('docs.show', $doc->id));
    }

    public function render()
    {
        return $this->page('livewire.pda.docs.create', [
            'title' => Document::types()[$this->type],
            'fields' => Arr::except(Document::headerFieldsFor($this->type), 'doc_date'),
        ] + $this->headerViewData());
    }
}
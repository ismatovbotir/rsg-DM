<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    public const NEW = 'new';
    public const ACCEPTED = 'accepted';
    public const IN_PROGRESS = 'in_progress';
    public const DONE = 'done';
    public const DECLINED = 'declined';
    public const CANCELLED = 'cancelled';

    /** Statuses in which the task still needs the operator's attention. */
    public const OPEN = [self::NEW, self::ACCEPTED, self::IN_PROGRESS];

    protected $fillable = [
        'title', 'description', 'assignee_id', 'created_by', 'priority', 'due_at',
        'status', 'doc_type', 'accepted_at', 'completed_at', 'cancel_reason',
    ];

    protected $casts = ['due_at' => 'datetime', 'accepted_at' => 'datetime', 'completed_at' => 'datetime'];

    public function scopeOpenFor($query, int $userId)
    {
        return $query->where('assignee_id', $userId)->whereIn('status', self::OPEN);
    }

    public function isOpen(): bool
    {
        return in_array($this->status, self::OPEN, true);
    }

    public function isOverdue(): bool
    {
        return $this->isOpen() && $this->due_at && $this->due_at->isPast();
    }

    public function getStatusLabelAttribute(): string
    {
        return [
            self::NEW => 'Yangi', self::ACCEPTED => 'Qabul qilingan', self::IN_PROGRESS => 'Jarayonda',
            self::DONE => 'Bajarilgan', self::DECLINED => 'Rad etilgan', self::CANCELLED => 'Bekor qilingan',
        ][$this->status] ?? $this->status;
    }

    public function getPriorityLabelAttribute(): string
    {
        return ['low' => 'Past', 'normal' => 'Oddiy', 'high' => 'Shoshilinch'][$this->priority] ?? $this->priority;
    }

    public function getDocTypeLabelAttribute(): ?string
    {
        return $this->doc_type ? (Document::types()[$this->doc_type] ?? $this->doc_type) : null;
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function documents()
    {
        return $this->belongsToMany(Document::class, 'task_documents');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventTask extends Model {
    use HasFactory;

    protected $table = 'event_tasks';

    protected $primaryKey = 'taskId';

    protected $fillable = [
        'task_name',
        'due_date',
        'priority',
        'is_completed',
        'eventId',
    ];

    protected $casts = [
        'due_date' => 'datetime',
        'priority' => 'string',
        'is_completed' => 'boolean',
    ];

    /**
     * العلاقة مع الفعالية
     */
    public function event() {
        return $this->belongsTo(Event::class, 'eventId');
    }
}

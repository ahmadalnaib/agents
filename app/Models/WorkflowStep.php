<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkflowStep extends Model
{
    //
    protected $fillable = [
        'workflow_id',
        'step_number',
        'title',
        'description',
        'status',
        'qa_conversation_id',
    ];

    public function workflow()
    {
        return $this->belongsTo(Workflow::class);
    }
}

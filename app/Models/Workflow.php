<?php

namespace App\Models;

use App\Models\WorkflowStep;
use Illuminate\Database\Eloquent\Model;

class Workflow extends Model
{
    //
    protected $fillable = [
        'user_id',
        'title',
        'input',
        'status',
    ];

    public function steps()
    {
        return $this->hasMany(WorkflowStep::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

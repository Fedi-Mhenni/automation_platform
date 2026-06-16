<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExecutionLog extends Model
{
    protected $fillable = ['workflow_id', 'node_id', 'action', 'message', 'status'];
    
    public function workflow() { return $this->belongsTo(Workflow::class); }
}
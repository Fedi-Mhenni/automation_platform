<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Workflow extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'user_id',
        'is_active',
        'nodes_structure',
        'token'
    ];

    protected $casts = [
        'nodes_structure' => 'array',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $workflow) {
            if (empty($workflow->token)) {
                $workflow->token = (string) Str::uuid();
            }

            if (empty($workflow->nodes_structure)) {
                $workflow->nodes_structure = [
                    'nodes' => [],
                    'edges' => [],
                ];
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function executionLogs()
    {
        return $this->hasMany(ExecutionLog::class);
    }
    
    public function getGraph(): array
    {
        return $this->nodes_structure ?? [
            'nodes' => [],
            'edges' => [],
        ];
    }

    public function setGraph(array $graph): void
    {
        $this->nodes_structure = [
            'nodes' => $graph['nodes'] ?? [],
            'edges' => $graph['edges'] ?? [],
        ];
    }

    public function getNodes(): array
    {
        return $this->getGraph()['nodes'];
    }

    public function getEdges(): array
    {
        return $this->getGraph()['edges'];
    }

}
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class SaveWorkflowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->route('workflow') && $this->route('workflow')->user_id === Auth::id();
    }

    public function rules(): array
    {
        return [
            'nodes'              => ['present', 'array'],
            'edges'              => ['present', 'array'],
            'meta'               => ['present', 'array'],
            'meta.startNodeId'   => ['nullable', 'string'],
            'nodes.*.id'         => ['required', 'string'],
            'nodes.*.type'       => ['required', 'string'],
            'nodes.*.payload'    => ['nullable'],
            'edges.*.source'     => ['required', 'string'],
            'edges.*.target'     => ['required', 'string'],
            'edges.*.condition'  => ['nullable', 'string'],
        ];
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutputFile extends Model
{
    protected $fillable = ['output_id', 'file_path', 'file_name'];

    /** @return BelongsTo<Output, $this> */
    public function output(): BelongsTo
    {
        return $this->belongsTo(Output::class);
    }
}


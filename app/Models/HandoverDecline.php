<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HandoverDecline extends Model
{
    protected $fillable = [
        'waste_handover_request_id',
        'provider_slug',
    ];

    public function handover()
    {
        return $this->belongsTo(WasteHandoverRequest::class, 'waste_handover_request_id');
    }
}

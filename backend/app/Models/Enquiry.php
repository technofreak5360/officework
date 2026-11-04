<?php

namespace App\Models;

use App\Enquiries\EmailStatus;
use Illuminate\Database\Eloquent\Model;

class Enquiry extends Model
{
    protected $fillable = [
        'product_id',
        'product_title',
        'name',
        'email',
        'phone',
        'message',
        'email_status',
    ];

    protected function casts(): array
    {
        return [
            'email_status' => EmailStatus::class,
        ];
    }
}

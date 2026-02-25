<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = [
        'admin_id',
        'action',
        'entity_type',
        'entity_id',
        'old_data',
        'new_data',
        'ip',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'old_data' => 'array',
            'new_data' => 'array',
        ];
    }

    public function admin()
    {
        return $this->belongsTo(AdminUser::class, 'admin_id');
    }
}

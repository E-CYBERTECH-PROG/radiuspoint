<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class Router extends Model {
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'name', 'ip_address', 'api_username', 'api_password',
        'status', 'port_configuration', 'last_seen', 'public_token', 'routeros_version', 'board_model'
    ];

    // Cast port_configuration as an array automatically
    protected $casts = [
        'port_configuration' => 'array',
        'last_seen' => 'datetime'
    ];
}
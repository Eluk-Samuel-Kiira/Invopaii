<?php

namespace App\Models\Platform;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AnnouncementDismissal extends Model
{
    public $timestamps = false;

    protected $fillable = ['announcement_id', 'user_id', 'dismissed_at'];

    protected $casts = ['dismissed_at' => 'datetime'];

    public function announcement() { return $this->belongsTo(Announcement::class); }
    public function user() { return $this->belongsTo(User::class); }
}
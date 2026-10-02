<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class SmsTemplate extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'message',
        'category',
        'description',
        'placeholders',
        'is_active',
        'is_system',
        'created_by',
    ];

    protected $casts = [
        'placeholders' => 'array',
        'is_active' => 'boolean',
        'is_system' => 'boolean',
    ];

    /**
     * Available categories for templates.
     */
    public const CATEGORIES = [
        'general' => 'General',
        'reminder' => 'Reminder',
        'alert' => 'Alert',
        'action' => 'Action Required',
        'account' => 'Account',
        'security' => 'Security',
        'onboarding' => 'Onboarding',
        'election' => 'Election',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($template) {
            if (empty($template->slug)) {
                $template->slug = Str::slug($template->name);
            }
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope to only active templates.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by category.
     */
    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Render the template with given data.
     */
    public function render(array $data = []): string
    {
        $message = $this->message;

        foreach ($data as $key => $value) {
            $message = str_replace("{{$key}}", $value, $message);
            $message = str_replace("{" . strtoupper($key) . "}", $value, $message);
        }

        return $message;
    }

    /**
     * Get character count of the message.
     */
    public function getCharacterCountAttribute(): int
    {
        return strlen($this->message);
    }

    /**
     * Get estimated SMS parts.
     */
    public function getSmsPartsAttribute(): int
    {
        $length = strlen($this->message);
        if ($length <= 160) {
            return 1;
        }
        return (int) ceil($length / 153);
    }
}

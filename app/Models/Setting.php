<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'group', 'type', 'is_encrypted', 'is_public'];

    protected function casts(): array
    {
        return [
            'is_encrypted' => 'boolean',
            'is_public'    => 'boolean',
        ];
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember("setting:{$key}", 3600, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();
            return $setting ? $setting->typedValue() : $default;
        });
    }

    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => (string) $value]);
        Cache::forget("setting:{$key}");
    }

    public function typedValue(): mixed
    {
        return match ($this->type) {
            'boolean' => (bool) $this->value,
            'integer' => (int) $this->value,
            'float'   => (float) $this->value,
            'array'   => json_decode($this->value, true),
            default   => $this->value,
        };
    }

    public function scopeGroup($query, string $group) { return $query->where('group', $group); }
    public function scopePublic($query) { return $query->where('is_public', true); }
}

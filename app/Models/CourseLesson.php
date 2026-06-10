<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseLesson extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'judul',
        'konten',
        'video_youtube',
        'durasi_menit',
        'urutan',
        'aktif',
    ];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function progress(): HasMany
    {
        return $this->hasMany(CourseProgress::class);
    }

    public function embedYoutube(): ?string
    {
        if (blank($this->video_youtube)) {
            return null;
        }

        $url = trim($this->video_youtube);
        $id = null;

        if (preg_match('/youtu\.be\/([a-zA-Z0-9_-]+)/', $url, $match)) {
            $id = $match[1];
        }

        if (! $id && preg_match('/youtube\.com\/watch\?v=([a-zA-Z0-9_-]+)/', $url, $match)) {
            $id = $match[1];
        }

        if (! $id && preg_match('/youtube\.com\/embed\/([a-zA-Z0-9_-]+)/', $url, $match)) {
            $id = $match[1];
        }

        return $id ? 'https://www.youtube.com/embed/'.$id : null;
    }
}

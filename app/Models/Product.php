<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    public const CATEGORIES = [
        'buku' => 'Buku',
        'alat_tulis' => 'Alat Tulis',
        'atribut_sekolah' => 'Atribut Sekolah',
        'lainnya' => 'Lainnya',
    ];

    protected $fillable = [
        'name', 'slug', 'description', 'price', 'stock', 'image_path', 'category', 'custom_category', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'stock' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order')->orderBy('id');
    }

    public function primaryImage(): HasOne
    {
        return $this->hasOne(ProductImage::class)->where('is_primary', true);
    }

    public function primaryImagePath(): ?string
    {
        if ($this->relationLoaded('images')) {
            return $this->images->firstWhere('is_primary', true)?->image_path
                ?? $this->images->first()?->image_path
                ?? $this->image_path;
        }

        return $this->primaryImage()->value('image_path')
            ?? $this->images()->value('image_path')
            ?? $this->image_path;
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function categoryLabel(): string
    {
        if ($this->category === 'lainnya' && filled($this->custom_category)) {
            return $this->custom_category;
        }

        return self::CATEGORIES[$this->category] ?? Str::headline($this->category);
    }
}

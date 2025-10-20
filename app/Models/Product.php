<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_specialmenu',
        'id_category',
        'name',
        'image',
        'description',
        'prices'
    ];
    protected $casts = [
        'prices' => 'array',
    ];
    protected $table = 'products';

    // Relationship with Special Menu
    public function specialMenu()
    {
        return $this->belongsTo(SpecialMenu::class, 'id_specialmenu');
    }

    // Relationship with Category
    public function category()
    {
        return $this->belongsTo(Category::class, 'id_category');
    }
}

<?php

namespace App\Models\Besoin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Departement extends Model
{
    protected $table = 'besoin_departements';

    protected $fillable = ['nom', 'code', 'parent_id'];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Departement::class, 'parent_id');
    }

    public function enfants(): HasMany
    {
        return $this->hasMany(Departement::class, 'parent_id');
    }

    public function postes(): HasMany
    {
        return $this->hasMany(Poste::class);
    }
}
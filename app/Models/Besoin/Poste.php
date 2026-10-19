<?php

namespace App\Models\Besoin;

use Database\Factories\Besoin\PosteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Poste extends Model
{
    /** @use HasFactory<PosteFactory> */
    use HasFactory;

    protected $table = 'besoin_postes';

    protected $fillable = ['intitule', 'description', 'departement_id', 'actif'];

    public function departement(): BelongsTo
    {
        return $this->belongsTo(Departement::class);
    }
}
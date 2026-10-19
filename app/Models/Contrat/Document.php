<?php

namespace App\Models\Contrat;

use App\Enums\TypeDocument;
use App\Models\User;
use Database\Factories\Contrat\DocumentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Document extends Model
{
    /** @use HasFactory<DocumentFactory> */
    use HasFactory;

    protected $table = 'contrat_documents';

    protected $fillable = [
        'documentable_type',
        'documentable_id',
        'type',
        'nom',
        'chemin',
        'disque',
        'televerse_par_id',
    ];

    protected function casts(): array
    {
        return [
            'type' => TypeDocument::class,
        ];
    }

    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function televersePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'televerse_par_id');
    }
}

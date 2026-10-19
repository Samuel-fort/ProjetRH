<?php

namespace App\Models\Recrutement;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Note (sur 20) et commentaire d'un évaluateur sur une candidature. */
class EvaluationCandidat extends Model
{
    use HasFactory;

    protected $table = 'evaluation_candidats';

    protected $fillable = ['candidature_id', 'entretien_id', 'evaluateur_id', 'note', 'commentaire'];

    protected function casts(): array
    {
        return ['note' => 'decimal:2'];
    }

    public function candidature(): BelongsTo
    {
        return $this->belongsTo(Candidature::class);
    }

    public function entretien(): BelongsTo
    {
        return $this->belongsTo(Entretien::class);
    }

    public function evaluateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluateur_id');
    }
}

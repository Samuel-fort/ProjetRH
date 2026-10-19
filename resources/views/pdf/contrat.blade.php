<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Contrat de travail</title>
    <style>
        /* DejaVu Sans est fournie avec dompdf et gère bien les accents. */
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #222; line-height: 1.6; }
        h1 { text-align: center; font-size: 18px; margin-bottom: 4px; }
        .sous-titre { text-align: center; color: #666; margin-bottom: 28px; }
        h2 { font-size: 13px; margin: 18px 0 4px; border-bottom: 1px solid #ccc; }
        table.infos td { padding: 3px 8px 3px 0; vertical-align: top; }
        table.infos td:first-child { width: 150px; color: #555; }
        .signatures { margin-top: 50px; width: 100%; }
        .signatures td { width: 50%; height: 90px; vertical-align: top; }
    </style>
</head>
<body>
    <h1>CONTRAT DE TRAVAIL</h1>
    <div class="sous-titre">
        {{ $d['employeur'] }}
        @if($d['matricule']) — Matricule {{ $d['matricule'] }} @endif
    </div>

    <h2>Entre les parties</h2>
    <p>
        L'employeur <strong>{{ $d['employeur'] }}</strong> et le salarié(e)
        <strong>{{ $d['salarie'] }}</strong> ({{ $d['email'] }}).
    </p>

    <h2>Article 1 — Engagement et poste</h2>
    <p>Le salarié est engagé au poste de <strong>{{ $d['poste'] }}</strong>.</p>

    <h2>Article 2 — Conditions de travail</h2>
    <table class="infos">
        <tr><td>Date de début</td><td>{{ $d['date_debut'] }}</td></tr>
        <tr><td>Lieu de travail</td><td>{{ $d['lieu_travail'] }}</td></tr>
        <tr><td>Horaires</td><td>{{ $d['horaires'] }}</td></tr>
        <tr><td>Rémunération mensuelle brute</td><td>{{ $d['salaire'] }} Ar</td></tr>
    </table>

    <p style="margin-top: 24px;">Fait en deux exemplaires, le {{ $d['date_edition'] }}.</p>

    <table class="signatures">
        <tr>
            <td>L'employeur<br><small>(nom, signature)</small></td>
            <td>Le salarié<br><small>(« lu et approuvé », signature)</small></td>
        </tr>
    </table>
</body>
</html>

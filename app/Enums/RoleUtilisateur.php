<?php

namespace App\Enums;

enum RoleUtilisateur: string
{
    case Manager = 'manager';
    case Rh = 'rh';
    case Finance = 'finance';
    case Direction = 'direction';
    case Admin = 'admin';
}

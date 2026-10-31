<?php

namespace App\Filament\Resources\AuditLogs\Schemas;

use Filament\Schemas\Schema;

/** Le journal d'audit est en lecture seule : aucun formulaire. */
class AuditLogForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([]);
    }
}

<?php

namespace App\Infrastructure\DB;

final class SchemaSqlHelper
{
    public function __construct(
        private string $schema
    ) {}

    public function table(string $table): string
    {
        return $this->schema . '.' . $table;
    }
}

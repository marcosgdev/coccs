<?php

namespace GestContratos\Models;

use GestContratos\Core\Model;

final class GenericModel extends Model
{
    public function __construct(string $table, array $fillable)
    {
        $this->table = $table;
        $this->fillable = $fillable;
    }
}

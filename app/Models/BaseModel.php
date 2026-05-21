<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BaseModel extends Model
{
    /**
     * Default pagination count.
     */
    protected static int $defaultPerPage = 15;

    /**
     * Maximum allowed pagination count.
     */
    protected static int $maxPerPage = 100;


    /**
     * Get dynamic per page from request.
     */
    public function getPerPage(): int
    {
        $perPage = request()->get('per_page');

        if ( ! is_numeric($perPage)) {
            return static::$defaultPerPage;
        }

        $perPage = (int)$perPage;

        if ($perPage <= 0) {
            return static::$defaultPerPage;
        }

        return min($perPage, static::$maxPerPage);
    }
}
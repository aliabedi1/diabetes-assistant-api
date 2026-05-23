<?php

namespace App\Models;

use Illuminate\Container\EntryNotFoundException;
use Illuminate\Contracts\Container\CircularDependencyException;
use Illuminate\Database\Eloquent\Model;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

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
     *
     * @return int
     * @throws EntryNotFoundException
     * @throws CircularDependencyException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
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
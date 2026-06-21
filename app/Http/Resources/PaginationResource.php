<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PaginationResource extends JsonResource
{
    protected $rawResource;


    public function __construct($resource)
    {
        $this->rawResource = $resource;
        parent::__construct($resource);
    }


    public function toArray($request)
    {
        return [
            'items'      => $this->collection->map(fn ($item) => $item->toArray($request))->values()->all(),
            'pagination' => [
                'current_page' => $this->rawResource->currentPage(),
                'from'         => $this->rawResource->firstItem(),
                'last_page'    => $this->rawResource->lastPage(),
                'per_page'     => $this->rawResource->perPage(),
                'to'           => $this->rawResource->lastItem(),
                'total'        => $this->rawResource->total(),
            ],
            'links'      => [
                'first' => $this->rawResource->url(1),
                'last'  => $this->rawResource->url($this->rawResource->lastPage()),
                'prev'  => $this->rawResource->previousPageUrl(),
                'next'  => $this->rawResource->nextPageUrl(),
            ]
        ];
    }
}
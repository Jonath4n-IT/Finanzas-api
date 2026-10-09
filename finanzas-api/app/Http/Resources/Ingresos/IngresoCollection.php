<?php

namespace App\Http\Resources\Ingresos;

use Illuminate\Http\Resources\Json\ResourceCollection;

class IngresoCollection extends ResourceCollection
{
    /** @var class-string<IngresoResource> */
    public $collects = IngresoResource::class;
}

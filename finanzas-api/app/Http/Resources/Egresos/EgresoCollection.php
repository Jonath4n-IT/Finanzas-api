<?php

namespace App\Http\Resources\Egresos;

use Illuminate\Http\Resources\Json\ResourceCollection;

class EgresoCollection extends ResourceCollection
{
    /** @var class-string<EgresoResource> */
    public $collects = EgresoResource::class;
}

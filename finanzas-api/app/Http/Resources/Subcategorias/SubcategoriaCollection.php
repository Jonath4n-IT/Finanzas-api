<?php

namespace App\Http\Resources\Subcategorias;

use Illuminate\Http\Resources\Json\ResourceCollection;

class SubcategoriaCollection extends ResourceCollection
{
    /** @var class-string<SubcategoriaResource> */
    public $collects = SubcategoriaResource::class;
}

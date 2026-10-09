<?php

namespace App\Http\Resources\Categorias;

use Illuminate\Http\Resources\Json\ResourceCollection;

class CategoriaCollection extends ResourceCollection
{
    /** @var class-string<CategoriaResource> */
    public $collects = CategoriaResource::class;
}

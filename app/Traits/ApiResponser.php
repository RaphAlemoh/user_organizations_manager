<?php

namespace App\Traits;

trait ApiResponser
{
    protected function jsonReponse($data, $code = 201)
    {
        return response()->json($data, $code);
    }
}

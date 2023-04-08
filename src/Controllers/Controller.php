<?php

namespace App\Http\Controllers;

use App\Utils\Builder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests, Builder;

    public function __construct()
    {
        if(isset($this->model)) {
            $this->model = new $this->model;

            $this->model_table = $this->model->getTable();

            $this->model_columns = Schema::getColumnListing($this->model_table);
        }
    }
}


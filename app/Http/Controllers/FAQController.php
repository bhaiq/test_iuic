<?php

namespace App\Http\Controllers;

use App\Models\FAQ;
use Illuminate\Http\Request;

class FAQController extends Controller
{
    public function _list()
    {
        return $this->response(FAQ::orderBy('index')->paginate(10)->toArray());
    }

    public function detail($id)
    {
        return $this->response(FAQ::findOrFail($id)->toArray());
    }
}

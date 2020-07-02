<?php

namespace App\Http\Controllers;

use App\Models\News;
use Illuminate\Http\Request;

class NewsController extends Controller
{
    public function _list(Request $request)
    {
        $types = $request->get('type', 0);
        $types = explode(',', $types);
        $res   = News::whereIn('type', $types)
            ->orderBy('id', 'desc')
            ->select('id', 'title', 'thumbnail', 'created_at')
            ->paginate($request->get('per_page', 10));
        return $this->response($res->toArray());
    }

    public function detail($id)
    {
        $new = News::find($id);
        return $this->response($new->toArray());
    }
}

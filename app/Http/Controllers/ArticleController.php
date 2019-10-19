<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Http\Request;

class ArticleController extends Controller
{
    public function _list(Request $request)
    {

        $type = $request->get('type', 0);

        $article = Article::where('type', $type)->orderBy('id', 'desc')->paginate($request->get('per_page'));
        $data    = $article->toArray();
        return $this->response($data);
        
    }
}

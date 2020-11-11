<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use App\Models\FeedbackComment;
use App\Services\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FeedbackController extends Controller
{
    public function _list()
    {
        Service::auth()->isLoginOrFail();
        $user = Service::auth()->getUser();
        $data = Feedback::whereUid($user->id)->orderBy('id', 'desc')->paginate(10);
        return $this->response($data->toArray());
    }

    public function detail($id)
    {
        Service::auth()->isLoginOrFail();
        $feedback = Feedback::findOrFail($id);
        $feedback->load('comment');
        return $this->response($feedback->toArray());
    }

    public function create(Request $request)
    {
        Service::auth()->isLoginOrFail();
        $this->validate($request->all(), [
            'description' => 'required|string|min:10',
            'img'         => 'required|array'
            ],[
             'description.required' => trans('api.description_cannot_empty'),
             'description.string' => trans('api.description_must_string'),
             'description.min' => trans('api.description_words_is')."10",
             'img.required' => trans('api.picture_cannot_empty'),
             'img.array' => trans('api.images_must_arrays'),
        ]);

        $data          = $request->only('description', 'img');
        $data['title'] = Str::limit($request->input('description'), 10) . '...';
        $data['uid']   = Service::auth()->getUser()->id;
        $feedback      = Feedback::create($data);
        $feedback->refresh();
        return $this->response($feedback->toArray());
    }

    public function comment($id, Request $request)
    {
        Service::auth()->isLoginOrFail();
        $this->validate($request->all(), [
            'description' => 'required|min:10'
            ],[
             'description.required' => trans('api.description_cannot_empty'),
             'description.min' => trans('api.description_words_is')."10",
        ]);
        $feedback         = Feedback::findOrFail($id);
        $feedback->status = Feedback::STATUS_STATUS_ON;
        $feedback->save();

        $feedback->comment()->create(['uid' => Service::auth()->getUser()->id, 'type' => FeedbackComment::TYPE_CUS, 'description' => $request->input('description')]);

        return $this->response($feedback->toArray());
    }
}

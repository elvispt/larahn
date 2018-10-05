<?php

namespace App\Http\Controllers;

use App\Repositories\Stories;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{

    protected $cacheTtl = 120;

    public function index()
    {
        $items = $this->getTopStories();

        return view('welcome', [
            'items' => $items,
        ]);
    }

    public function show(int $id)
    {
        $commentsKey = 'comments-view-data-story-' . $id;
        $story = collect($this->getTopStories())->first(function ($story) use ($id) {
            return $story->id === $id;
        });

        if (Cache::has($commentsKey)) {
            $comments = Cache::get($commentsKey);
        } else {
            $kids = $story->kids;
            $stories = new Stories();
            $comments = $stories->loadComments($kids);
            $comments = $stories->sortComments($comments, $kids);
            Cache::put($commentsKey, $comments, $this->cacheTtl);
        }

        return view('comments', [
            'story' => $story,
            'items' => $comments,
        ]);
    }

    protected function getTopStories(): array
    {
        $stories = new Stories();
        $ids = $stories->topStories();
        return $stories->fetch($ids);
    }
}

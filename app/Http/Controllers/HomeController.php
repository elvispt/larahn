<?php

namespace App\Http\Controllers;

use App\Repositories\Stories;

class HomeController extends Controller
{

    protected $cacheTtl = 30;

    public function index()
    {
        $items = $this->getTopStories();

        return view('welcome', [
            'items' => $items,
        ]);
    }

    public function show(int $id)
    {
        $story = collect($this->getTopStories())->first(function ($story) use ($id) {
            return $story->id === $id;
        });
        $kids = $story->kids;

        $stories = new Stories();
        $k = $stories->fetch($kids, 'comment');

        dd($k);
    }

    protected function getTopStories(): array
    {
        $stories = new Stories();
        $ids = $stories->topStories();
        return $stories->fetch($ids);
    }
}

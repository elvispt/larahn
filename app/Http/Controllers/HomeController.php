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
        $comments = $this->loadComments($kids);
        $comments = $this->sortComments($comments, $kids);
        return view('comments', [
            'story' => $story,
            'items' => $comments,
        ]);
    }

    private function loadComments($ids): array
    {
        $stories = new Stories();
        $comments = $stories->fetch($ids, 'comment');
        foreach ($comments as $comment) {
            $kids = data_get($comment, 'kids');
            if ($kids) {
                $comment->sub = $this->loadComments($kids);
            }
        }
        return $comments;
    }

    private function sortComments($comments, $ids): array
    {
        $list = [];
        foreach ($ids as $id) {
            foreach ($comments as $comment) {
                if ($comment->id === $id) {
                    $kids = data_get($comment, 'kids');
                    if ($kids) {
                        $comment->sub = $this->sortComments($comment->sub, $kids);
                    }
                    $list[] = $comment;
                    break;
                }
            }
            reset($comments);
        }
        return $list;
    }

    protected function getTopStories(): array
    {
        $stories = new Stories();
        $ids = $stories->topStories();
        return $stories->fetch($ids);
    }
}

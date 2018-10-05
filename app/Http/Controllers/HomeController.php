<?php

namespace App\Http\Controllers;

use App\Repositories\Stories;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{

    protected $cacheTtl = 30;

    public function index(Request $request)
    {
        $items = $this->getTopStories();

        return view('welcome', [
            'items' => $items,
        ]);
    }

    public function show(Request $request, int $id)
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
        $ids = $this->getTopStoriesIdList();
        $stories = new Stories();
        return $stories->fetch($ids);
    }

    protected function getTopStoriesIdList(): array
    {
        $key = 'topstories-ids';
        if (Cache::has($key)) {
            $topStories = Cache::get($key);
        } else {
            $url = "https://hacker-news.firebaseio.com/v0/topstories.json";

            $client = new Client();

            try {
                $res = $client->get($url);
            } catch (ClientException $e) {
                dd($e);
            }
            $topStories = json_decode($res->getBody()->getContents());
            Cache::put($key, $topStories, now()->addMinutes($this->cacheTtl));
        }
        return $topStories;
    }
}

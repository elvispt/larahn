<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
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

        $k = $this->getStories($kids, 'comment');

        dd($k);
    }

    protected function getTopStories(): array
    {
        $ids = $this->getTopStoriesIdList();
        return $this->getStories($ids);
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
            Cache::put($key, $topStories, 10);
        }
        return $topStories;
    }

    protected function getStories($ids, $type = 'story')
    {
        $stories = [];
        $key = 'story-';
        $requestIds = [];
        foreach ($ids as $id) {
            $storyKey = $key . $id;
            if (Cache::has($storyKey)) {
                $stories[] = Cache::get($storyKey);
            } else {
                $requestIds[] = $id;
            }
        }
        $requests = function ($ids, $uri) {
            foreach ($ids as $id) {
                $url = $uri . 'item/' . $id . '.json';
                yield new \GuzzleHttp\Psr7\Request('GET', $url);
            }
        };
        $client = new Client();
        $baseUri = 'https://hacker-news.firebaseio.com/v0/';
        $pool = new Pool($client, $requests($requestIds, $baseUri), [
            'concurrency' => '100',
            'fulfilled' => function (Response $response) use (&$stories, $key) {
                $contents = $response->getBody()->getContents();
                $json = null;
                try {
                    $json = \GuzzleHttp\json_decode($contents);
                    Cache::put($key . $json->id, $json, now()->addMinutes(10));
                } catch (\InvalidArgumentException $exception) {
                    dd($exception);
                }
                $stories[] = $json;
            },
            'rejected' => function ($reason, $index) {
                dump($reason, $index);
            },
        ]);
        $promise = $pool->promise();
        $promise->wait();

        $stories = collect($stories)->filter(function ($story) use ($type) {
            return data_get($story, 'type') === $type
                   && data_get($story, 'deleted', false) === false
                   && data_get($story, 'dead', false) === false;
        })->toArray();

        return $stories;
    }
}

<?php

namespace App\Repositories;

use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Cache;

class Stories
{
    protected $cacheTtl = 30;

    public function fetch($ids, $type = 'story')
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
                    Cache::put($key . $json->id, $json, now()->addMinutes($this->cacheTtl));
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

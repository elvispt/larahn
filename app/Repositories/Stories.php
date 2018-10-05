<?php

namespace App\Repositories;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Cache;

class Stories
{
    protected $cacheTtl = 60;

    /**
     * Gets a story details from a list of story ids. Stores each story into
     * it's own cache entry.
     *
     * @param array $ids
     * @param string $type
     *
     * @return array
     */
    public function fetch(array $ids, $type = 'story')
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

        $stories = collect($stories)
            ->filter(function ($story) use ($type) {
                return data_get($story, 'type') === $type
                       && data_get($story, 'deleted', false) === false
                       && data_get($story, 'dead', false) === false;
            })
            ->sortByDesc(function ($story) {
                return $story->score;
            })
            ->toArray();

        return $stories;
    }

    /**
     * Gets the top stories identifiers. Stores in cache.
     *
     * @return array
     */
    public function topStories(): array
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

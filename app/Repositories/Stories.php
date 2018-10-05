<?php

namespace App\Repositories;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Cache;

class Stories
{
    protected $storyTtl = 4320;
    protected $topStoriesTtl = 120;

    /**
     * Gets a story details from a list of story ids. Stores each story into
     * it's own cache entry.
     *
     * @param array  $ids
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
                    $storyKey = $key . $json->id;
                    Cache::put($storyKey, $json, now()->addMinutes($this->storyTtl + mt_rand(1, 1000)));
                } catch (\InvalidArgumentException $exception) {
                    dd($exception);
                } catch (Exception $exception) {
                    $json = null;
                }
                if ($json) {
                    $stories[] = $json;
                }
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
            });
        if ($type === 'story') {
            $stories = $stories->sortByDesc(function ($story) {
                return $story->score;
            });
        }

        return $stories->values()->toArray();
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
            Cache::put($key, $topStories, now()->addMinutes($this->topStoriesTtl));
        }
        return $topStories;
    }

    public function loadComments($ids): array
    {
        $comments = $this->fetch($ids, 'comment');
        foreach ($comments as $comment) {
            $kids = data_get($comment, 'kids');
            if ($kids) {
                $comment->sub = $this->loadComments($kids);
            }
        }
        return $comments;
    }

    public function sortComments($comments, $ids): array
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
}

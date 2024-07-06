<?php

namespace Mollsoft\Telegram\Abstract;

use Illuminate\Http\Client\PendingRequest;

class ApiClient
{
    public function __construct(protected readonly PendingRequest $client)
    {
    }

    public function sendRequest(string $method, ?array $data = null, array|string|null $query = null): array
    {
        $request = clone $this->client;

        if ($data) {
            foreach ($data as $key => $value) {
                if (is_array($value)) {
                    $value = json_encode($value);
                }

                $request = $request->attach($key, $value);
            }

            $response = $request->post($method);
        }
        else {
            $response = $request->get($method, $query);
        }

        if (!$response->json('ok')) {
            throw new \Exception($response->json('description', $response->body()));
        }

        $response = $response->json('result');
        return is_array($response) ? $response : [$response];
    }
}

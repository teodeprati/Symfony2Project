<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use DateTimeImmutable;

class   MongoDBService
{
    private HttpClientInterface $httpClient;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    public function insertVisit(string $pageName) 
    {
        $this->httpClient->request('POST', 'https://us-east-2.aws.neurelo.com/rest/visits/__one', [
            'headers' => [
                'X-API-KEY' => 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCIsImtpZCI6ImFybjphd3M6a21zOnVzLWVhc3QtMjowMzczODQxMTc5ODQ6YWxpYXMvYjJjYWNlYWItQXV0aC1LZXkifQ.eyJlbnZpcm9ubWVudF9pZCI6Ijk1OWEyYWU2LWRhZDUtNGIxOS04NTQ3LTNhNDhkODViNGM1YSIsImdhdGV3YXlfaWQiOiJnd19iMmNhY2VhYi0yYTRlLTQ3YzYtOTlkZS1iNDM3M2I4NWE2MjIiLCJwb2xpY2llcyI6WyJSRUFEIiwiV1JJVEUiLCJVUERBVEUiLCJERUxFVEUiLCJDVVNUT00iXSwiaWF0IjoiMjAyNS0wMy0xMlQwMjo1MjoxMS42MTYyMjk1NTdaIiwianRpIjoiMzAzMWRlOTMtYzc1ZS00NmY1LTg2OWMtZTczZjFhNjk0MDBmIn0.1MEQprrTSrgM78dpwO3oE3imnM66i37tFjzIG8ASbz5pW87dlndg0KKb0nbPUshZLY7AjT4pILy3WSy78V65AR53vtUdi4ZxMgIMlTV7lbzgKbPvsNMhSfn3EHW7WiQTWe0qWMe_Rfi0zXkct9ZJU8aaYpy5SmBS_55fOzNZabUnuV9kYLdXQDLQ8XUTaAIY9tM_VJ6m0Z5CaMAfK_dCMchCjsYfOcsoAgQxSLkERYdhib0JsJyptgu5oL16cNTg2f9ZOMGEFGUypZGL_uUXqe7JfqXTKckIAH4X7jcbF2miGlnAhd9qh7-YfOwxbutLHoiz2FBN0MuXmNNPItB5kA',
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'pageName' => $pageName,
                'visitedAt' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
            ]
        ]);
    }
}
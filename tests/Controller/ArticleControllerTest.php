<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ArticleControllerTest extends WebTestCase
{
    public function testIndexPageIsAccessible(): void
    {
        $client = static::createClient();
        $client->request('GET', '/article/');

        $this->assertResponseIsSuccessful(); // Vérifie que le code HTTP est 200
    }
}
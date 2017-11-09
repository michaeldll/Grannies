<?php

namespace AppBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class JouerControllerControllerTest extends WebTestCase
{
    public function testNouvellepartie()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/nouvellePartie');
    }

    public function testDistribuercartes()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/distribuerCartes');
    }

    public function testPiocher()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/piocher');
    }

    public function testRevendiquerborne()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/revendiquerBorne');
    }

    public function testAfficherplateau()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/afficherPlateau');
    }

}

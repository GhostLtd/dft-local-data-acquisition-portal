<?php

namespace App\Tests\Functional\Frontend;

use App\Messenger\AlphagovNotify\LoginEmail;
use App\Tests\DataFixtures\Functional\Frontend\LoginFixtures;
use App\Tests\Functional\AbstractFunctionalTestCase;
use Symfony\Component\Panther\Exception\InvalidArgumentException;

class LoginTest extends AbstractFunctionalTestCase
{
    public function setUp(): void
    {
        $this->initialiseClientAndLoadFixtures([
            LoginFixtures::class,
        ]);
    }

    public function dataLogin(): array {
        return [
            ['test@example.com', true],
            ['pest@example.com', false],
        ];
    }

    /**
     * @dataProvider dataLogin
     */
    public function testLogin(string $email, bool $isValidEmail): void
    {
        $this->client->request('GET', '/logout');
        $this->client->request('GET', '/');
        $this->clickLinkContaining('Login');

        $signIn = $this->client->getCrawler()->selectButton('login_sign_in');

        $form = $signIn->form([
            'login[email]' => $email,
        ], 'POST');
        $this->client->submit($form);

        $this->assertEquals('Check your email', $this->getHeader());

        $message = $this->fetchMessage();

        if (!$isValidEmail) {
            $this->assertNull($message);
            return;
        }

        $this->assertInstanceOf(LoginEmail::class, $message);
        $link = $message->getPersonalisation()['login_link'] ?? null;

        $this->assertNotNull($link);
        $this->client->request('GET', $link);

        $this->assertEquals('Login using email link', $this->getHeader());
        $this->client->submitForm('login');

        $this->assertEquals('Local Authorities', $this->getHeader());
    }

    protected function getHeader(): string
    {
        return $this->client->getCrawler()->filter('h1')->text();
    }
}

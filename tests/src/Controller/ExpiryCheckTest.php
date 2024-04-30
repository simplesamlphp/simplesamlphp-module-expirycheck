<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\expirycheck\Controller;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Auth;
use SimpleSAML\Configuration;
use SimpleSAML\Error;
use SimpleSAML\HTTP\RunnableResponse;
use SimpleSAML\Module\expirycheck\Controller;
use SimpleSAML\Session;
use SimpleSAML\XHTML\Template;
use Symfony\Component\HttpFoundation\Request;

/**
 * Set of tests for the controllers in the "expirycheck" module.
 */
#[CoversClass(Controller\ExpiryCheck::class)]
class ExpiryCheckTest extends TestCase
{
    /** @var \SimpleSAML\Configuration */
    protected Configuration $config;

    /** @var \SimpleSAML\Session */
    protected Session $session;


    /**
     * Set up for each test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->config = Configuration::loadFromArray(
            [
                'module.enable' => ['expirycheck' => true],
            ],
            '[ARRAY]',
            'simplesaml'
        );

        $this->session = Session::getSessionFromRequest();

        Configuration::setPreLoadedConfig($this->config, 'config.php');
    }


    /**
     * Test that a missing SourceID results in an error-response
     *
     * @param string $endpoint
     * @return void
     */
    #[DataProvider('endpoints')]
    public function testMissingSourceId(string $endpoint): void
    {
        $request = Request::create(
            '/' . $endpoint,
            'GET'
        );

        $c = new Controller\ExpiryCheck($this->config, $this->session);

        $this->expectException(Error\BadRequest::class);
        $this->expectExceptionMessage("BADREQUEST('%REASON%' => 'Missing required StateId query parameter.')");
        call_user_func([$c, $endpoint], $request);
    }


    /**
     * @return array
     */
    public static function endpoints(): array
    {
        return [
            ['about2expire'],
            ['expired'],
        ];
    }


    /**
     * Test that accessing the expired-endpoint returns a Template
     *
     * @return void
     */
    public function testExpired(): void
    {
        $request = Request::create(
            '/expired',
            'GET',
            ['StateId' => 'abc123'],
        );

        $c = new Controller\ExpiryCheck($this->config, $this->session);
        $c->setAuthState(new class () extends Auth\State {
            public static function loadState(string $id, string $stage, bool $allowMissing = false): ?array
            {
                return ['expireOnDate' => 'someDate', 'netId' => 'someId'];
            }
        });
        $response = $c->expired($request);

        self::assertTrue($response->isSuccessful());
        self::assertInstanceOf(Template::class, $response);
    }


    /**
     * Test that accessing the about2expire-endpoint without yes-parameter returns a Template
     *
     * @return void
     */
    public function testAboutToExpire(): void
    {
        $request = Request::create(
            '/about2expire',
            'GET',
            ['StateId' => 'abc123'],
        );

        $c = new Controller\ExpiryCheck($this->config, $this->session);
        $c->setAuthState(new class () extends Auth\State {
            public static function loadState(string $id, string $stage, bool $allowMissing = false): ?array
            {
                return ['daysleft' => 10, 'expireOnDate' => 'someDate', 'netId' => 'someId'];
            }
        });
        $response = $c->about2expire($request);

        self::assertTrue($response->isSuccessful());
        self::assertInstanceOf(Template::class, $response);
    }


    /**
     * Test that accessing the about2expire-endpoint with yes-parameter returns a RunnableResponse
     *
     * @return void
     */
    public function testAboutToExpireYes(): void
    {
        $request = Request::create(
            '/about2expire',
            'GET',
            ['yes' => 'yes', 'StateId' => 'abc123']
        );

        $c = new Controller\ExpiryCheck($this->config, $this->session);
        $c->setAuthState(new class () extends Auth\State {
            public static function loadState(string $id, string $stage, bool $allowMissing = false): ?array
            {
                return [];
            }
        });
        $response = $c->about2expire($request);

        self::assertTrue($response->isSuccessful());
        self::assertInstanceOf(RunnableResponse::class, $response);
    }
}

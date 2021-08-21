<?php

declare(strict_types=1);

namespace SimpleSAML\Module\expirycheck\Controller;

use SimpleSAML\Auth;
use SimpleSAML\Configuration;
use SimpleSAML\Error;
use SimpleSAML\HTTP\RunnableResponse;
use SimpleSAML\Logger;
use SimpleSAML\Module;
use SimpleSAML\Session;
use SimpleSAML\XHTML\Template;
use Symfony\Component\HttpFoundation\Request;

use function htmlspecialchars;

/**
 * Controller class for the expirycheck module.
 *
 * This class serves the different views available in the module.
 *
 * @package simplesamlphp/simplesamlphp
 */
class ExpiryCheck
{
    /** @var \SimpleSAML\Configuration */
    protected Configuration $config;

    /** @var \SimpleSAML\Session */
    protected Session $session;

    /**
     * @var \SimpleSAML\Auth\State|string
     * @psalm-var \SimpleSAML\Auth\State|class-string
     */
    protected $authState = Auth\State::class;


    /**
     * Controller constructor.
     *
     * It initializes the global configuration and session for the controllers implemented here.
     *
     * @param \SimpleSAML\Configuration $config The configuration to use by the controllers.
     * @param \SimpleSAML\Session $session The session to use by the controllers.
     *
     * @throws \Exception
     */
    public function __construct(
        Configuration $config,
        Session $session
    ) {
        $this->config = $config;
        $this->session = $session;
    }


    /**
     * Inject the \SimpleSAML\Auth\State dependency.
     *
     * @param \SimpleSAML\Auth\State $authState
     */
    public function setAuthState(Auth\State $authState): void
    {
        $this->authState = $authState;
    }


    /**
     * About to expire.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request The current request.
     *
     * @return \SimpleSAML\XHTML\Template|\SimpleSAML\HTTP\RunnableResponse
     */
    public function about2expire(Request $request)
    {
        Logger::info('expirycheck - User has been warned that NetID is near to expirational date.');

        $stateId = $request->get('StateId');
        if ($stateId === null) {
            throw new Error\BadRequest('Missing required StateId query parameter.');
        }

        /** @psalm-var array $state */
        $state = $this->authState::loadState($stateId, 'expirywarning:about2expire');

        if ($request->get('yes') !== null) {
            // The user has pressed the yes-button
            return new RunnableResponse([Auth\ProcessingChain::class, 'resumeProcessing'], [$state]);
        }

        $daysleft = $state['daysleft'];

        $t = new Template($this->config, 'expirycheck:about2expire.twig');
        $t->data['autofocus'] = 'yesbutton';
        $t->data['yesTarget'] = Module::getModuleURL('expirycheck/about2expire.php');
        $t->data['yesData'] = ['StateId' => $stateId];
        $t->data['expireOnDate'] = $state['expireOnDate'];
        $t->data['netId'] = $state['netId'];

        if ($daysleft === 0) {
            # netid will expire today
            $t->data['header'] = $t->t(
                '{expirycheck:expwarning:warning_header_today}',
                [
                    '%NETID%' => htmlspecialchars($t->data['netId'])
                ]
            );
            $t->data['warning'] = $t->t(
                '{expirycheck:expwarning:warning_today}',
                [
                    '%NETID%' => htmlspecialchars($t->data['netId'])
                ]
            );
        } elseif ($daysleft === 1) {
            # netid will expire in one day
            $t->data['header'] = $t->t(
                '{expirycheck:expwarning:warning_header}',
                [
                    '%NETID%' => htmlspecialchars($t->data['netId']),
                    '%DAYS%' => $t->t('{expirycheck:expwarning:day}'),
                    '%DAYSLEFT%' => htmlspecialchars($daysleft),
                ]
            );
            $t->data['warning'] = $t->t(
                '{expirycheck:expwarning:warning}',
                [
                    '%NETID%' => htmlspecialchars($t->data['netId']),
                    '%DAYS%' => $t->t('{expirycheck:expwarning:day}'),
                    '%DAYSLEFT%' => htmlspecialchars($daysleft),
                ]
            );
        } else {
            # netid will expire in next <daysleft> days
            $t->data['header'] = $t->t(
                '{expirycheck:expwarning:warning_header}',
                [
                    '%NETID%' => htmlspecialchars($t->data['netId']),
                    '%DAYS%' => $t->t('{expirycheck:expwarning:days}'),
                    '%DAYSLEFT%' => htmlspecialchars($daysleft),
                ]
            );
            $t->data['warning'] = $t->t(
                '{expirycheck:expwarning:warning}',
                [
                    '%NETID%' => htmlspecialchars($t->data['netId']),
                    '%DAYS%' => $t->t('{expirycheck:expwarning:days}'),
                    '%DAYSLEFT%' => htmlspecialchars($daysleft),
                ]
            );
        }

        return $t;
    }


    /**
     * Expired.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request The current request.
     *
     * @return \SimpleSAML\XHTML\Template
     */
    public function expired(Request $request): Template
    {
        Logger::info('expirycheck - User has been warned that NetID is near to expirational date.');

        $stateId = $request->get('StateId');
        if ($stateId === null) {
            throw new \SimpleSAML\Error\BadRequest('Missing required StateId query parameter.');
        }

        /** @psalm-var array $state */
        $state = $this->authState::loadState($stateId, 'expirywarning:expired');

        $t = new Template($this->config, 'expirycheck:expired.twig');
        $t->data['expireOnDate'] = $state['expireOnDate'];
        $t->data['netId'] = $state['netId'];

        return $t;
    }
}

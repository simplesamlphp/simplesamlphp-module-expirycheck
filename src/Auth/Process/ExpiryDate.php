<?php

declare(strict_types=1);

namespace SimpleSAML\Module\expirycheck\Auth\Process;

use SimpleSAML\Assert\Assert;
use SimpleSAML\Auth;
use SimpleSAML\Error;
use SimpleSAML\Logger;
use SimpleSAML\Module;
use SimpleSAML\Utils;

use function array_key_exists;
use function date;
use function intval;
use function is_int;
use function is_string;
use function strtotime;
use function time;

/**
 * Filter which show "about to expire" warning or deny access if netid is expired.
 *
 * Based on preprodwarning module by rnd.feide.no
 *
 * <code>
 * // show about2xpire warning or deny access if netid is expired
 * 10 => [
 *     'class' => 'expirycheck:ExpiryDate',
 *     'netid_attr' => 'userPrincipalName',
 *     'expirydate_attr' => 'accountExpires',
 *     'convert_expirydate_to_unixtime' => true,
 *     'warndaysbefore' => 60,
 *     'date_format' => 'd.m.Y', # php date syntax
 * ],
 * </code>
 *
 * @package SimpleSAMLphp
 */
class ExpiryDate extends Auth\ProcessingFilter
{
    /** @var int */
    private int $warndaysbefore = 0;

    /**
     *  @var string
     * @psalm-suppress PropertyNotSetInConstructor
     */
    private string $netidAttr;

    /**
     * @var string
     * @psalm-suppress PropertyNotSetInConstructor
     * */
    private string $expirydateAttr;

    /** @var string */
    private string $dateFormat = 'd.m.Y';

    /** @var bool */
    private bool $convertExpirydateToUnixtime = false;


    /**
     * Initialize this filter.
     *
     * @param array &$config  Configuration information about this filter.
     * @param mixed $reserved  For future use.
     */
    public function __construct(array &$config, $reserved)
    {
        parent::__construct($config, $reserved);

        if (array_key_exists('warndaysbefore', $config)) {
            if (!is_int($config['warndaysbefore'])) {
                throw new Error\Exception('Invalid value for number of days given to expirycheck::ExpiryDate filter.');
            }

            $this->warndaysbefore = $config['warndaysbefore'];
        }

        if (array_key_exists('netid_attr', $config)) {
            if (!is_string($config['netid_attr'])) {
                throw new Error\Exception(
                    'Invalid attribute name given as eduPersonPrincipalName to expirycheck::ExpiryDate filter.'
                );
            }

            $this->netidAttr = $config['netid_attr'];
        }

        if (array_key_exists('expirydate_attr', $config)) {
            if (!is_string($config['expirydate_attr'])) {
                throw new Error\Exception(
                    'Invalid attribute name given as schacExpiryDate to expirycheck::ExpiryDate filter.'
                );
            }

            $this->expirydateAttr = $config['expirydate_attr'];
        }

        if (array_key_exists('date_format', $config)) {
            if (!is_string($config['date_format'])) {
                throw new Error\Exception('Invalid date format given to expirycheck::ExpiryDate filter.');
            }

            $this->dateFormat = $config['date_format'];
        }

        if (array_key_exists('convert_expirydate_to_unixtime', $config)) {
            if (!is_bool($config['convert_expirydate_to_unixtime'])) {
                throw new Error\Exception(
                    'Invalid value for convert_expirydate_to_unixtime given to expirycheck::ExpiryDate filter.'
                );
            }

            $this->convertExpirydateToUnixtime = $config['convert_expirydate_to_unixtime'];
        }
    }


    /**
     * Show expirational warning if remaining days is equal or under defined $warndaysbefore
     *
     * @param array &$state
     * @param int $expireOnDate
     * @param int $warndaysbefore
     * @return bool
     */
    public function shWarning(array &$state, int $expireOnDate, int $warndaysbefore): bool
    {
        $now = time();
        if ($expireOnDate >= $now) {
            $days = intval(($expireOnDate - $now) / 86400); //24*60*60=86400
            if ($days <= $warndaysbefore) {
                $state['daysleft'] = $days;
                return true;
            }
        }
        return false;
    }


    /**
     * Check if given date is older than today
     *
     * @param int $expireOnDate
     * @return bool
     */
    public function checkDate(int $expireOnDate): bool
    {
        $now = time();
        return $now <= $expireOnDate;
    }


    /**
     * Apply filter
     *
     * @param array &$state  The current state.
     */
    public function process(array &$state): void
    {
        Assert::keyExists($state, 'Attributes');
        Assert::keyExists($state['Attributes'], $this->netidAttr);

        /*
         * UTC format: 20090527080352Z
         */
        $netId = $state['Attributes'][$this->netidAttr][0];
        // expirydateAttr optional
        $expireOnDate = "0";
        if (array_key_exists($this->expirydateAttr, $state['Attributes'])) {
            $expireOnDate = $state['Attributes'][$this->expirydateAttr][0];
        }

        if (intval($expireOnDate) === 0) {
            // Never expires
            return;
        } elseif ($this->convertExpirydateToUnixtime === true) {
            $expireOnDate = $this->convertFiletimeToUnixtime($expireOnDate);
        } else {
            $expireOnDate = strtotime($expireOnDate);
        }

        $httpUtils = new Utils\HTTP();
        if ($this->shWarning($state, $expireOnDate, $this->warndaysbefore)) {
            if (isset($state['isPassive']) && $state['isPassive'] === true) {
                // We have a passive request. Skip the warning.
                return;
            }
            Logger::warning('expirycheck: NetID ' . $netId . ' is about to expire!');

            // Save state and redirect
            $state['expireOnDate'] = date($this->dateFormat, $expireOnDate);
            $state['netId'] = $netId;
            $id = Auth\State::saveState($state, 'expirywarning:about2expire');
            $url = Module::getModuleURL('expirycheck/about2expire');
            $httpUtils->redirectTrustedURL($url, ['StateId' => $id]);
        }

        if (!$this->checkDate($expireOnDate)) {
            Logger::error('expirycheck: NetID ' . $netId .
                ' has expired [' . date($this->dateFormat, $expireOnDate) . ']. Access denied!');

            /* Save state and redirect. */
            $state['expireOnDate'] = date($this->dateFormat, $expireOnDate);
            $state['netId'] = $netId;
            $id = Auth\State::saveState($state, 'expirywarning:expired');
            $url = Module::getModuleURL('expirycheck/expired');
            $httpUtils->redirectTrustedURL($url, ['StateId' => $id]);
        }
    }


    /**
     * @param string $fileTime Time as represented by MS Active Directory
     * @return int Unix-time
     */
    private function convertFiletimeToUnixtime(string $fileTime): int
    {
        $winSecs = intval(intval($fileTime) / 10000000); // divide by 10 000 000 to get seconds
        return $winSecs - 11644473600; // 1.1.1600 -> 1.1.1970 difference in seconds
    }
}

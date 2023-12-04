<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\expirycheck\Auth\Process;

use PHPUnit\Framework\TestCase;
use SimpleSAML\Assert\AssertionFailedException;
use SimpleSAML\Error;
use SimpleSAML\Module\expirycheck\Auth\Process\ExpiryDate;

class ExpiryDateTest extends TestCase
{
    /** @var array valid configuration */
    private static $config = [
        'class' => 'expirycheck:ExpiryDate',
        'netid_attr' => 'userPrincipalName',
        'expirydate_attr' => 'accountExpires',
        'convert_expirydate_to_unixtime' => false,
        'warndaysbefore' => 60,
        'date_format' => 'd.m.Y', # php date syntax
    ];

    /** @var array minimal request */
    private static $minRequest = [
        'Source' => [
            'entityid' => 'https://localhost/sp',
        ],
        'Destination' => [
            'entityid' => 'https://localhost/idp',
        ],
        'Attributes' => [],
    ];

    /**
     */
    public function testInvalidWarndaysbefore(): void
    {
        $config = ['warndaysbefore' => 'X'];
        $this->expectException(Error\Exception::class);
        $this->expectExceptionMessage("Invalid value for number of days given to expirycheck::ExpiryDate filter.");
        $filter = new ExpiryDate($config, null);
        self::fail();
        $filter->checkDate(1);
    }

    /**
     */
    public function testInvalidNetidAttr(): void
    {
        $config = ['netid_attr' => 1];
        $this->expectException(Error\Exception::class);
        $msg = "Invalid attribute name given as eduPersonPrincipalName to expirycheck::ExpiryDate filter.";
        $this->expectExceptionMessage($msg);
        $filter = new ExpiryDate($config, null);
        self::fail();
        $filter->checkDate(1);
    }

    /**
     */
    public function testInvalidExpirydateAttr(): void
    {
        $config = ['expirydate_attr' => 1];
        $this->expectException(Error\Exception::class);
        $msg = "Invalid attribute name given as schacExpiryDate to expirycheck::ExpiryDate filter.";
        $this->expectExceptionMessage($msg);
        $filter = new ExpiryDate($config, null);
        self::fail();
        $filter->checkDate(1);
    }

    /**
     */
    public function testInvalidDateFormat(): void
    {
        $config = ['date_format' => 1];
        $this->expectException(Error\Exception::class);
        $msg = "Invalid date format given to expirycheck::ExpiryDate filter.";
        $this->expectExceptionMessage($msg);
        $filter = new ExpiryDate($config, null);
        self::fail();
        $filter->checkDate(1);
    }

    /**
     */
    public function testInvalidConvertToUnixtime(): void
    {
        $config = ['convert_expirydate_to_unixtime' => 1];
        $this->expectException(Error\Exception::class);
        $msg = "Invalid value for convert_expirydate_to_unixtime given to expirycheck::ExpiryDate filter.";
        $this->expectExceptionMessage($msg);
        $filter = new ExpiryDate($config, null);
        self::fail();
        $filter->checkDate(1);
    }

    /**
     */
    public function testValidConfiguration(): void
    {
        $filter = new ExpiryDate(self::$config, null);
        self::assertFalse($filter->checkDate(1));
    }

    /**
     */
    public function testWarningPast(): void
    {
        $filter = new ExpiryDate(self::$config, null);
        $state = [];
        $warning = $filter->shWarning($state, strtotime("10 September 2000"), 30);
        self::assertFalse($warning);
    }

    /**
     */
    public function testWarningNextWeek(): void
    {
        $filter = new ExpiryDate(self::$config, null);
        $state = [];
        $nextWeek = time() + (7 * 24 * 60 * 60);
        $warning = $filter->shWarning($state, $nextWeek, 30);
        self::assertTrue($warning);
    }

    /**
     */
    public function testWarningDistantFuture(): void
    {
        $filter = new ExpiryDate(self::$config, null);
        $state = [];
        $nextCentury = strtotime("2150/06/30");
        $warning = $filter->shWarning($state, $nextCentury, 30);
        self::assertFalse($warning);
    }

    /**
     */
    public function testProcessMissingAttributes(): void
    {
        $filter = new ExpiryDate(self::$config, null);
        $state = [];
        $this->expectException(AssertionFailedException::class);
        $msg = "Expected the key \"Attributes\" to exist.";
        $this->expectExceptionMessage($msg);
        $filter->process($state);
    }

    /**
     */
    public function testProcessMissingNetidAttr(): void
    {
        $filter = new ExpiryDate(self::$config, null);
        $initialState = ['Attributes' => []];
        $state = $initialState;
        $this->expectException(AssertionFailedException::class);
        $msg = "Expected the key \"userPrincipalName\" to exist.";
        $this->expectExceptionMessage($msg);
        $filter->process($state);
    }

    /**
     */
    public function testProcessMissingExpirydate(): void
    {
        $filter = new ExpiryDate(self::$config, null);
        $initialState = ['Attributes' =>
            [
                'userPrincipalName' => ['test'],
            ]
        ];
        $state = $initialState;
        $filter->process($state);
        self::assertEquals($initialState, $state);
    }

    /**
     */
    public function testProcessNoExpiry(): void
    {
        $skipReason = 'https://github.com/simplesamlphp/simplesamlphp-test-framework/issues/3#issuecomment-1836154792';
        $this->markTestSkipped($skipReason);
        $filter = new ExpiryDate(self::$config, null);
        $initialState = ["Attributes" =>
            [
                'userPrincipalName' => ['test'],
                'accountExpires' => ['20231228112510Z'],
            ]
        ];
        $request = array_merge(self::$minRequest, $initialState);
        $state = $initialState;
        $filter->process($request);
        self::assertEquals($initialState, $state);
    }
}

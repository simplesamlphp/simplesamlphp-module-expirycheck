expirycheck module
==================

The expirycheck module validates user's expiry date attribute by showing
warning to the user whose NetID is about to expire or denying access if NetID
has already expired.

The expirycheck module is implemented as an Authentication Processing Filter.
That means it can be configured in the global config.php file or the SP remote
or IdP hosted metadata.

It is recommended to run the expirycheck module at the IdP, and configure the
filter to run before all the other filters you may have enabled.

How to setup the expirycheck module
-----------------------------------

First you need to enable the expirycheck module in SimpleSAMLphp's `config/config.php`:

    module.enable => [
        'expirycheck' => true,
    ],

Then you need to set filter parameters in your config.php file.

Example:

	10 => [
		'class' => 'expirycheck:ExpiryDate',
		'netid_attr' => 'userPrincipalName',
		'expirydate_attr' => 'accountExpires',
		'convert_expirydate_to_unixtime' => true,
		'warndaysbefore' => 60,
		'date_format' => 'd.m.Y',
	],


Parameter netid_attr represents (ldap) attribute name which has user's NetID stored in it,
parameter expirydate_attr represents (ldap) attribute name which has user's expiry date
(date must be formatted as YYYYMMDDHHMMSSZ, e.g. 20111011235959Z) stored in it.
Those two attributes needs to be part of the attribute set, which is retrieved from ldap during
authentication process.

Note:
Microsoft Active Directory has it's own format to store dates. This filter can automatically convert this
by setting 'convert_expirydate_to_unixtime' to TRUE.

Parameter warndaysbefore set as a number, which represents how many days before expiry
date the "about to expire" warning will show to the user.
Parameter date_format defines date representation format. PHP Date() syntax
is used. More info: http://php.net/manual/en/function.date.php

P.S.

Comments and bug reports please send to Alex Mihičinac <alexm@arnes.si>

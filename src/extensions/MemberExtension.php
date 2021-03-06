<?php

namespace Firesphere\HaveIBeenPwned\Extensions;

use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Security\Member;

/**
 * Class \Firesphere\HaveIBeenPwned\Extensions\MemberExtension
 *
 * @property Member|MemberExtension $owner
 * @property int $PasswordIsPwnd
 * @property string $BreachedSites
 */
class MemberExtension extends DataExtension
{
    /**
     * Name of the tab that is used for HaveIBeenPwned
     */
    const PWND_TAB = 'Root.HaveIBeenPwned';

    /**
     * @var array
     */
    private static $db = [
        'PasswordIsPwnd' => 'Int',
        'BreachedSites'  => 'Text'
    ];

    protected $fallbackHelp = 'If the error says that you "have been Pwnd", it means your password appears in the ' .
    '<a href="https://haveibeenpwned.com/Privacy">Have I Been Pwnd</a> database. ' .
    'Therefore, we can not accept your password, because it is insecure or known to have been breached. ' .
    'Before a password is safely stored in our database, we test if the password has been breached. ' .
    'We do not share your password. ' .
    'We run a safe test against the HaveIBeenPwned database to. ' .
    'None of your data is shared or stored at HaveIBeenPwned. ' .
    'For more information, you can read up on "Password safety", ' .
    'and we strongly recommend installing a password manager if you haven\'t already. ' .
    'Several options are LastPass, BitWarden and 1Password. ' .
    'These services are also able to test your passwords against the HaveIBeenPwned database, ' .
    'to see if your passwords are secure and safe.<br />' .
    'Furthermore, <a href="https://www.troyhunt.com/introducing-306-million-freely-downloadable-pwned-passwords/">' .
    'Troy Hunt explains why and how this service is important</a>.';

    /**
     * @param FieldList $fields
     */
    public function updateCMSFields(FieldList $fields)
    {
        // PwndDisabled always needs to be false
        $this->owner->PwndDisabled = false;

        $fields->removeByName(['BreachedSites', 'PasswordIsPwnd']);
        $this->breachFound($fields);

        $this->breachedSites($fields);

        $fields->addFieldsToTab('Root.Main', [
            ReadonlyField::create(
                'PasswordIsPwnd',
                _t(self::class . '.PWNCOUNT', 'Pwnd Count')
            )->setDescription(_t(
                self::class . '.AMOUNT',
                'Amount of times the password appears in the Have I Been Pwnd database'
            )),
            CheckboxField::create(
                'PwndDisabled',
                _t(self::class . '.TMPDISABLE', 'Disable "Have I Been Pwnd" temporarily')
            )->setDescription(_t(
                self::class . '.TMPDISABLEDESCR',
                'Allow the password to be a compromised password once (only from the CMS), ' .
                'to reset a users password manually and let the user reset the password on first login.'
            ))
        ]);
    }

    /**
     * @param FieldList $fields
     */
    protected function breachFound(FieldList $fields)
    {
        if ($this->owner->BreachedSites || $this->owner->PasswordIsPwnd) {
            $fields->findOrMakeTab(
                static::PWND_TAB,
                _t(self::class . '.PWNDTAB', 'Have I Been Pwnd?')
            );
            $text = _t(
                self::class . '.PWNDHelp',
                $this->fallbackHelp
            );

            $help = LiteralField::create('Helptext', '<p>' . $text . '</p>');
            $fields->addFieldToTab(static::PWND_TAB, $help);
        }
    }

    /**
     * @param FieldList $fields
     */
    protected function breachedSites(FieldList $fields)
    {
        if ($this->owner->BreachedSites) {
            $fields->addFieldToTab(
                static::PWND_TAB,
                ReadonlyField::create(
                    'BreachedSites',
                    _t(self::class . '.BREACHEDSITES', 'Known breaches')
                )->setDescription(_t(
                    self::class . '.BREACHEDDESCRIPTION',
                    'Sites on which your email address or username has been found in known breaches.'
                ))
            );
        }
    }
}

<?php

namespace App\Utility\AlphagovNotify;

class Reference
{
    public const ACCOUNT_CREATION_DIARY_KEEPER_EMAIL_TEMPLATE_ID = '57c965e5-1f6f-402d-8b32-f779f0470bf9';
    public const ACCOUNT_CREATION_INTERVIEWER_EMAIL_TEMPLATE_ID = 'd71851aa-df69-4d65-b24f-5092c18a24c4';
    public const ACCOUNT_CREATION_EVENT = 'account-creation';
    public const ACCOUNT_CREATION_LINK_EXPIRY = 2*7*24*60*60; // 2 weeks

    public const FORGOTTEN_PASSWORD_TEMPLATE_ID = 'a7400098-f7e8-49a1-bf7e-5814102db784';
    public const FORGOTTEN_PASSWORD_EVENT = 'forgotten-password';
    public const FORGOTTEN_PASSWORD_LINK_EXPIRY = 1*24*60*60;
}

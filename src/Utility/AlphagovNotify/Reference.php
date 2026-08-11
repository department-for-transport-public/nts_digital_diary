<?php

namespace App\Utility\AlphagovNotify;

class Reference
{
    public const string STATUS_ACCEPTED = 'accepted';
    public const string STATUS_FAILED = 'failed';
    public const string STATUS_WAITING_FOR_RETRY = 'waiting-for-retry';

    public const string ACCOUNT_CREATION_DIARY_KEEPER_EMAIL_TEMPLATE_ID = '581af36b-c36e-4f3f-a352-792c3e61d1e8';
    public const string ACCOUNT_CREATION_INTERVIEWER_EMAIL_TEMPLATE_ID = 'd71851aa-df69-4d65-b24f-5092c18a24c4';
    public const string ACCOUNT_CREATION_EVENT = 'account-creation';
    public const int ACCOUNT_CREATION_LINK_EXPIRY = 7*24*60*60; // Fallback expiry (interviewers / missing diary week). See AccountCreationHelper::calculateLinkExpiry(). There is a related rate limiter for resending expired links
    public const int ACCOUNT_CREATION_LINK_MIN_EXPIRY = 7*24*60*60; // Floor for diary-keeper links, so late sends are still usable
    public const int ACCOUNT_CREATION_LINK_GRACE = 3*24*60*60; // Buffer added after the diary week ends, for late completion

    public const string REMIND_DIARY_KEEPER_EMAIL_TEMPLATE_ID = '8affb687-29a1-459d-aece-d3779c03e46d';
    public const string REMIND_DIARY_KEEPER_EVENT = 'remind-diary-keeper';

    public const string FEEDBACK_CENTRE_MESSAGE_ARRIVED_TEMPLATE_ID = 'e8d914a9-689f-47b6-850b-cd7cd7088084';
    public const string FEEDBACK_CENTRE_MESSAGE_ARRIVED = 'feedback-message-arrived';

    public const string FEEDBACK_CENTRE_MESSAGE_ASSIGNED_TEMPLATE_ID = '5098c660-745a-449e-9a83-af81b82ed737';
    public const string FEEDBACK_CENTRE_MESSAGE_ASSIGNED = 'feedback-message-assigned';

    public const string FORGOTTEN_PASSWORD_TEMPLATE_ID = 'a7400098-f7e8-49a1-bf7e-5814102db784';
    public const string FORGOTTEN_PASSWORD_EVENT = 'forgotten-password';
    public const int FORGOTTEN_PASSWORD_LINK_EXPIRY = 24*60*60;
}

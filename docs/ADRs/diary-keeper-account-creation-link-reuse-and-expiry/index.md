# Decision record for Account Creation Link Reuse and Expiry

Title: Making the account creation link robust to repeated use across the diary week

## Context and Problem Statement

The account activation link is, in practice, a Diary Keeper's de-facto "front door" to the
service for the whole diary week. The same signed link is delivered both in the initial account
creation email and in the week-start reminder email (`RemindersHelper`), and both route through
`SetupController`. Diary Keepers commonly return to whichever of these emails they have to hand,
repeatedly, rather than bookmarking the site or using the login page. (Commit `b0367de1` did not
remove the reminder feature; it removed the 2026 feature toggle, so the reminder is now always
on.)

Three problems surface when a Diary Keeper uses that link repeatedly (e.g. several times a day
throughout their diary week):

1. **The reuse limit is reached mid-week.** The signed URL is rate-limited by
   `account_activation_link_reuse` (`config/packages/rate_limiter.yaml`), consumed once per
   *fresh session* in `UrlSigner::isValid()` and gated by the session-criteria cache in
   `AbstractController::fetchUserAndCacheCriteria()`. Because the PHP session idle timeout is
   one hour (`framework.yaml` `gc_maxlifetime`), "several times a day" means several fresh
   sessions, each spending a token. Once exhausted, the user is bounced to an "Account setup
   link expired" page and (confusingly) resent "create your account" emails, with a risk of a
   temporary lockout while the 1-per-3-days resend limiter (`account_creation_link_expired`)
   is in effect.

   Note: the prior ADR and an inline comment stated the link allowed "five uses". The
   implementation actually allows **ten** fresh-session uses (one token each); the comment was
   stale.

2. **Expiry is measured from send time, not the diary week.** `ACCOUNT_CREATION_LINK_EXPIRY`
   was a fixed 7 days counted from when the email was sent. Because Diary Keeper emails are
   sent *before* the diary week begins, a fixed window can lapse partway through (or before)
   the week the user actually needs it.

3. **Activated users are mis-messaged.** On expiry/overuse the flow resent an account-creation
   email and showed "a new link has been sent" even to users who already had a password — a
   misleading message for someone who has long since created their account.

This record builds on `docs/ADRs/diary-keeper-account-activation-and-login-behaviours`. Its
decisions still hold — automatic login after setting a password, the Remember Me badge, and the
reminder email carrying an activation link (now always on, via `RemindersHelper`). This record
revises only the stale "five uses" figure (the link actually allowed ten uses) and refines the
reuse, expiry and messaging behaviours below.

## Decision

The following applies to the account creation link flow (`SetupController`):

1. **Do not consume a reuse token when the visitor is already authenticated as the link's
   user.** If `security.getUser().getId() === {userId}`, the reuse limiter is skipped (the
   signature is still validated). A returning Diary Keeper with a live Remember Me cookie
   therefore re-uses their link freely.
2. **Anchor the Diary Keeper link expiry to the end of the diary week.** Validity is computed
   as `diaryWeekStartDate + 7 days + grace`, with a floor so links sent late (or without a
   week date) remain usable. Interviewers and any user without a diary week keep the fixed
   fallback. Grace is currently **3 days**; the floor is **7 days**. The week-start reminder
   link (`RemindersHelper`) uses the same anchored expiry, so it too stays valid for the whole
   diary week rather than its previous fixed 3-day window (which expired ~2 days into the week).
3. **Do not resend a "create your account" email to activated users.** When an expired/overused
   link belongs to a user who already has a password, no email is sent; the page steers them to
   log in instead.
4. **Correct the user-facing and developer-facing copy.** The expired page no longer claims a
   link was sent when none was; the rate-limited wording matches the real 3-day window; and the
   reuse-limiter comment is corrected from "5 uses" to "10 uses".

## Rationale

- **Reuse exemption is the tightest safe relaxation.** The reuse limiter exists to cap the
  blast radius of a *leaked or forwarded* link. An attacker is not authenticated as the link's
  owner, so exempting the authenticated owner preserves that protection entirely while removing
  the runaway-consumption problem for the dominant, legitimate case. Because the Remember Me
  lifetime (1 week) matches the diary week, a same-device Diary Keeper is re-authenticated on
  each visit and spends no tokens — the one-hour session timeout becomes irrelevant.
- **Anchoring beats a longer fixed window.** Simply doubling the constant would still be a bet
  that the pre-week lead time is bounded. Anchoring to the week's end guarantees coverage for
  the whole week regardless of how early the email was sent, while the floor protects late
  sends and edge cases (e.g. email reset mid-week, missing week date).
- **No benefit to resending to activated users.** They already have a password and can log in;
  a fresh "create your account" email is confusing and can read as a fault or phishing. With
  the reuse exemption, logged-in users no longer trip overuse at all, so the remaining path is
  primarily the genuinely-expired / different-device case, which is best served by a login steer.
- **Honest messaging.** The expired page previously asserted a link was sent even when the
  resend was rate-limited or deliberately skipped.

## Implementation

- `src/Controller/Auth/SetupController.php`
  - `setup()` computes `$alreadyThisUser` and passes `null` instead of the reuse limiter to
    `fetchUserAndCacheCriteria()` when true.
  - `creationLinkWasExpired()` gates the resend (and its `account_creation_link_expired`
    limiter consumption) on `$user->getPassword() === null`.
- `src/Utility/Security/AccountCreationHelper.php`
  - New `calculateLinkExpiry(UserPersonInterface, ?int $currentTime)` returns
    `max(weekEnd + grace - now, ACCOUNT_CREATION_LINK_MIN_EXPIRY)` for a Diary Keeper with a
    diary-week start, else `ACCOUNT_CREATION_LINK_EXPIRY`. `sendAccountCreationEmail()` passes
    it as `linkExpiryOverride`; `generateAccountCreationUrl()`'s default is unchanged.
- `src/Utility/TravelDiary/RemindersHelper.php`
  - The week-start reminder link now passes `calculateLinkExpiry($diaryKeeper)` as its
    `linkExpiryOverride` instead of the fixed 3-day value.
- `src/Utility/AlphagovNotify/Reference.php`
  - `ACCOUNT_CREATION_LINK_EXPIRY` = 7 days (fallback). New `ACCOUNT_CREATION_LINK_MIN_EXPIRY`
    = 7 days (floor) and `ACCOUNT_CREATION_LINK_GRACE` = 3 days. The now-unused
    `REMIND_DIARY_KEEPER_LINK_EXPIRY` constant was removed.
- `templates/auth/account_setup_link_expired.html.twig` and
  `translations/auth+intl-icu.en_gb.yaml` — separate copy for the activated (login steer) and
  un-activated (resend) cases; reworded the rate-limited wording.
- `config/packages/rate_limiter.yaml` — corrected the `account_activation_link_reuse` comment.
- Tests: `tests/Utility/Security/AccountCreationHelperTest.php` (expiry maths),
  updated `tests/Functional/Auth/SetupControllerBehaviourTest.php` (login steer + reuse
  exemption) and `tests/Functional/DiaryKeeper/AccountActivationTest.php`.

## Expected outcomes

1. A Diary Keeper can return to their account creation link repeatedly throughout the diary
   week without hitting "link expired" pages or receiving repeat onboarding emails.
2. The link remains valid for the whole diary week irrespective of how far ahead the email was
   sent.
3. The reuse limiter continues to cap abuse of a leaked/forwarded link for anyone not
   authenticated as the owner.
4. Activated users are guided to log in rather than being mis-told a new link was sent.

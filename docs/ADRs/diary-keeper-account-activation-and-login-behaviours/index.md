# Decision record for Diary Keeper Account Activation and Login Behaviours

Title: Discussion of Diary Keeper Account Activation and Login Behaviours

## Context and Problem Statement

We decided to look in to account activation and diary keeper login behaviours as they are impacted by the introduction of email reminders for Diary Keepers before the start of their travel diary week. 

## Decision

The following (except for automated login after setting a password) applies exclusively to Diary Keepers.

1. The reminder email will contain an account activation link 
2. Clicking on an account activation link will automatically log in after successfully setting a password. If a password is already set, following the link will just log in the user (via a pre-auth screen)
3. The automatic login will also have the `Remeber Me` badge activated
4. Account activation links will become invalid after five uses

## Rationale

The decision was based on the following factors:

1. The introduction of the reminder email: It is necessary to handle the fact that a Diary Keeper might not yet have activated their account by the time the reminder email is received.
2. It seems like an unnecessary extra step to make users log in using the password they have just created.
3. Since the password reset flow uses a secure link via email for identity verification, it is reasonable to accept that another form of a secure link (the account activation link) via email is also enough to verify identity.
4. Diary Keepers may wish to log in on more than one device, so link re-use would make sense within sensible limits.
5. Having a password as well as being able to log in via a secure link allows for more flexibility.

## Implementation Plan

- The changes will be implemented at the same time as the addition of a reminder email flow.
- Feedback will be monitored for comments about the account creation and login processes.

## Expected outcomes

The following outcomes are expected:

1. Account creation will be simplified
2. Login will be easier

## Conclusion

We believe that the changes outlined will improve the user experience of account creation and logging in. 
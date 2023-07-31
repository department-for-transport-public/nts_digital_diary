# Interviewer Training

Training takes the form of several repeatable modules, each identified by a `moudle name`.

When an interviewer accesses the training area, they will be presented with a full set of the available modules (if no
modules exist for the interviewer, they will be created at this point - including any `AreaPeriod` instances that may
be required).

## Module workflow auto-transitions

The training modules will now automatically transition from `not started`, through `in progress` to `complete`.
- Video modules: playing a video will mark the module as `in progress`, playing to the end of the video will mark it as `complete`
- Interviewer's personal travel diary: marked as `in progress` after adding the first journey, `complete` when the diary is marked as complete (diary keeper view)
- Onboarding practice: marked as `in progress` upon logging in, `complete` once the household has been submitted
- Travel diary correction: marked as `in progress` upon impersonating any user in the household, `complete` when the household is submitted for export

## Diary Keeper username uniqueness

In order to allow interviewers to complete onboarding training without each having to provide a unique diary keeper
email address, the `user` table specified that the username must be unique within the context of the
`training_interviewer_id` field. 

Additionally, since MySql/MariaDB ignore indexes that contain a null (in the case where a user is not part of training),
there is a virtual column in the table which replaces a null for `interviewer_id` with the string `no-interviewer`.  
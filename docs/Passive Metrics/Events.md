# Passive metric events

Passive metric events have the following structure:
- `id` - ID of the event
- `created_at` - the date/time at which the event was triggered/created
- `user_serial` - the serial of the user who was logged in at the time, prefixed with `dk`, `int`, `ob` or `adm` to indicate the user type (diary keeper, interviewer, onboarding or admin). Can be used to indicate if impersonation was in play.
- `diary_serial` - the serial of the related diary (if applicable)
- `event` - the name of the event
- `metadata` - additional metadata

All events will contain the following metadata keys as standard:
- `browser_viewport` - an array containing `size` (`mobile`, `tablet` or `desktop`) and `aspect` (`landscape`, `portrait` or `square`). For example...
  ```json
  {"size": "desktop", "aspect": "landscape"}
  ```

**Note**: none of the events are recorded in the course of interviewer training

## Diary state event

- `Diary: state change` - additional metadata:
  - `from` - the previous state
  - `to` - the new state

See [example 1](#example-1---diary-state-change)


## Entity creation events

- `Journey: create` - additional metadata:
  - `day` - the diary day number (i.e. 1-7)
- `Stage: create` - additional metadata:
  - `journeyId` - the journey ID
  - `number` - the stage number within the journey

All entity creation events will also have the following metadata:
- `id` - the ID of the new Journey or Stage entity
- `sourceWizard` - the wizard that was used to create the entity

`Journey: split`, `Journey: repeat` and `Journey: return` events have the same metadata as `Journey: create`.


See [example 2](#example-2---journey-create)


## Entity edit events

- `Journey: edit`
- `Stage: edit`

All entity edit events will have the following metadata:
- `id` - the ID of the Journey or Stage entity
- `changed` - a list of the entity properties that were changed. For example:
  ```json
  ["travelTime","adultCount","childCount"]
  ```

See [example 3](#example-3---stage-edit)


## Journey share event

- `Journey: share` - additional metadata:
  - `id` the ID of the source journey
  - `shared_to` - an array of IDs of the newly created Journeys

See [example 4](#example-4---journey-share)

## Entity delete events

- `Journey: delete`
- `Stage: delete`

All entity delete events will have the following metadata:
- `id` - the ID of the entity


## Login success event 

- `Login: success` - additional metadata:
  - `firewall` - the login firewall (either "main", or "onboarding" depending upon the login page used)

See [example 5](#example-5---login-success)

## Onboarding complete event

- `Onboarding: complete` - no additional metadata

## Interviewer actions

- `Interviewer: household comparison` - Interviewer has viewed the household comparison screen.
See [example 6](#example-6---interviewer-household-compare). Extra metadata...
  - `day` - the day number being viewed
- `Interviewer: impersonate` - the interviewer has started impersonation of a diary keeper
- `Interviewer: change diary-keeper email` - the interviewer has changed a diary keeper's
email address after onboarding. See [example 7](#example-7---interviewer-change-diary-keeper-email)


## Admin add diary keeper

When an admin adds a diary keeper to a household after onboarding is complete.

- `Admin: add diary keeper` - no additional metadata


# Appendix A - examples

## Example 1 - `Diary: state change`
```json
{
  "id": "01HHM6K9QSWE49HHQGWHVAZNF1",
  "created_at": "2023-12-14 13:00:03",
  "user_serial": "dk:308801/08/1/1",
  "diary_serial":  "308801/08/1/1",
  "event": "Diary: state change",
  "metadata": {
    "from": "completed",
    "to": "in-progress",
    "browser_viewport": {"size": "desktop", "aspect": "landscape"}
  }
}
```

## Example 2 - `Journey: create`
```json
{
  "id": "01HHC91MYEFXEC37PW9EQYQ55B",
  "created_at": "2023-12-11 11:08:55",
  "user_serial": "int:1001",
  "diary_serial": "000000/01/1/1",
  "event": "Journey: create",
  "metadata": {
    "id": "01HHC91MYBH6C16XZTCHXPA6XV",
    "day": 4,
    "source_wizard": "ReturnJourneyWizard",
    "browser_viewport": {"size": "desktop", "aspect": "landscape"}
  }
}
```

## Example 3 - `Stage: edit`
```json
{
  "id": "01HHCAY9YZ85XA06FG8XRCVDS2",
  "created_at": "2023-12-11 11:42:03",
  "user_serial": "int:1001",
  "diary_serial": "000000/01/1/1",
  "event": "Journey: edit",
  "metadata": {
    "id": "01HHC91MYBH6C16XZTCHXPA6XV",
    "changed": ["startTime","startLocation"],
    "browser_viewport": {"size": "desktop", "aspect": "landscape"}
  }
}
```

## Example 4 - `Journey: share`

```json
{
  "id": "01HJ1A9712S9XW52A2SW4QD3A5",
  "created_at": "2023-12-19 15:14:35",
  "user_serial": "dk:111111/01/2/1",
  "diary_serial": "111111/01/2/1",
  "event": "Journey: share",
  "metadata": {"shared_to":["01HJ1A970BKR8YD91QSBG855N2"],"id":"01HJ1A8GY7DTA972F87QMNCZ9Z","browser_viewport":{"size":"desktop","aspect":"landscape"}}
},
```

## Example 5 - `Login: success`

```json
{
  "id": "01HHKZXWFBSC9419EJSD6YVP54",
  "created_at": "2023-12-14 11:03:30",
  "user_serial": "int:1001",
  "diary_serial": null,
  "event": "Login: success",
  "metadata": {
    "firewall": "main"
    "browser_viewport": {"size": "desktop", "aspect": "landscape"}
  }
}
```

## Example 6 - `Interviewer: household compare`
```json
{
  "id": "01HHM2FH3TZEND2DJYNVEV7HD9",
  "created_at": "2023-12-14 11:48:05",
  "user_serial": "int:1001",
  "diary_serial": "000200/01/1",
  "event": "Household comparison",
  "metadata": {
    "day":4,
    "browser_viewport": {"size": "desktop", "aspect": "landscape"}
  }
}
```

## Example 7 - `Interviewer: change diary-keeper email`
```json
{
  "id": "01HHMESX2WDJ5SHAR4HA62C4QZ",
  "created_at": "2023-12-14 15:23:28",
  "user_serial": "int:1001",
  "diary_serial": "308801/01/1/2",
  "event": "Interviewer: change diary-keeper email",
  "metadata": {
    "browser_viewport": {"size": "desktop", "aspect": "landscape"}
  }
}
```

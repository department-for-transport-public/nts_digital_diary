[Home](../../README.md) > [API](../API.md) > Households

# Households summary

## End points

### `GET /households`

Retrieve collection of `Households`, showing an overall summary of household statuses

#### Response

Successful response will be an array of [HouseholdSummary](#HouseholdSummary) objects

```json
[
  ...
]
```

## Models

### HouseholdSummary

- `area` string
- `address`: integer
- `household`: integer
- `travelWeekStartDate` string (formatted date)
- `householdState` string (one of: "new", "in_progress", "completed", "approved")
- `diaryCount` integer
- `diaryStateCounts` dictionary/keyed array comprising aggregate counts for diary states:
  - `new` int
  - `in-progress` int
  - `completed` int
  - `approved` int
  - `discarded` int

#### Example
This represents the household with serial 308701/04/01, which is currently in progress.

The household has 6 diaries, of which 1 is new, 2 are in progress, 2 have been completed and 1 has been completed and then marked as approved by the interviewer.

```json
{
  "area": "308701",
  "address": 4,
  "household": 1,
  "startDate": "2023-09-15",
  "householdState": "in_progress",
  "diaryCount": 6,
  "diaryStateCounts": {
    "new": 1,
    "in-progress": 2,
    "completed": 2,
    "approved": 1,
    "discarded": 0
  }
}
```

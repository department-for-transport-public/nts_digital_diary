[Home](../../README.md) > [API](../API.md) > Survey Export

# Survey Export

## End points

### `GET /survey-data` 

This endpoint will return survey data for all households that have been submitted for export at or after startTime
and before endTime.

#### Parameters

It is recommended to use `startTime` and `endTime` for exporting survey data. This will return surveys that have been
submitted at or after (`>=`) the `startTime` and before (`<`) the `endTime`. As such each request should use a `startTime` equal to
the `endTime` from the previous request to ensure no gaps occur in the request timestamp history

- `startTime` (unix timestamp, required, >=)
- `endTime` (unix timestamp, required, <. Limited to no more than 7 days/604,800 seconds after `startTime`)

or

- `householdSerials` (comma separated list of household serials, e.g. `220617/2/1`, required. A maximum of 10 households can be requested in each call)

#### Response

Successful response will be an array of [household](#household) objects
```json
[
  ...
]
```

## Models

- [Household](#household)
- [Vehicle](#vehicle)
- [Diary Keeper](#diary-keeper)
- [Day](#day)
- [Journey](#journey)
- [Stage](#stage)
- [Property change history](#property-change-history)

### Household

- `area` string
- `address` int
- `household` int
- `travelWeekStartDate` string (formatted date)
- `submittedBy` string (email address)
- `submittedAt` string (formatted date/time)
- `diaryKeepers` array

#### Example
```json
{
  "area": "211500",
  "address": 17,
  "household": 1,
  "travelWeekStartDate": "2022-11-23",
  "submittedBy": "john.smith@example.com",
  "submittedAt": "2022-12-15T14:06:15+00:00",
  "diaryKeepers": [...]
}
```

### Vehicle

- `capiNumber` int (CAPI number)
- `name` string
- `odometerUnit` string (enum: `miles`, `kilometres`) | null
- `weekStartOdometerReading` int | null
- `weekEndOdometerReading` int | null

#### Example
```json
{
  "capiNumber": 1,
  "name": "Red car",
  "odometerUnit": "miles",
  "weekStartOdometerReading": 31546,
  "weekEndOdometerReading": 31672
}
```


### Diary Keeper

- `person` int (CAPI number)
- `name` string
- `isAdult` boolean
- `mediaType` string (enum: `digital`, `paper`)
- `state` string (enum: `approved`, `discarded`)
- `hasUsedPracticeDay` boolean
- `approvalChecklistDaysVerirfiedBy` string (email address)
- `approvalChecklistDaysVerirfiedAt` string (formatted date/time)
- `days` array

#### Example
```json
{
  "person": 1, 
  "name": "Alice", 
  "isAdult": true,
  "mediaType": "digital",
  "state": "approved",
  "hasUsedPracticeDay": false,
  "approvalChecklistVerifiedBy": "bob@example.com",
  "approvalChecklistVerifiedAt": "2023-05-22T15:06:50+01:00",
  "days": [...]
```

### Day

- `dayNumber` int
- `date` string (formatted date)
- `diaryKeeperNotes` string | null
- `interviewerNotes` string | null
- `journeys` array

#### Example
```json
  "dayNumber": 1,
  "date": "2022-11-23",
  "diaryKeeperNotes": "Notes provided by diary keeper",
  "interviewerNotes": null,
  "journeys": [...]
```

### Journey

- `id` string (ULID)
- `startTime` string (formatted time)
- `startLocation` string (null when `startIsHome` is true)
- `startIsHome` boolean
- `endTime` string (formatted time)
- `endLocation` string (null when `endIsHome` is true)
- `endIsHome` boolean
- `purpose` string
- `purposeCode` int | null
- `stages` array
- `sharedFromId` string (ULID, the id of the originating journey, indicating this is a journey that has been shared)
- `_history` object ([Property change history](#property-change-history))

#### Example
```json
{
  "startTime": "10:00",
  "startLocation": null,
  "startIsHome": true,
  "endTime": "10:45",
  "endLocation": "Town Centre, North Placeminster",
  "endIsHome": false,
  "purpose": "To go shopping for clothes",
  "purposeCode": null,
  "stages": [...],
  "_history": {...}
}
```

### Stage

**NB** - with cost values (parkingCost, ticketCost), a zero value indicates there was no cost (NIL on paper form), but
null indicates there was a cost but that the diary keeper didn't know how much.

- `#` int (sequential, stage number)
- `methodCode` int | null
- `methodOther` string | null (value provided when `methodCode` is null)
- `distance` string | null (decimal, 2 places)
- `distanceUnit` string (enum: `miles`, `metres`)
- `childCount` int
- `adultCount` int
- `travelTime` int | null (travel time in minutes)
- `boardingCount` int | null
- `ticketCost` string | null (decimal, £, 2 places)
- `ticketType` string | null
- `isDriver` boolean | null
- `parkingCost` string | null (decimal, £, 2 places)
- `vehicle` string | null
- `vehicleCapiNumber` int | null (CAPI number)
- `_history` object ([Property change history](#property-change-history))

#### Example
```json
{
  "#": 1,
  "methodCode": 1,
  "methodOther": null,
  "distance": "1.00",
  "distanceUnit": "miles",
  "childCount": 0,
  "adultCount": 1,
  "travelTime": 15,
  "boardingCount": null,
  "ticketCost": null,
  "ticketType": "Adult return",
  "isDriver": null,
  "parkingCost": null,
  "vehicle": null,
  "_history": {...}
},
```

### Property change history

History objects will contain a list of all relevant properties that have changed for the current `Journey` or `Stage`.
The value of each property is an array of the history of that property. The individual historical values take the following form:

- `timestamp` int
- `value` int | string | null
- `interviewerId` string | null (interviewer's NatCen serial, or null when edited by Diary Keeper)

**Note** The history for any given property may have been purged to some extent, as it is not necessary to retain
certain sequences of history... for example, if the Diary Keeper has made several consecutive edits, only the latest
will be kept. Likewise, only the latest of a consecutive set of interviewer edits will be kept. 

#### Example
In the following example (of a whole `_history` object), the value for `travelTime` was given a value of `10` by the
diary keeper, and then given a value of `5` by an interviewer (`INT1`). Each property of Journey/Stage will have a
history like this when it has been edited.
```json
{
  "travelTime": [
    {
      "value": "5",
      "interviewerId": "INT1",
      "timestamp": 1655459623
    },
    {
      "value": "10",
      "interviewerId": null,
      "timestamp": 1655418500
    }
  ]
}
```
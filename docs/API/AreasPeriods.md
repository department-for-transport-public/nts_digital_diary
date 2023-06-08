[Home](../../README.md) > [API](../API.md) > Areas

# Areas

## End points

### `GET /area_periods`

Retrieve collection of `AreaPeriods`

#### Response

Successful response will be an array of reduced (excluding `interviewers` and `onbaording_codes`) [AreaPeriod](#areaperiod) objects

```json
[
  ...
]
```

### `GET /area_periods/{year}/{area}`

Retrieve specific AreaPeriod object, identified by `{year}` and `{area}`

#### Response

Successful response will be an [AreaPaeriod](#areaperiod) object

### `POST /area_periods`

Create a new AreaPeriod

#### Request body

The request body should be a partial (only `area`, `year`, `month`), json encoded [AreaPeriod](#areaperiod) object

#### Response

Successful response will be an [AreaPaeriod](#areaperiod) object

### `DELETE /area_periods/{year}/{area}`

Delete specific [AreaPeriod](#areaperiod) object, identified by `{year}` and `{area}`


## Models

### AreaPeriod

- `id` string (ULID)
- `area` string
- `year` integer
- `month` integer
- `interviewers` array (string, serialId)
- `onboarding_codes` array

#### Example

```json
{
    "id": "01G4A3EJ295JAH4HRKKAE4KMY7",
    "area": "220601",
    "year": 2022,
    "month": 6,
    "interviewers": [
        "1234",
        "4321"
    ],
    "onboarding_codes": [
        [
            "45652205",
            "59885698"
        ],
      ...
    ]
}
```

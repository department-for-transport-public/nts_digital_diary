[Home](../../README.md) > [API](../API.md) > Areas

# Areas

## End points

### `GET /area_periods`

Retrieve collection of `AreaPeriods`

#### Response

Successful response will be an array of [AreaPeriod](#areaperiod) objects (`interviewers` and `onbaording_codes` will not be included in this output)

```json
[
  ...
]
```

### `GET /area_periods/{ULID}`

Retrieve specific AreaPeriod object, identified by `{ULID}`

#### Response

Successful response will be an [AreaPaeriod](#areaperiod) object (all fields will be returned)

### `POST /area_periods`

Create a new AreaPeriod

#### Request body

The request body should be a json encoded [AreaPeriod](#areaperiod) object (excluding `id`, `interviewers` and `onboarding_codes`)

### `DELETE /area_periods/{ULID}`

Delete specific [AreaPeriod](#areaperiod) object, identified by `{ULID}`


## Models

### AreaPeriod

- `id` string, optional (ULID)
- `area` string
- `year` integer
- `month` integer
- `interviewers` array, optional (string, ULID)
- `onboarding_codes` array, optional

#### Example

```json
{
    "id": "01G4A3EJ295JAH4HRKKAE4KMY7",
    "area": "220601",
    "year": 2022,
    "month": 6,
    "interviewers": [
        "01G4A3BM1XW3NMV79J0BE6XV4P",
        "01G4A3E96QJHSM109HD3CZ6GAW"
    ],
    "onboarding_codes": [
        [
            "45652205",
            "59885698"
        ],
        [
            "04641751",
            "22897262"
        ],
      ...
    ]
}
```

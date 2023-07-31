[Home](../../README.md) > [API](../API.md) > Interviewers

# Interviewers

## End points

### `GET /interviewers`

Retrieve collection of `Intervewers`

#### Response

Successful response will be an array of [Interviewer](#interviewer) objects (`area_periods` will not be included in this output)

```json
[
  ...
]
```

### `GET /interviewers/{serialId}`

Retrieve specific [Interviewer](#interviewer) object, identified by `{serialId}`

#### Response

Successful response will be an [Interviewer](#interviewer) object

### `POST /interviewers`

Create a new interviewer

#### Request body

The request body should be a partial (only `serialId`, `name`, `email`), json encoded [Interviewer](#interviewer) object

### `DELETE /interviewers/{serialId}`

Delete specific [Interviewer](#interviewer) object, identified by `{serialId}`


## Models

### Interviewer

- `id` string (ULID)
- `name` string
- `serialID` string (unique)
- `email` string (email address, unique)
- `area_periods` array (string, year/area)
- `training_record` array (training records)

#### Example

```json
{
  "id": "01G42P4XFB9V62H12NYVFMK03Y",
  "name": "Alice",
  "serialId": "1234",
  "email": "alice@example.com"
  "area_periods": [
    "2022/211500",
    "2022/212600"
  ],
  "training_record": [{}, {}, ...]
}
```

### Training Record
- `moduleNumber` int
- `moduleName` string
- `latestId` string (ULID)
- `state` string
- `created`: int | null (timestamp)
- `started`: int | null (timestamp)
- `completed`: int | null (timestamp)

#### Example

```json
{
  "moduleNumber": 1,
  "moduleName": "introduction",
  "latestId": "01H4G3PQ09FK0EDAPQZY1BQR2N",
  "state": "complete",
  "created": 1688462908,
  "started": 1688639589,
  "completed": null
}
```

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

### `GET /interviewers/{ULID}`

Retrieve specific [Interviewer](#interviewer) object, identified by `{ULID}`

#### Response

Successful response will be an [Interviewer](#interviewer) object (all fields will be returned)

### `POST /interviewers`

Create a new interviewer

#### Request body

The request body should be a json encoded [Interviewer](#interviewer) object (excluding `id`, and `area_periods`)

### `DELETE /interviewers/{ULID}`

Delete specific [Interviewer](#interviewer) object, identified by `{ULID}`


## Models

### Interviewer

- `id` string, optional (ULID)
- `name` string
- `serialID` string (unique)
- `email` string (email address, unique)
- `area_periods` array, optional (string, ULID)

#### Example

```json
{
  "id": "01G42P4XFB9V62H12NYVFMK03Y",
  "name": "Alice",
  "serialId": "AS43",
  "email": "alice@example.com"
  "area_periods": [
    "01G4A3QG7VQNCRCV1YZTJGYM9N",
    "01G4A5HFM6N41M3BNV3QSDQ9W8"
  ]
}
```

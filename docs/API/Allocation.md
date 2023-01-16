[Home](../../README.md) > [API](../API.md) > Allocation (Interviewers/Areas)

# Allocation (Interviewers/Areas)

## End points

### `POST /interviewers/{interviewer ULID}/allocate/{area_period ULID}`

Allocation the Interviewer identified by `{interviewer ULID}` to the AreaPeriod identified by `{area_period ULID}`

#### Response

Successful response will be the [Interviewer](Interviewers.md#interviewer) object (with the allocated area listed in `area_periods`)


### `POST /interviewers/{interviewer ULID}/deallocate/{area_period ULID}`

Deallocate the Interviewer identified by `{interviewer ULID}` from the AreaPeriod identified by `{area_period ULID}`

#### Response

Successful response will be the [Interviewer](Interviewers.md#interviewer) object (with the deallocated area no longer listed in `area_periods`)

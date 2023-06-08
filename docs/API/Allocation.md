[Home](../../README.md) > [API](../API.md) > Allocation (Interviewers/Areas)

# Allocation (Interviewers/Areas)

## End points

### `POST /interviewers/{serialId}/allocate/{year}/{area}`

Allocate the Interviewer identified by `{serialId}` to the AreaPeriod identified by `{year}` and `{area}`

#### Response

Successful response will be the updated [Interviewer](Interviewers.md#interviewer) object


### `POST /interviewers/{serialId}/deallocate/{year}/{area}`

Deallocate the Interviewer identified by `{serialId}` from the AreaPeriod identified by `{year}` and `{area}`

#### Response

Successful response will be the updated [Interviewer](Interviewers.md#interviewer) object

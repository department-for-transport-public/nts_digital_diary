[Home](../README.md) > API

# API

All API endpoints are available under the root URL `/api/v1`

## Index
- [Authentication](API/Authentication.md)
- [API Change log](API/ChangeLog.md)
- [Data types](API/DataTypes.md)
- [Survey Export API](API/SurveyExport.md)
   - [GET /survey-data](API/SurveyExport.md#get-survey-data)
- Allocation API
  - [Interviewers](API/Interviewers.md)
    - [GET /interviewers](API/Interviewers.md#get-interviewers)
    - [GET /interviewers/{ULID}](API/Interviewers.md#get-interviewersulid)
    - [POST /interviewers](API/Interviewers.md#post-interviewers)
    - [DELETE /interviewers/{ULID}](API/Interviewers.md#delete-interviewersulid)
  - [Areas](API/Areas.md)
    - [GET /area_periods](API/AreasPeriods.md#get-areaperiods)
    - [GET /area_periods/{ULID}](API/AreasPeriods.md#get-areaperiodsulid)
    - [POST /area_periods](API/AreasPeriods.md#post-areaperiods)
    - [DELETE /area_periods/{ULID}](API/AreasPeriods.md#delete-areaperiodsulid)
  - [Allocation (Interviewers/Areas)](API/Allocation.md)

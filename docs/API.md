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
    - [GET /interviewers/{serialId}](API/Interviewers.md#get-interviewersserialid)
    - [POST /interviewers](API/Interviewers.md#post-interviewers)
    - [DELETE /interviewers/{serialId}](API/Interviewers.md#delete-interviewersserialid)
  - [Areas](API/Areas.md)
    - [GET /area_periods](API/AreasPeriods.md#get-areaperiods)
    - [GET /area_periods/{year}/{area}](API/AreasPeriods.md#get-areaperiodsyeararea)
    - [POST /area_periods](API/AreasPeriods.md#post-areaperiods)
    - [DELETE /area_periods/{year}/{area}](API/AreasPeriods.md#delete-areaperiodsyeararea)
  - [Allocation (Interviewers/Areas)](API/Allocation.md)

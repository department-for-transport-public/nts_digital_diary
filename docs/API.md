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
  - [Households summary](API/Households.md)
    - [GET /households](API/Households.md#get-households) 
  - [Interviewers](API/Interviewers.md)
    - [GET /interviewers](API/Interviewers.md#get-interviewers)
    - [GET /interviewers/{serialId}](API/Interviewers.md#get-interviewersserialid)
    - [POST /interviewers](API/Interviewers.md#post-interviewers)
    - [DELETE /interviewers/{serialId}](API/Interviewers.md#delete-interviewersserialid)
  - [Areas](API/AreaPeriods.md)
    - [GET /area_periods](API/AreaPeriods.md#get-area_periods)
    - [GET /area_periods/{year}/{area}](API/AreaPeriods.md#get-area_periodsyeararea)
    - [POST /area_periods](API/AreaPeriods.md#post-area_periods)
    - [DELETE /area_periods/{year}/{area}](API/AreaPeriods.md#delete-area_periodsyeararea)
  - [Allocation (Interviewers/Areas)](API/Allocation.md)

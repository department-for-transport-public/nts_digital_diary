[Home](../../README.md) > [API](../API.md) > API Change Log

# API Change Log

## Changes in v2023.07.21

- **Fix**: better parameter error messages on export api
- **Update**: renamed `emptyDays` verification properties on Diary Keeper to `approvalChecklist` ([SurveyExport](./SurveyExport.md#diary-keeper)) 

## Changes in v2023.06.22

- **Fix**: primary data in export API was outputting costs as decimals whilst property change log was giving integers. Both now output as decimals

## Changes in v2023.04.25

- Interviewer now includes training record
- added `hasUsedPracticeDay` property to Diary Keeper (export)
- added `emptyDiaryDaysVerifiedBy` and `emptyDiaryDaysVerifiedAt` properties to Diary Keeper (export)
- added `state` property to diary keeper (export), indicating if the diary was approved or discarded by the interviewer

## Changes in v2023.03.22

### Household

- Added `vehicles` array

## Changes in v2023.01.04

### Diary Keeper

- added `mediaType`, string. `paper`|`digital`, indicating if the diary keeper has used a paper or digital diary (with paper having been transcribed by the interviewer)

### Journey

- added `id`, string | null (ULID). A unique identified for this journey
- added `sharedJourneyId`, string | null (ULID). If present, indicates that this journey is one that has been "shared" from another Diary Keeper and represents the id of the originating journey.

### Stage

- added `vehicleCapiNumber`, int | null

### History / Property Change Log

- renamed `distanceTravelledValue` to `distance`
- renamed `distanceTravelledUnit` to `distanceUnit`
- removed `method` and `methodOther` as method is not editable once a stage has been created
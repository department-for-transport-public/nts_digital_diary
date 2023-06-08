[Home](../../README.md) > [API](../API.md) > API Change Log

# API Change Log

## Changes in V1.3

- Interviewer now includes training record
- added `hasUsedPracticeDay` property to Diary Keeper (export)
- added `emptyDiaryDaysVerifiedBy` and `emptyDiaryDaysVerifiedAt` properties to Diary Keeper (export)
- added `state` property to diary keeper (export), indicating if the diary was approved or discarded by the interviewer

## Changes in v1.2

### Household

- Added `vehicles` array

## Changes in v1.1

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
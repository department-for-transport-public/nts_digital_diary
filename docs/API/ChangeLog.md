[Home](../../README.md) > [API](../API.md) > API Change Log

# API Change Log

## Changes in v1.1

### Journey

- added `id`, string | null (ULID). A unique identified for this journey
- added `sharedJourneyId`, string | null (ULID). If present, indicates that this journey is one that has been "shared" from another Diary Keeper and represents the id of the originating journey.

### Stage

- added `vehicleCapiNumber`, int | null

### History / Property Change Log

- renamed `distanceTravelledValue` to `distance`
- renamed `distanceTravelledUnit` to `distanceUnit`
- removed `method` and `methodOther` as method is not editable once a stage has been created
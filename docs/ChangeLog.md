[Home](../README.md) > Change log

# Change log

## 2023-07-26
- **Fix**: user loader issue preventing real user from logging in when their username had been used in training
- **Fix**: onboarding training was validating username uniqueness before setting training interviewer id
- **Update**: Repeat journey guidance/copy
- **Update**: change "paper diary" option when onboarding
- **Update**: Training module copy
- **Update**: Interviewer's diary keeper approval checklist, and related export API property names
- **Feature**: Journey splitter for simple one-stage journeys (interviewer-only tool)
- **Update**: Interviewer training areas are now fixed to Jan/2023
- **Update**: Previous households are deleted from interviewer training before creating a new training record (allowing serials to be the same each time)

## 2023-07-07
- **Feature**: New compare household screen for interviewers
- **Update**: Change odometer to milometer
- **Fix**: issues with costOrNil fields
- **Fix**: export API now gives more meaningful error messages when query params are invalid
- **Update**: replace interviewer training videos
- **Update**: Household with discarded diaries can now be submitted for export
- **Update**: added more tests - JourneyPropertyChangeLogTest and JourneyExportTest
- **Update**: add more stages into the screenshot tests so that bus/coach, private other and public other stages are shown in the sidebar
- **Update**: add context (side-column) for repeat journey wizard
- **Update**: make stage detail titles (e.g. car/walk/hovercraft) consistent across wizards, and add missing stage on "add stage" sidebar
- **Update**: update interviewer screenshotter to show new "compare households" feature
- **Fix**: add stricter requirements to "add journey" URL

## 2023-06-30
- **Update**: homepage title
- **Update**: change odometer to milometer
- **Feature**: showing list of onboarding codes to interviewers is now a switchable feature (off/not-shown by default)
- **Feature**: persist the hasCost part of costOrNil fields, and allow the cost part to be empty (allowing the
  distinction between "nil cost" and "there was a cost, but I cannot remember")
- **Fix**: add ordering character to pre-fill/post-fill/post-sub test screenshots so that they appear chronologically 
  when folder is ordered by name. 
- **Feature**: filter dashboard area for interviewers to show only current areas, with historical areas (more than 9 weeks ago) on separate page
- **Fix**: added error normalizer to improve error reporting on export API
- **Fix**: add missing "No vehicles" placeholder to onboarding completion screen, when no household vehicles
- **Feature**: show more household statistics on interviewer dashboard pages, and make terms clearer
- **Fix**: journey sharing does not work for interviewers impersonating a diary-keeper when trying to share to a diary that is in the completed state
- **Feature**: household comparison screen

## 2023-06-22
- **Update**: journey sharing now pre-fills the journey purpose if the destination of the journey being shared was "home".
- **Update**: onboarding "diary week start" field now shows help text giving the valid date range.
- **Feature**: during [Onboarding] add "Other" as a possibility when selecting a household vehicle type
- **Fix**: property change log was logging decimals as having changed when they hadn't
- **Update**: interviewer training now organised in to 8 modules
- **Feature**: interviewer training modules auto start/complete
- **Fix**: export API was exporting costs as decimals whilst property change log was logging them as integers
- **Fix**: property change log was not logging cost-related fields
- **Update**: add context (side-column) for return journey wizard
- **Fix**: costOrNil fields should be displayed as " - " when cost is 0.00
- **Update**: onboarding now reminds interviewers that participants must be over 12 years of age
- **Fix**: adding an interviewer via the admin was causing two welcome emails to be sent
- **Fix**: Auto generate any missing proxy files in production

## 2023-06-08
- **Update**: diary keeper and interviewer edit permission updates
- **Update**: add diary keeper state to export API (include discarded diaries in export)
- **Update**: update repeat journey wizard (and links)
- **Update**: add reminders on days 1/7 to record odometer readings
- **Feature**: feedback form
- **Update**: show diary states on dashboard for list of proxied diary keepers, and show same list on completed diary page

## 2023-05-23

- **Update**: allow [Onboarding] of Diary Keepers who plan to use a paper diary (for interviewers to transcribe before submission)
- **Update**: [Onboarding] add capiNumber field to Vehicle
- **Update**: use NatCen interviewer IDs and area numbers for object identification on [API]
- **Update**: redirect impersonation back to referring page
- **Update/Fix**: whole diary view (interviewers)
  - show shared journeys
  - formatting issue for journey with no stages
  - wrong journey number was being shown for journeys with no stages
  - replace column title for "passengers"
  - highlight "no stages"
- **Update**: make impersonation banner more obvious (change colour)
- **Feature**: interviewer training modules 
- **Feature**: add primary driver for vehicle during onboarding, and request odometer readings from that diary keeper as part of their diary
- **Feature**: add cleanup cron to remove OTP users a week after onboarding, or 1-2 months after their area period date
- **Update**: change travel times question to mention that 12pm is midday
- **Feature**: purge email addresses 60 days after submission (not yet active)
- **Feature**: purge survey data 200 days after submission (not yet active)
- Allow interviewer to discard diaries that have not been completed
- **Update**: Send email to DK after submission of diary, and add not to summary screen
- **Update**: include training record in interviewer API
- **Update**: indicate use of DK practice day on export API
- **Update**: interviewers now required to verify empty diary days for each diary keeper (details added to export API)
- **Update**: diary keeper guidance - short walk help and video guidance on dashboard
- **Fix**: no longer possible to share a journey with a diary that is marked as complete/approved
- **Update**: `cost or nil` for parking/ticket costs

## 2023-01-13

- **Feature**: added new [Allocation [API] end points](API/Allocation.md)
- **Update**: limited [survey data export](API/SurveyExport.md) [API] parameters
- **Fix**: and updates to [Property change log]
- Now requires PHP 8.1 (previously 7.4)
- Revamp/expand [project documentation](../README.md)
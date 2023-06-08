[Home](../README.md) > Change log

# Change log

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
# Journey/Stage property change log

This feature keeps a log of successive values (of relevant Journey and Stage properties) saved by interviewers and diary
keepers. Its purpose is to replicate interviewers using a green pen to edit a paper diary. This log is filtered according
to some simple rules, and the filtered data is included as part of the export API data for each Journey and Stage,
within the `_history` key.

For the purposes of this documentation, both adding and editing a journey or stage are considered a "change", and would
therefore be recorded in the change log.

## Filter rules

The logs are pruned according to the following rules:

- if all changes have been made by either a Diary Keeper or an Interviewer; only retain the most recent log entry
(however in this instance, to reduce export overhead, the change log will be empty on the export API - the purpose is
to only show changes made by different users)
- if the most recent change was made by an interviewer; retain the most recent interviewer log entry and the most recent
diary keeper log entry
- if the most recent change was made by a diary keeper; retain the most recent diary keeper log entry, the most recent
interviewer log entry, and the next most recent diary keeper log entry after the interviewer log entry. 

### Example 1

The following values were entered as the travel time on the same stage (in chronological order):

- diary keeper: 20 minutes
- interviewer: 15 minutes
- interviewer: 16 minutes

The filtering process would reduce the two consecutive interviewer changes down to one, making the change log look like
this:

- diary keeper: 20 minutes
- interviewer: 16 minutes

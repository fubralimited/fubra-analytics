# Fubra Analytics

- Only works in whole days.

- Switching accounts will alter data (obviously).

- Profiles and data is maintained, but only showed for current user accounts.

- Everything is based on accounts and accounts is reset on every update.

- Remember to configure per user api limit to be more than 1 request per second. e.g. 1000/sec


### TODO:
---

Change auth to use single instance like WP plugins and then remove passing in the client

Find way of managing profiles
    
	- Maybe set all profiles to updated = no then yes once updated and go on that only.

Filter profiles with no "ga:visits"
    
    - Add to exclude list to avoid in future.
    - Make exclude list editable..?
    - Only exclude if creation date is longer than x

Set a daily quota for querying past data.
    
    - Limit web interface to use archived data in the db and not api calls.


---

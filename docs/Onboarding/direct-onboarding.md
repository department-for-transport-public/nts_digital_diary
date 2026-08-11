# Direct onboarding

The point of direct (quick) onboarding is to reduce the amount of information that an interviewer must enter on the digital diary during onboarding.

A URL must be constructed with the relevant information added as query parameters. This URL could be used directly, or converted in to a QR code and scanned.

The base URL is `https://{domain}/onboarding/direct`

## Query parameters

Adding a `debug` parameter (with no value) will enable the display of any validation errors to aid with debugging. This option will not be available on the live/production environment.

### General data

- `passcode1` - the first access passcode
- `passcode2` - the second access passcode

### Household data

- `add` - the address number for the household / the second part of the serial number (expected to be in the range 1-25)
- `h` - the household number / the third part of the serial number (1-99)
- `start` - the diary week start date (yyyy-mm-dd)
- `cl` - the check letter

### Diary Keeper data
Diary keepers are represented using `dk` parameter prefix. Each diary keeper (household member) parameter must be indexed by their CAPI number and the name of the parameter. For example, diary keeper with CAPI number 3 might have a name parameter that looks like `dk[3][name]=Charlie`

- `name` - the diary keeper's name (short/friendly name for easy identification purposes, not full name. Max length 255 chars)
- `adult` - a boolean indicating if the user should be using an adult or child diary (1 => adult, 0 => child)

### Vehicle data
Vehicle are represented using `v` parameter prefix. Each vehicle parameter must be indexed by its CAPI number and the name of the parameter. For example, vehicle with CAPI number 3 might have a name parameter that looks like `v[3][name]=Red car`

- `name` - the vehicle's friendly name/identifier (Max length 255 chars)
- `method` - the vehicle's method code (4 => car, 5 => motorcycle, 6 => van or lorry, 0 => other)
- `driver` - the CAPI number for the diary keeper / household member who is the primary driver of this vehicle


## Examples

### Example 1

Passcodes:
- 1 - 33451065
- 2 - 50563944

Household:
- serial `405999/12/1`, 
- start date of 25th May 2024

Diary keepers:
- CAPI #1 - Alice (adult)
- CAPI #2 - Bob (adult)
- CAPI #3 - Charlie (child)

Vehicles:
- CAPI #1 - car known as `VW Golf`, mostly driven by Bob
- CAPI #2 - motorcycle known as `CBF125`, mostly driven by Alice

For such as household, the direct onboarding URL would look like this:
`https://{domain}/onboarding/direct?debug&passcode1=33451065&passcode2=50563944&add=12&h=1&cl=X&dk[1][name]=Alice&dk[1][adult]=1&dk[2][name]=Bob&dk[2][adult]=1&dk[3][name]=Charlie&dk[3][adult]=0&v[1][name]=VW Golf&v[1][method]=4&v[1][driver]=2&v[2][name]=CBF125&v[2][method]=5&v[2][driver]=1`

Separated for ease of reading...
```
https://{domain}/onboarding/direct
?debug
&passcode1=33451065
&passcode2=50563944
&add=12
&h=1
&cl=X
&dk[1][name]=Alice
&dk[1][adult]=1
&dk[2][name]=Bob
&dk[2][adult]=1
&dk[3][name]=Charlie
&dk[3][adult]=0
&v[1][name]=VW Golf
&v[1][method]=4
&v[1][driver]=2
&v[2][name]=CBF125
&v[2][method]=5
&v[2][driver]=1
```


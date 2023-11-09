[Home](../../README.md) > [API](../API.md) > Data types

# Data types

- **serialId** - string  
  NatCen's internal unique reference for an interviewer
- **area** - string  
  NatCen's internal reference for an area - numeric, but represented by a string as the first digit can be a zero
- **ULID** - string  
  26 character unique identifier (https://github.com/ulid/spec, https://symfony.com/doc/current/components/uid.html#ulids)  
  e.g. `01AN4Z07BY79KA1307SR9X4MV3`
- **decimal** - string
  Decimal precision number represented as a string to avoid rounding or floating point errors
  e.g. `46.934`
- **cost** - string
  essentially a decimal field, but with some extra encoding.
  - when a value of `"0.00"` is returned, the user indicated that there was no cost
  - when a value of `""` is returned, the user indicated that there was a cost, but did not enter a value for it
- **formatted date** - string  
  e.g. `2022-11-23`
- **formatted date / time** - string  
  ISO 8601  
  e.g. `2022-12-15T14:06:15+00:00`
- **formatted time** - string
  24 hour format with hours and minutes
  e.g. `23:15`, `00:00`

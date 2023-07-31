[Home](../README.md) > Doctrine

# Doctrine

## Custom types

### decimal_brick

Works in the same fashion as `decimal`, but deals in instances of `Brick\Math\BigDecimal` rather than `string`.

#### Scale conversion

Does *not* automatically handle the conversion to the relevant scale (nor is this possible, as only `$value` and `$platform` are passed to the `convertToPHPValue` and `convertToDatabaseValue` methods).

Conversion to the relevant scale and validation of adherence should be handled in the Form.

#### Example

With regards to the Distance entity:

* The `NumberType` form is employed to edit the Distance data.
* This form internally uses `BigDecimalToStringTransformer` to switch scale when handling decimals.
  * If the switch to the desired scale is possibly, it is done.
  * If not, the value is left at its inputted scale.
* The `ValidValueValidator` then internally uses `DecimalValidator` to check that the value is within the bounds of the desired scale.

So the two paths are that the inputted value has:

1) too many decimal places, and gets flagged by the validator.
2) less or an equal number of decimal places, and gets converted to the desired number (in this case 2 decimal places).


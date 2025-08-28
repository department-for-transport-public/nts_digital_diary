#!/bin/bash

# Check for two arguments
if [ "$#" -ne 2 ]; then
    echo "Usage: $0 <folder for comparison (red)> <folder to compare against (green)>"
    exit 1
fi

COMPARE=$1
COMPARE_TO=$2
# Output to a folder within the source set
RESULTS="${COMPARE}compared-to-${COMPARE_TO}"

function pad () { [ "$#" -gt 1 ] && [ -n "$2" ] && printf "%$2.${2#-}s" "$1"; }
function compare () {
    IMAGE_FROM="$COMPARE$1"
    IMAGE_TO="$COMPARE_TO$1"
    IMAGE_RESULT="$RESULTS$1"

    echo -n "  $(pad "$1" -80) "

    read WIDTH1 HEIGHT1 <<< $(identify -format "%w %h" "$IMAGE_FROM")
    read WIDTH2 HEIGHT2 <<< $(identify -format "%w %h" "$IMAGE_TO")
    if [ "$WIDTH1" -ne "$WIDTH2" ]; then
        echo "Error: Images have different widths ($WIDTH1 vs $WIDTH2)."
        exit 1
    fi

    # Determine the correct dimensions
    if [ "$HEIGHT1" -lt "$HEIGHT2" ]; then
        TARGET_DIMS="${WIDTH1}x${HEIGHT2}"
    else
        TARGET_DIMS="${WIDTH1}x${HEIGHT1}"
    fi

    convert '(' $IMAGE_TO -background white -extent $TARGET_DIMS -flatten -grayscale Rec709Luminance ')' \
        '(' $IMAGE_FROM -background white -extent $TARGET_DIMS -flatten -grayscale Rec709Luminance ')' \
        '(' -clone 0-1 -compose darken -composite ')' \
        -channel RGB -combine \
        $IMAGE_RESULT

    SCORE=`/usr/bin/compare -metric NCC "$IMAGE_FROM" "$IMAGE_TO" null: 2>&1`

    # Color based on thresholds
    if (( $(echo "$SCORE == 1.0" | bc -l) )); then
        COLOUR=$'\e[1;32m'  # Bright green
    elif (( $(echo "$SCORE >= .9" | bc -l) )); then
        COLOUR=$'\e[1;33m'  # Bright yellow/orange
    else
        COLOUR=$'\e[1;31m'  # Bright red
    fi

    echo -n $COLOUR$(awk "BEGIN { printf \"%.1f\", $SCORE * 100 }")%
    echo $'\e[0m'
}

echo Comparing images in $COMPARE to those in $COMPARE_TO

# prep result folder
rm -fr $RESULTS
rsync -a --include '*/' --exclude '*' $COMPARE $RESULTS

find "$COMPARE" -type f | grep .png | sort | while read line; do
    ## remove the comparison folder from the line, passing just the relative path to the image
    compare ${line#$COMPARE}
done

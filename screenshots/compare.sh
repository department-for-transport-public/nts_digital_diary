#!/bin/bash
set -euo pipefail

# Parse options
PARALLEL=false
PARALLEL_JOBS=4

show_help() {
    cat << EOF
Usage: $0 [OPTIONS] <folder1> <folder2>

Compare PNG screenshots between two folders and generate visual diff images.
Differences appear as red (from folder1) and green (from folder2).

Arguments:
    folder1     Folder for comparison (differences shown in red)
    folder2     Folder to compare against (differences shown in green)

Options:
    -h, --help      Show this help message and exit
    -p, --parallel  Run comparisons in parallel (requires GNU parallel)
    -j N            Number of parallel jobs (default: 4, implies -p)

Output:
    Results are saved to a folder alongside the source folders, including a
    scores.tsv file ("<relative path>\t<NCC score|missing|width-mismatch>" per image).

Exit codes:
    0   All comparisons passed (>= 99.7% match)
    1   Some comparisons had warnings or failures
EOF
    exit 0
}

while [[ $# -gt 0 ]]; do
    case "$1" in
        -h|--help)
            show_help
            ;;
        -p|--parallel)
            PARALLEL=true
            shift
            ;;
        -j)
            PARALLEL=true
            PARALLEL_JOBS="$2"
            shift 2
            ;;
        -*)
            echo "Unknown option: $1"
            echo "Usage: $0 [-h] [-p|--parallel] [-j N] <folder1> <folder2>"
            echo "Try '$0 --help' for more information."
            exit 1
            ;;
        *)
            break
            ;;
    esac
done

# Check for two folder arguments
if [ "$#" -ne 2 ]; then
    echo "Usage: $0 [-h] [-p|--parallel] [-j N] <folder1> <folder2>"
    echo "Try '$0 --help' for more information."
    exit 1
fi

# Check for required dependencies
MISSING=()
for cmd in rsync identify convert compare bc awk; do
    command -v "$cmd" >/dev/null 2>&1 || MISSING+=("$cmd")
done
if [ ${#MISSING[@]} -gt 0 ]; then
    echo $'\e[1;31m'"Error: missing required dependencies: ${MISSING[*]}"$'\e[0m'
    exit 1
fi

# Check for parallel if requested
if [ "$PARALLEL" = true ] && ! command -v parallel >/dev/null 2>&1; then
    echo "Warning: GNU parallel not found, falling back to sequential processing."
    PARALLEL=false
fi

COMPARE="${1%/}"
COMPARE_TO="${2%/}"

if [ ! -d "$COMPARE" ]; then
    echo $'\e[1;31m'"Error: source folder not found: $COMPARE"$'\e[0m'
    exit 1
fi
if [ ! -d "$COMPARE_TO" ]; then
    echo $'\e[1;31m'"Error: destination folder not found: $COMPARE_TO"$'\e[0m'
    exit 1
fi

# Output to a folder alongside the source set
RESULTS="$(dirname "$COMPARE")/$(basename "$COMPARE")-compared-to-$(basename "$COMPARE_TO")"

# Counters for summary
COUNT_TOTAL=0
COUNT_PASS=0
COUNT_WARN=0
COUNT_FAIL=0

function pad () { [ "$#" -gt 1 ] && [ -n "$2" ] && printf "%$2.${2#-}s" "$1"; }

# Append one "<relpath>\t<score-or-marker>" line to scores.tsv. <value> is the raw NCC
# score, or "missing" / "width-mismatch" when no score could be computed. Pass/warn/fail
# thresholds are deliberately NOT recorded here — the comparison page derives colours from
# the score, so the boundaries can be re-tuned without regenerating. A single printf
# appended with >> is atomic across parallel jobs (O_APPEND, one short write per line).
function record () {  # record <relpath> <score-or-marker>
    printf '%s\t%s\n' "${1#/}" "$2" >> "$RESULTS/scores.tsv"
}

function print_summary () {
    echo
    echo "========================================="
    echo "Summary: $COUNT_TOTAL files compared"
    echo -e "  \e[1;32mPassed:\e[0m  $COUNT_PASS"
    echo -e "  \e[1;33mWarning:\e[0m $COUNT_WARN"
    echo -e "  \e[1;31mFailed:\e[0m  $COUNT_FAIL"
    echo "========================================="
}

function compare () {
    IMAGE_FROM="$COMPARE$1"
    IMAGE_TO="$COMPARE_TO$1"
    IMAGE_RESULT="$RESULTS$1"

    echo -n "  $(pad "$1" -80) "

    if [ ! -f "$IMAGE_TO" ]; then
        echo $'\e[1;35m'"MISSING"$'\e[0m'
        record "$1" missing
        return 2
    fi

    read WIDTH1 HEIGHT1 <<< $(identify -format "%w %h" "$IMAGE_FROM")
    read WIDTH2 HEIGHT2 <<< $(identify -format "%w %h" "$IMAGE_TO")
    if [ "$WIDTH1" -ne "$WIDTH2" ]; then
        echo $'\e[1;31m'"WIDTH MISMATCH ($WIDTH1 vs $WIDTH2)"$'\e[0m'
        record "$1" width-mismatch
        return 2
    fi

    # Determine the correct dimensions
    if [ "$HEIGHT1" -lt "$HEIGHT2" ]; then
        TARGET_DIMS="${WIDTH1}x${HEIGHT2}"
    else
        TARGET_DIMS="${WIDTH1}x${HEIGHT1}"
    fi

    convert '(' "$IMAGE_TO" -background white -extent "$TARGET_DIMS" -flatten -grayscale Rec709Luminance ')' \
        '(' "$IMAGE_FROM" -background white -extent "$TARGET_DIMS" -flatten -grayscale Rec709Luminance ')' \
        '(' -clone 0-1 -compose darken -composite ')' \
        -channel RGB -combine \
        "$IMAGE_RESULT"

    SCORE=$(/usr/bin/compare -metric NCC "$IMAGE_FROM" "$IMAGE_TO" null: 2>&1)
    record "$1" "$SCORE"

    # Color based on thresholds
    local STATUS
    if (( $(echo "$SCORE >= 0.997" | bc -l) )); then
        COLOUR=$'\e[1;32m'  # Bright green
        STATUS=0
    elif (( $(echo "$SCORE >= .9" | bc -l) )); then
        COLOUR=$'\e[1;33m'  # Bright yellow/orange
        STATUS=1
    else
        COLOUR=$'\e[1;31m'  # Bright red
        STATUS=2
    fi

    echo -n "${COLOUR}$(awk "BEGIN { printf \"%.1f\", $SCORE * 100 }")%"
    echo $'\e[0m'
    return $STATUS
}

# Export for parallel execution
export COMPARE COMPARE_TO RESULTS
export -f pad compare record

echo "Comparing images in $COMPARE ("$'\e[1;31m'"red"$'\e[0m'") to those in $COMPARE_TO ("$'\e[1;32m'"green"$'\e[0m'"). Outputting to $RESULTS"

# prep result folder
rm -fr "$RESULTS"
rsync -a --include '*/' --exclude '*' "$COMPARE/" "$RESULTS/"

if [ "$PARALLEL" = true ]; then
    # Parallel execution with job log for summary
    echo "Running in parallel mode with $PARALLEL_JOBS jobs"
    echo
    JOBLOG=$(mktemp)
    trap 'rm -f "$JOBLOG"' EXIT

    find "$COMPARE" -type f -name "*.png" | sort | \
        parallel --joblog "$JOBLOG" -j "$PARALLEL_JOBS" 'f={}; compare "${f#'"$COMPARE"'}"' || true

    # Parse job log for summary (column 7 is exit code: 0=pass, 1=warn, 2=fail)
    COUNT_TOTAL=$(tail -n +2 "$JOBLOG" | wc -l)
    COUNT_PASS=$(tail -n +2 "$JOBLOG" | awk '$7 == 0' | wc -l)
    COUNT_WARN=$(tail -n +2 "$JOBLOG" | awk '$7 == 1' | wc -l)
    COUNT_FAIL=$(tail -n +2 "$JOBLOG" | awk '$7 >= 2' | wc -l)

    print_summary
else
    # Sequential execution with counters
    while read -r line; do
        ((COUNT_TOTAL++)) || true
        status=0
        compare "${line#$COMPARE}" || status=$?
        case $status in
            0) ((COUNT_PASS++)) || true ;;
            1) ((COUNT_WARN++)) || true ;;
            *) ((COUNT_FAIL++)) || true ;;
        esac
    done < <(find "$COMPARE" -type f -name "*.png" | sort)

    print_summary
fi

# Exit with appropriate code
# Fail if any failures, or if warnings exceed 5% of total
if [ "$COUNT_FAIL" -gt 0 ]; then
    exit 1
fi
if [ "$COUNT_TOTAL" -gt 0 ]; then
    WARN_PERCENT=$(awk "BEGIN { printf \"%.0f\", ($COUNT_WARN / $COUNT_TOTAL) * 100 }")
    if [ "$WARN_PERCENT" -gt 5 ]; then
        exit 1
    fi
fi
exit 0

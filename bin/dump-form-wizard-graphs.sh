#!/usr/bin/env sh

# Missing "dot"?
# apt install graphviz

SCRIPT_DIR=`dirname "$0"`
OUTPUT_DIR=$SCRIPT_DIR/../var/form-wizards
mkdir -p $OUTPUT_DIR

$SCRIPT_DIR/console nts:form-wizard:list | \
  while read formWizard
  do
    $SCRIPT_DIR/console workflow:dump "$formWizard" | dot -Tpng -o "$OUTPUT_DIR/$formWizard".png
  done

## Alternative method, not using list console command/tagging
#$SCRIPT_DIR/console debug:cont 2> /dev/null | \
#    grep -oE 'form_wizard(\.\w+)+' | \
#    grep -Ev '\.(definition|metadata_store)' | \
#    sort -u | \
#    while read in; do $SCRIPT_DIR/console workflow:dump "$in" --dump-format=mermaid | $SCRIPT_DIR/../node_modules/.bin/mmdc -o "$in.svg"; done

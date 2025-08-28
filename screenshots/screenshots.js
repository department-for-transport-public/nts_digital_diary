#!/usr/bin/env node
let yargs

try {
    yargs = require('yargs/yargs')
    require('puppeteer')
} catch(e) {
    console.log('FATAL: Missing dependencies - run "yarn install" in screenshots directory')
    return 1
}

const {hideBin} = require('yargs/helpers')
const frontend = require('./src/frontend')

const generateCommand = (yargs) => {
    // noinspection BadExpressionStatementJS
    yargs
        .positional('baseUrl', {
            description: 'The root URL of the DfT NTS website',
            type: 'string'
        })
        .positional('outputDir', {
            description: 'Output directory',
            type: 'string',
        })
        .options({
            'd': {
                alias: 'documentation',
                boolean: true,
                default: false,
                describe: 'Generate (only) the screenshots for documentation',
                type: 'boolean',
            }
        })
        .options({
            'e': {
                alias: 'extra-diary-stages',
                boolean: true,
                default: false,
                describe: 'Add extra stages into the diary keeper flow',
                type: 'boolean',
            }
        })
        .strictCommands()
        .strictOptions();
}

// noinspection BadExpressionStatementJS
yargs(hideBin(process.argv))
    .usage('usage: $0 <command>')
    .command('frontend <baseUrl> <outputDir>', 'Generates screenshots of the NTS website frontend', yargs => generateCommand(yargs, false), argv => frontend.run(argv))
    .demandCommand(1)
    .help()
    .argv

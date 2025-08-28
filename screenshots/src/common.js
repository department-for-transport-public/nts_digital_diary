const fs = require('node:fs')
const path = require('node:path')
const puppeteer = require("puppeteer")
const readline = require("node:readline")
const sharp = require("sharp")
const {xpathHelper} = require("./xpath")

module.exports.setupBrowserAndPage = async () => {
    const browser = await puppeteer.launch({
        args: [
            '--single-process',
            '--ignore-certificate-errors',
        ],
        // ignoreHTTPSErrors: true,
        //headless: false,
    });
    const page = await browser.newPage();
    await page.setCacheEnabled(false);
    await page.setViewport({width: 1003, height: 200});

    return [browser, page];
}

const waitForOptions = {
    timeout: 10000,
    waitUntil: "load",
}

module.exports.waitForOptions = waitForOptions

async function type(page, fieldId, value) {
    const $field = await page.$(fieldId)
    await $field.type(value)
}

module.exports.type = type

module.exports.goto = async function(page, url) {
    await page.goto(url, waitForOptions)
}

const predicate = function(options)
{
    const predicates =  []

    if (options.text !== undefined) {
        predicates.push(`normalize-space(text()) = '${options.text}'`)
    }

    if (options.textStartsWith !== undefined) {
        predicates.push(`starts-with(normalize-space(text()), '${options.textStartsWith}')`)
    }

    if (options.textContains !== undefined) {
        predicates.push(`contains(text(), '${options.textContains}')`)
    }

    if (options.class !== undefined) {
        predicates.push(`contains(concat(' ', normalize-space(@class), ' '), ' ${options.class} ')`)
    }

    return predicates.length === 0 ?
        '' :
        `[${predicates.join(' and ')}]`
}

module.exports.predicate = predicate

const findElement = async function(page, expression, index=0) {
    const elements = await page.$$(expression)

    if (!elements[index]) {
        throw new Error(`Element not found for ${expression}[${index}]`);
    }

    return elements[index];
}

module.exports.findElement = findElement

const clickElement = async function(page, element, waitForNavigation = true) {
    const promises = []

    if (waitForNavigation) {
        promises.push(page.waitForNavigation(waitForOptions))
    }

    promises.push(element.click())
    await Promise.all(promises)
}

const clickLink = async function(page, expression, index = 0, waitForNavigation = true) {
    let element = await findElement(page, expression, index)
    await clickElement(page, element, waitForNavigation)
}

module.exports.clickElement = clickElement
module.exports.clickLink = clickLink

module.exports.clickElementWithText = async function(page, elementType, text, index = 0, waitForNavigation = true) {
    await clickLink(page, `xpath///${elementType}${predicate({text})}`, index, waitForNavigation)
}

module.exports.clickElementContainingText = async function(page, elementType, textContains, index = 0, waitForNavigation = true) {
    await clickLink(page, `xpath///${elementType}${predicate({textContains})}`, index, waitForNavigation)
}

module.exports.clickLinkWithText = async function(page, text, index = 0, waitForNavigation = true) {
    await clickLink(page, `xpath///a${predicate({text})}`, index, waitForNavigation)
}

module.exports.clickLinkContainingText = async function(page, textContains, index = 0, waitForNavigation = true) {
    await clickLink(page, `xpath///a${predicate({textContains})}`, index, waitForNavigation)
}

module.exports.clickLinkWithTextThatStartsWith = async function(page, textStartsWith, index = 0, waitForNavigation = true) {
    await clickLink(page, `xpath///a${predicate({textStartsWith})}`, index, waitForNavigation)
}

module.exports.clickButtonWithText = async function(page, text, index = 0, waitForNavigation = true) {
    await clickLink(page, `xpath///button${predicate({text})}`, index, waitForNavigation)
}

module.exports.enterDate = async function(page, id_prefix, year, month, day) {
    await type(page, `${id_prefix}_day`, day)
    await type(page, `${id_prefix}_month`, month)
    await type(page, `${id_prefix}_year`, year)
}

module.exports.clickOptionValue = async function(page, option_value, context_id) {
    let expression = context_id !== undefined
        ? `xpath///*[@id="${context_id}"]//input[@value="${option_value}"]`
        : `xpath///input[@value="${option_value}"]`;
    let element = await findElement(page, expression, 0);
    await element.click();
}

module.exports.idOfInputWhoseLabelContains = async function(page, textContains, index = 0) {
    let expression = `xpath///label${predicate({textContains})}`;
    let element = await findElement(page, expression, index);
    return await element.evaluate(e => '#' + e.getAttribute('for'));
}

const fillForm = async function(page, data, xpathPrefix = 'xpath///form')
{
    for(const [labelOrId, value] of Object.entries(data)) {
        if (typeof value === 'object') {
            let xpath
            if (labelOrId.startsWith('#')) {
                xpath = xpathPrefix + `//*[@id="${labelOrId.substring(1)}"]`
            } else {
                xpath = xpathHelper({
                    prefix: xpathPrefix,
                    tags: ['legend'],
                    text: labelOrId,
                    textStartsWith: true,
                    suffix: '/..',
                })
            }

            await fillForm(page, value, xpath)
            continue
        }

        let targetId
        if (labelOrId.startsWith('#')) {
            targetId = labelOrId
        } else {
            const xpath = xpathHelper({
                prefix: xpathPrefix,
                tags: ['label'],
                text: labelOrId,
                textStartsWith: true,
            })

            const labelElement = await page.$(xpath)
            if (!labelElement) {
                throw new Error(`Could not find match for "${labelOrId}" [XPath: "${xpath}]"]`)
            }

            targetId = await labelElement.evaluate(x => x.getAttribute('for'))
            if (!targetId) {
                throw new Error(`Could not find referenced (for) element for label element "${labelOrId}"`)
            }

            targetId = `#${targetId}`
        }

        const targetElement = await page.$(targetId)

        if (value === true || value === false) {
            const type = await targetElement.evaluate(x => x.type)

            if (!['checkbox', 'radio'].includes(type)) {
                throw new Error(`Boolean values are only supported for checkbox and radio elements`)
            }

            const currentValue = await targetElement.evaluate(x => x.checked)
            if (currentValue !== value) {
                await targetElement.click()
            }
        } else {
            // See: https://stackoverflow.com/a/52633235/865429
            await targetElement.click({clickCount: 4})
            await targetElement.type(value)
        }
    }
}

module.exports.fillForm = fillForm;

module.exports.submit = async function(page, data, buttonText) {
    const buttons = {}
    for (const buttonElement of await page.$$('form button')) {
        const text = await buttonElement.evaluate(x => x.textContent)
        buttons[text] = buttonElement;
    }

    await fillForm(page, data)

    const buttonElement = buttons[buttonText];

    if (!buttonElement) {
        throw new Error(`Button not found with text ${buttonText}]`)
    }

    await Promise.all([
        page.waitForNavigation(waitForOptions),
        buttonElement.click(),
    ])
}

const getScreenshotPathHelper = (outputDir) => {
    return (relativePath) => {
        const fullPath = outputDir + relativePath
        const dir = path.dirname(fullPath)
        fs.mkdirSync(dir, {recursive: true})
        return fullPath
    }
}

async function getBoundingBoxForSelector(page, selector)
{
    const element = await page.waitForSelector(selector, {timeout: 0}) // We're selecting items already present on the page

    if (!element) {
        throw new Error(`Warning: Could not find element for selector '${selector}'`)
    }

    return await element.evaluate((e) => {
        const {top, bottom, left, right} = e.getBoundingClientRect()
        return {top, bottom, left, right}
    })
}

async function getPageRelativeBoundingBoxForSelector(page, selector, bodyRect, isXPath=false)
{
    let rect = await getBoundingBoxForSelector(page, selector, isXPath)

    const yOffset = bodyRect.top
    const xOffset = bodyRect.left

    // Make relative to page...
    return {top: rect.top - yOffset, bottom: rect.bottom - yOffset, left: rect.left - xOffset, right: rect.right - xOffset}
}

async function getPageRelativeBoundBoxForSelectors(page, selectors, bodyRect)
{
    let minTop = null
    let maxBottom = null
    let minLeft = null
    let maxRight = null

    const min = (value, current) => Math.floor(current === null ? value : (value < current ? value : current))
    const max = (value, current) => Math.ceil(current === null ? value : (value > current ? value : current))

    for(const selector of selectors) {
        const rect = await getPageRelativeBoundingBoxForSelector(page, selector, bodyRect)

        minTop = min(rect.top, minTop)
        minLeft = min(rect.left, minLeft)
        maxBottom = max(rect.bottom, maxBottom)
        maxRight = max(rect.right, maxRight)
    }

    return {top: minTop, bottom: maxBottom, left: minLeft, right: maxRight}
}

async function resolveCropSelector(page, options)
{
    const bodyRect = await getBoundingBoxForSelector(page, 'body')
    const selectors = Array.isArray(options.cropSelector) ? options.cropSelector : [options.cropSelector]
    let rect = await getPageRelativeBoundBoxForSelectors(page, selectors, bodyRect)

    if (options.cropPadding) {
        const padding = typeof options.cropPadding === 'object' ?
            Object.assign({top: 0, bottom: 0, left: 0, right: 0}, options.cropPadding) :
            {top: options.cropPadding, bottom: options.cropPadding, left: options.cropPadding, right: options.cropPadding}

        rect = {
            top: rect.top - padding.top,
            bottom: rect.bottom + padding.bottom,
            left: rect.left - padding.left,
            right: rect.right + padding.right,
        }

        const maxY = bodyRect.bottom - bodyRect.top
        const maxX = bodyRect.right - bodyRect.left

        rect.top = Math.max(rect.top, 0)
        rect.left = Math.max(rect.left, 0)
        rect.right = Math.min(rect.right, maxX)
        rect.bottom = Math.min(rect.bottom, maxY)
    }

    const {cropSelector, ...newOptions} = options

    // swap to top/left/width/height for cropper
    return Object.assign(newOptions, {
        cropRegion: {top: rect.top, left: rect.left, width: rect.right - rect.left, height: rect.bottom - rect.top}
    })
}

module.exports.getScreenshotter = (page, outputDir) => async (path, options={}) => {
    const takeScreenshot = async(path, options = {}) => {
        const fullPath = getScreenshotPathHelper(outputDir)(path);
        await page.screenshot({path: fullPath, fullPage: true})

        if (options.hasOwnProperty('cropSelector')) {
            options = await resolveCropSelector(page, options)
        }

        const hasCropRegion = options.hasOwnProperty('cropRegion')
        const hasCropCoords = options.hasOwnProperty('cropX1')
            || options.hasOwnProperty('cropX2')
            || options.hasOwnProperty('cropY1')
            || options.hasOwnProperty('cropY2')

        if (hasCropRegion || hasCropCoords) {
            sharp.cache(false)
            let buffer = await sharp(fullPath).toBuffer()

            if (hasCropRegion) {
                const image = await sharp(buffer)
                buffer = await image.extract(options.cropRegion).toBuffer()
            }

            if (hasCropCoords) {
                const image = await sharp(buffer)
                const metadata = await image.metadata()

                const cropX1 = options.cropX1 ? options.cropX1 : 0
                const cropX2 = options.cropX2 ? options.cropX2 : metadata.width
                const cropY1 = options.cropY1 ? options.cropY1 : 0
                const cropY2 = options.cropY2 ? options.cropY2 : metadata.height

                let region = {top: cropY1, left: cropX1, width: cropX2 - cropX1, height: cropY2 - cropY1};

                buffer = await image.extract(region).toBuffer()
            }

            await sharp(buffer).toFile(fullPath)
        }
    }

    try {
        await takeScreenshot(path, options)
    }
    catch(e) {
        console.log(`Warning: Taking screenshot failed - waiting and retrying... (${path})`);
        await new Promise(r => setTimeout(r, 1000))
        await takeScreenshot(path, options)
    }
}

module.exports.executeCommand = (command) => {
    const {stdin: input, stdout: output} = require('node:process')
    const rl = readline.createInterface({input, output})

    return new Promise(r => rl.question(`SCREENSHOTS:${command}`, answer => {
        rl.close()

        if (answer.startsWith('OK: ')) {
            r(answer.substring(4))
        } else {
            if (answer.startsWith('FAIL: ')) {
                process.stderr.write('Error from parent process: ' + answer.substring(6))
            } else {
                process.stderr.write('Unsupported answer format: ' + answer)
            }
            process.exit(1)
        }
    }))
}

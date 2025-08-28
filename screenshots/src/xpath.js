module.exports.xpathHelper = ({
        cls,
        prefix,
        suffix,
        tags = [],
        text,
        textStartsWith = false
    } = {}
) => {
    let predicates = []

    if (text !== undefined) {
        if (textStartsWith === true) {
            predicates.push(`starts-with(normalize-space(text()), '${text}')`)
        } else {
            predicates.push(`normalize-space(text()) = '${text}'`)
        }
    }

    if (cls !== undefined) {
        predicates.push(`contains(concat(' ', normalize-space(@class), ' '), ' ${cls} ')`)
    }

    predicates = predicates.join(' and ')

    let xpath = (prefix ?? '') + '//'
    if (tags.length === 1) {
        xpath += tags[0]
    } else {
        xpath += '*'

        const tagPredicates = tags.map((tag) => `self::${tag}`).join(' or ')
        predicates = `(${tagPredicates}) and (${predicates})`
    }

    return xpath +
        (predicates ? `[${predicates}]` : '') +
        (suffix ?? '')
}

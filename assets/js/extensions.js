Object.prototype.toFormData = function (obj) {
    let flatten = function (obj, output, itemPrefix) {
        if (output === undefined) {
            output = [];
        }
        if (typeof obj !== "object") {
            output[itemPrefix] = obj;
            return;
        }
        Object.keys(obj).forEach((idx) => {
            flatten(obj[idx], output, itemPrefix ? (itemPrefix + '[' + idx + ']') : idx);
        });
        return output;
    }

    let formDataFromFlatObject = function(obj) {
        let formData = new FormData();
        Object.keys(obj).forEach((idx) => {
            formData.append(idx, obj[idx]);
        });
        return formData;
    }

    return formDataFromFlatObject(flatten(obj));
}
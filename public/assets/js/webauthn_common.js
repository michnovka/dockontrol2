function recursiveBase64StrToArrayBuffer(obj)
{
    const prefix = "=?BINARY?B?";
    const suffix = "?=";

    if (typeof obj === "string" && obj.startsWith(prefix) && obj.endsWith(suffix)) {
        // Extract Base64 string
        let base64Str = obj.slice(prefix.length, -suffix.length);

        // Decode Base64 into binary string
        let binaryString = atob(base64Str);

        // Convert binary string into ArrayBuffer
        let bytes = new Uint8Array(binaryString.length);
        for (let i = 0; i < binaryString.length; i++) {
            bytes[i] = binaryString.charCodeAt(i);
        }
        return bytes.buffer; // Return as ArrayBuffer
    }

    if (Array.isArray(obj)) {
        return obj.map(item => recursiveBase64StrToArrayBuffer(item)); // Process arrays recursively
    }

    if (obj !== null && typeof obj === "object") {
        let newObj = {}; // Create a new object to avoid mutation issues
        Object.entries(obj).forEach(([key, value]) => {
            newObj[key] = recursiveBase64StrToArrayBuffer(value);
        });
        return newObj;
    }

    return obj;
}


/**
 * Convert a ArrayBuffer to Base64
 * @param {ArrayBuffer} buffer
 * @returns {String}
 */
function arrayBufferToBase64(buffer)
{
    return btoa(String.fromCharCode.apply(null, new Uint8Array(buffer)));
}
